<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Menu/Brand/Index', [
            'brands' => Brand::query()
                ->where('tenant_id', $this->tenantId())
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order', 'is_active', 'created_at']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            $brand = Brand::create([
                'tenant_id' => $this->tenantId(),
                'name' => trim($validated['name']),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            return redirect()
                ->route('vendor.brands.index')
                ->with('success', "Brand \"{$brand->name}\" created successfully.");
        } catch (QueryException $ex) {
            Log::error('Brand store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to save brand.',
            ]);
        } catch (Exception $ex) {
            Log::error('Brand store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while creating the brand.',
            ]);
        }
    }

    public function update(Request $request, Brand $brand)
    {
        abort_unless((int) $brand->tenant_id === $this->tenantId(), 404);

        $validated = $request->validate($this->rules($brand->id), $this->messages());

        try {
            $brand->update([
                'name' => trim($validated['name']),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            return redirect()
                ->route('vendor.brands.index')
                ->with('success', "Brand \"{$brand->name}\" updated successfully.");
        } catch (QueryException $ex) {
            Log::error('Brand update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update brand.',
            ]);
        } catch (Exception $ex) {
            Log::error('Brand update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while updating the brand.',
            ]);
        }
    }

    public function destroy(Brand $brand)
    {
        abort_unless((int) $brand->tenant_id === $this->tenantId(), 404);

        try {
            $brand->delete();

            return redirect()
                ->route('vendor.brands.index')
                ->with('success', 'Brand deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Brand delete failed', [
                'message' => $ex->getMessage(),
                'brand_id' => $brand->id,
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete brand.',
            ]);
        }
    }

    private function rules(?int $brandId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))
                    ->ignore($brandId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Brand name is required.',
            'name.unique' => 'This brand already exists.',
        ];
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
