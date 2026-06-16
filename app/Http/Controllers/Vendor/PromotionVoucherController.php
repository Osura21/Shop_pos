<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromotionVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class PromotionVoucherController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        return Inertia::render('VendorAdmin/Promotions/Vouchers/Index');
    }

    public function getData(Request $request)
    {
        $rows = PromotionVoucher::query()
            ->with('branch:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('promotion_vouchers.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (! filled($search)) {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                });
            })
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row->type))
            ->addColumn('activation_badge', fn ($row) => $this->activationBadge((bool) $row->is_active))
            ->editColumn('value', fn ($row) => $row->type === 'percentage' ? $this->money($row->value).'%' : $this->money($row->value))
            ->editColumn('secondary_value', fn ($row) => $row->secondary_value !== null ? $this->money($row->secondary_value) : null)
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['type_badge', 'activation_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Promotions/Vouchers/CreateUpdate', array_merge($this->promotionFormProps(), [
            'voucher' => null,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->promotionRules());
        PromotionVoucher::create(array_merge($this->promotionPayload($validated), [
            'code' => $validated['code'] ?: $this->generateCode(),
        ]));

        return redirect()->route('vendor.promotions.vouchers.index')->with('success', 'Voucher created successfully.');
    }

    public function edit($id)
    {
        $voucher = PromotionVoucher::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Promotions/Vouchers/CreateUpdate', array_merge($this->promotionFormProps(), [
            'voucher' => $voucher,
        ]));
    }

    public function update(Request $request, $id)
    {
        $voucher = PromotionVoucher::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->promotionRules($voucher->id));
        $voucher->update(array_merge($this->promotionPayload($validated), [
            'code' => $validated['code'] ?: $voucher->code,
        ]));

        return redirect()->route('vendor.promotions.vouchers.index')->with('success', 'Voucher updated successfully.');
    }

    public function destroy($id)
    {
        PromotionVoucher::where('tenant_id', $this->tenantId())->findOrFail($id)->delete();

        return redirect()->route('vendor.promotions.vouchers.index')->with('success', 'Voucher deleted successfully.');
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (PromotionVoucher::where('tenant_id', $this->tenantId())->where('code', $code)->exists());

        return $code;
    }

    private function promotionRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('promotion_vouchers', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (request()->input('type') === 'percentage' && $value > 100) {
                        $fail('The discount percentage cannot exceed 100%.');
                    }
                },
            ],
            'secondary_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'secondary_max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'secondary_min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_spend' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $minSpend = request()->input('min_spend');
                    if ($minSpend !== null && $minSpend !== '' && $value <= $minSpend) {
                        $fail('The maximum spend must be greater than the minimum spend.');
                    }
                },
            ],
            'secondary_max_spend' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $minSpend = request()->input('secondary_min_spend');
                    if ($minSpend !== null && $minSpend !== '' && $value <= $minSpend) {
                        $fail('The secondary maximum spend must be greater than the secondary minimum spend.');
                    }
                },
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'order_types' => ['nullable', 'array'],
            'order_types.*' => ['string', Rule::in($this->orderTypeKeys())],
            'available_days' => ['nullable', 'array'],
            'available_days.*' => ['string', Rule::in($this->dayKeys())],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'is_active' => ['boolean'],
        ];
    }

    private function promotionPayload(array $validated): array
    {
        return [
            'tenant_id' => $this->tenantId(),
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'] ?? 0,
            'secondary_value' => $this->secondaryCurrencyCode() ? ($validated['secondary_value'] ?? null) : null,
            'max_discount' => $validated['max_discount'] ?? null,
            'secondary_max_discount' => $this->secondaryCurrencyCode() ? ($validated['secondary_max_discount'] ?? null) : null,
            'min_spend' => $validated['min_spend'] ?? null,
            'secondary_min_spend' => $this->secondaryCurrencyCode() ? ($validated['secondary_min_spend'] ?? null) : null,
            'max_spend' => $validated['max_spend'] ?? null,
            'secondary_max_spend' => $this->secondaryCurrencyCode() ? ($validated['secondary_max_spend'] ?? null) : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'order_types' => array_values($validated['order_types'] ?? []),
            'available_days' => array_values($validated['available_days'] ?? []),
            'category_ids' => array_map('intval', $validated['category_ids'] ?? []),
            'product_ids' => array_map('intval', $validated['product_ids'] ?? []),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function promotionFormProps(): array
    {
        return [
            'branches' => Branch::where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get(),
            'categories' => $this->categoryOptions(),
            'products' => Product::where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get(),
            'orderTypes' => collect($this->orderTypeKeys())->map(fn ($key) => ['value' => $key, 'label' => $this->labelize($key)])->values(),
            'availableDays' => collect($this->dayKeys())->map(fn ($key) => ['value' => $key, 'label' => ucfirst($key)])->values(),
        ];
    }

    private function orderTypeKeys(): array
    {
        return ['takeaway', 'dine_in', 'pick_up', 'drive_thru', 'pre_order', 'catering'];
    }

    private function categoryOptions(): Collection
    {
        $items = Category::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'menu_id', 'parent_id', 'name', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $result = [];
        $this->appendCategoryOptions($items, null, 0, $result);

        return collect($result);
    }

    private function appendCategoryOptions(Collection $items, $parentId, int $depth, array &$result): void
    {
        $children = $items
            ->where('parent_id', $parentId)
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ]);

        foreach ($children as $item) {
            $prefix = $depth > 0 ? str_repeat('|-- ', $depth) : '';

            $result[] = [
                'id' => $item->id,
                'menu_id' => $item->menu_id,
                'parent_id' => $item->parent_id,
                'name' => $item->name,
                'display_name' => $prefix.$item->name,
                'depth' => $depth,
            ];

            $this->appendCategoryOptions($items, $item->id, $depth + 1, $result);
        }
    }

    private function dayKeys(): array
    {
        return ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    }

    private function labelize(string $value): string
    {
        return str($value)->replace('_', '-')->title()->toString();
    }

    private function activationBadge(bool $active): string
    {
        return $active
            ? '<span class="badge rounded-pill bg-success-subtle text-success"><i class="bi bi-check-lg"></i> Active</span>'
            : '<span class="badge rounded-pill bg-danger-subtle text-danger"><i class="bi bi-x-lg"></i> Inactive</span>';
    }

    private function typeBadge(string $type): string
    {
        return '<span class="badge rounded-pill bg-info-subtle text-info">'.e(ucfirst($type)).'</span>';
    }

    private function money($value): string
    {
        return number_format((float) $value, 3);
    }
}
