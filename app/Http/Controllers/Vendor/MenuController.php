<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Menu;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class MenuController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Menu/AllMenu/Index');
    }

    public function getData(Request $request)
    {
        $allBranches = Branch::where('tenant_id', $this->tenantId())
            ->pluck('name', 'id');

        $menus = Menu::query()
            ->where('tenant_id', $this->tenantId())
            ->select('menus.*');

        return DataTables::of($menus)

            ->addColumn('branch_name', function ($row) use ($allBranches) {
                if (! $row->branch_ids) {
                    return '-';
                }

                $names = collect($row->branch_ids)
                    ->map(fn ($id) => $allBranches[$id] ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                return count($names) ? implode(', ', $names) : '-';
            })
            ->addColumn('status', function ($row) {
                return (int) $row->is_active;
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Menu/AllMenu/CreateUpdate', [
            'menu' => null,
            'branches' => $this->branches(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $menu = new Menu;
            $menu->tenant_id = $this->tenantId();
            $this->fillMenu($menu, $validated);
            $menu->save();

            DB::commit();

            return redirect()
                ->route('vendor.menus.index')
                ->with('success', 'Menu created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Menu store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to save menu.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Menu store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Something went wrong while creating the menu.',
            ]);
        }
    }

    public function edit($id)
    {
        $menu = Menu::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Menu/AllMenu/CreateUpdate', [
            'menu' => $menu,
            'branches' => $this->branches(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::where('tenant_id', $this->tenantId())->findOrFail($id);

        $validated = $request->validate($this->rules($menu->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillMenu($menu, $validated);
            $menu->save();

            DB::commit();

            return redirect()
                ->route('vendor.menus.index')
                ->with('success', 'Menu updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Menu update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update menu.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Menu update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Something went wrong while updating the menu.',
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $menu = Menu::where('tenant_id', $this->tenantId())->findOrFail($id);
            $menu->delete();

            return redirect()
                ->route('vendor.menus.index')
                ->with('success', 'Menu deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Menu delete failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete menu.',
            ]);
        }
    }

    private function fillMenu(Menu $menu, array $validated): void
    {
        $menu->branch_ids = $validated['branch_ids'] ?? null;
        $menu->name = $validated['name'];
        $menu->description = $validated['description'] ?? null;
        $menu->is_active = (bool) ($validated['is_active'] ?? false);
    }

    private function rules($menuId = null): array
    {
        return [
            'branch_ids' => ['required', 'array'],
            'branch_ids.*' => ['exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('menus', 'name')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($menuId)
            ],
            'description' => [
                'required',
                'string',
                Rule::unique('menus', 'description')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($menuId)
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Menu name is required.',
            'name.unique' => 'Menu name already exists.',
            'branch_ids.required' => 'Please select at least one branch.',
            'description.required' => 'Menu description is required.',
            'description.unique' => 'Menu description already exists.',
        ];
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
