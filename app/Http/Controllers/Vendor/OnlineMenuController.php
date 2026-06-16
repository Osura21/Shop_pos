<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Menu;
use App\Models\OnlineMenu;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class OnlineMenuController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Menu/OnlineMenu/Index');
    }

    public function getData(Request $request)
    {

        $allBranches = Branch::where('tenant_id', $this->tenantId())
            ->pluck('name', 'id');

        $onlineMenus = OnlineMenu::query()
            ->with([
                'menu:id,name',
            ])
            ->where('tenant_id', $this->tenantId())
            ->select('online_menus.*');

        return DataTables::of($onlineMenus)

            ->addColumn('branch_name', function ($row) use ($allBranches) {
                if (!$row->branch_ids)
                    return '-';

                $names = collect($row->branch_ids)
                    ->map(fn($id) => $allBranches[$id] ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                return count($names) ? implode(', ', $names) : '-';
            })

            ->addColumn('menu_name', function ($row) {
                return $row->menu?->name ?? '-';
            })

            ->addColumn('status', function ($row) {
                return (int) $row->is_active;
            })

            ->rawColumns(['status'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Menu/OnlineMenu/CreateUpdate', [
            'onlineMenu' => null,
            'branches' => $this->branches(),
            'menus' => $this->menus(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $onlineMenu = new OnlineMenu();
            $onlineMenu->tenant_id = $this->tenantId();
            $this->fillOnlineMenu($onlineMenu, $validated);
            $onlineMenu->save();

            DB::commit();

            return redirect()
                ->route('vendor.online-menus.index')
                ->with('success', 'Online menu created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Online menu store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to save online menu.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Online menu store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Something went wrong while creating the online menu.',
            ]);
        }
    }

    public function edit($id)
    {
        $onlineMenu = OnlineMenu::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Menu/OnlineMenu/CreateUpdate', [
            'onlineMenu' => $onlineMenu,
            'branches' => $this->branches(),
            'menus' => $this->menus(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $onlineMenu = OnlineMenu::where('tenant_id', $this->tenantId())->findOrFail($id);

        $validated = $request->validate($this->rules($onlineMenu->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillOnlineMenu($onlineMenu, $validated);
            $onlineMenu->save();

            DB::commit();

            return redirect()
                ->route('vendor.online-menus.index')
                ->with('success', 'Online menu updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Online menu update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update online menu.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Online menu update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Something went wrong while updating the online menu.',
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $onlineMenu = OnlineMenu::where('tenant_id', $this->tenantId())->findOrFail($id);
            $onlineMenu->delete();

            return redirect()
                ->route('vendor.online-menus.index')
                ->with('success', 'Online menu deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Online menu delete failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete online menu.',
            ]);
        }
    }

    private function fillOnlineMenu(OnlineMenu $onlineMenu, array $validated): void
    {
        $onlineMenu->branch_ids = $validated['branch_ids'] ?? null;
        $onlineMenu->menu_id = $validated['menu_id'];
        $onlineMenu->name = $validated['name'];
        $onlineMenu->slug = $validated['slug'];
        $onlineMenu->is_active = (bool) ($validated['is_active'] ?? false);
    }

    private function rules($onlineMenuId = null): array
    {
        return [
            'branch_ids' => ['required', 'array'],
            'branch_ids.*' => ['exists:branches,id'],
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('online_menus', 'name')
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at')
                    ->ignore($onlineMenuId),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('online_menus', 'slug')
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at')
                    ->ignore($onlineMenuId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Online menu name is required.',
            'name.unique' => 'This name is already used.',
            'slug.required' => 'Slug is required.',
            'slug.unique' => 'This slug is already used.',
            'menu_id.required' => 'Please select a menu.',
            'branch_ids.required' => 'Please select at least one branch.',
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

    private function menus()
    {
        return Menu::query()
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