<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class StockManagementController extends Controller
{
    private const TYPE_OPTIONS = [
        'in' => 'Stock In',
        'out' => 'Stock Out',
        'wastage' => 'Wastage / Spoil',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Inventory/StockManagement/Index', [
            'branches' => $this->branches(),
            'products' => $this->products(),
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function getData(Request $request)
    {
        $movements = ProductStockMovement::query()
            ->with([
                'branch:id,name',
                'product:id,name,sku,brand,unit_type',
            ])
            ->where('tenant_id', $this->tenantId())
            ->select('product_stock_movements.*');

        return DataTables::of($movements)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhere('brand', 'like', "%{$search}%");
                        })
                            ->orWhere('note', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('product_name', fn ($row) => $row->product?->name ?? '-')
            ->addColumn('product_sku', fn ($row) => $row->product?->sku ?? '-')
            ->addColumn('unit_type', fn ($row) => $row->product?->unit_type ?: 'pcs')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row->type))
            ->addColumn('quantity_display', fn ($row) => $this->decimalLabel($row->quantity) . ' ' . ($row->product?->unit_type ?: 'pcs'))
            ->addColumn('stock_before_display', fn ($row) => $this->decimalLabel($row->stock_before))
            ->addColumn('stock_after_display', fn ($row) => $this->decimalLabel($row->stock_after))
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d H:i') ?: '-')
            ->rawColumns(['type_badge'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'type' => ['required', Rule::in(array_keys(self::TYPE_OPTIONS))],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->findOrFail($validated['product_id']);

            $quantity = (float) $validated['quantity'];
            $before = (float) ($product->current_stock ?? 0);
            $delta = $this->signedQuantity($validated['type'], $quantity);
            $after = $before + $delta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock cannot go below zero.',
                ]);
            }

            ProductStockMovement::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $validated['branch_id'] ?? null,
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $validated['note'] ?? null,
            ]);

            $product->current_stock = $after;
            $product->save();
        });

        return back()->with('success', 'Stock movement recorded successfully.');
    }

    private function typeBadge(string $type): string
    {
        $label = self::TYPE_OPTIONS[$type] ?? ucfirst($type);

        if ($type === 'in') {
            return '<span class="badge rounded-pill bg-success-subtle text-success border border-success px-2 py-1">Stock In</span>';
        }

        if ($type === 'wastage') {
            return '<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning px-2 py-1">Wastage / Spoil</span>';
        }

        return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-2 py-1">' . e($label) . '</span>';
    }

    private function signedQuantity(string $type, float $quantity): float
    {
        return match ($type) {
            'in' => $quantity,
            'out', 'wastage' => $quantity * -1,
            default => 0,
        };
    }

    private function decimalLabel($value): string
    {
        return rtrim(rtrim(number_format((float) ($value ?? 0), 3, '.', ''), '0'), '.') ?: '0';
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function products()
    {
        return Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'brand', 'unit_type', 'current_stock')
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand,
                'unit_type' => $product->unit_type ?: 'pcs',
                'current_stock' => (float) ($product->current_stock ?? 0),
                'label' => trim($product->name . ' - ' . $product->sku),
            ]);
    }
}
