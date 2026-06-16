<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FoodCategory;
use App\Models\Menu;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $currentMenuId = $request->integer('menu_id');

        if (!$currentMenuId && $menus->isNotEmpty()) {
            $currentMenuId = (int) $menus->first()->id;
        }

        $categories = collect();

        if ($currentMenuId) {
            $categories = Category::query()
                ->with('media')
                ->where('tenant_id', $this->tenantId())
                ->where('menu_id', $currentMenuId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $selectedCategoryId = $request->integer('selected');
        if (!$selectedCategoryId && $categories->isNotEmpty()) {
            $selectedCategoryId = (int) $categories->first()->id;
        }

        return Inertia::render('VendorAdmin/Menu/Category/Index', [
            'menus' => $menus,
            'foodCategories' => $this->foodCategories(),
            'currentMenuId' => $currentMenuId,
            'tree' => $this->buildTree($categories),
            'selectedCategoryId' => $selectedCategoryId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $category = new Category();
            $category->tenant_id = $this->tenantId();

            $this->fillCategory($category, $validated);
            $category->save();

            if ($request->hasFile('logo')) {
                $category->addMedia($request->file('logo'))
                    ->toMediaCollection('Category_Images');
            }

            DB::commit();

            return redirect()
                ->route('vendor.categories.index', [
                    'menu_id' => $category->menu_id,
                    'selected' => $category->id,
                ])
                ->with('success', 'Category created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Category store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Unable to save category.',
                ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Category store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while creating the category.',
                ]);
        }
    }

    public function update(Request $request, $id)
    {
        $category = Category::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        $validated = $request->validate($this->rules($category->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillCategory($category, $validated);
            $category->save();

            if ($request->boolean('remove_logo')) {
                $category->clearMediaCollection('Category_Images');
            }

            if ($request->hasFile('logo')) {
                $category->clearMediaCollection('Category_Images');
                $category->addMedia($request->file('logo'))
                    ->toMediaCollection('Category_Images');
            }

            DB::commit();

            return redirect()
                ->route('vendor.categories.index', [
                    'menu_id' => $category->menu_id,
                    'selected' => $category->id,
                ])
                ->with('success', 'Category updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Category update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Unable to update category.',
                ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Category update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while updating the category.',
                ]);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::query()
                ->where('tenant_id', $this->tenantId())
                ->findOrFail($id);

            $hasChildren = Category::query()
                ->where('tenant_id', $this->tenantId())
                ->where('parent_id', $category->id)
                ->exists();

            if ($hasChildren) {
                return back()->withErrors([
                    'general' => 'Delete child categories first.',
                ]);
            }

            $menuId = $category->menu_id;
            $parentId = $category->parent_id;

            $category->clearMediaCollection('Category_Images');
            $category->delete();

            return redirect()
                ->route('vendor.categories.index', [
                    'menu_id' => $menuId,
                    'selected' => $parentId,
                ])
                ->with('success', 'Category deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Category delete failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete category.',
            ]);
        }
    }

    private function fillCategory(Category $category, array $validated): void
    {
        $category->menu_id = (int) $validated['menu_id'];
        $category->parent_id = $validated['parent_id'] ?? null;
        $category->food_category_id = $category->parent_id
            ? Category::query()
                ->where('tenant_id', $this->tenantId())
                ->whereKey($category->parent_id)
                ->value('food_category_id')
            : ($validated['food_category_id'] ?? null);
        $category->name = $validated['name'];
        $category->slug = Str::slug($validated['slug']);
        $category->sort_order = (int) ($validated['sort_order'] ?? 0);
        $category->is_active = (bool) ($validated['is_active'] ?? false);
    }

    private function rules($categoryId = null): array
    {
        return [
            'menu_id' => [
                'required',
                'integer',
                Rule::exists('menus', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())),
            ],
            'food_category_id' => [
                'nullable',
                Rule::requiredIf(fn () => blank(request('parent_id'))),
                'integer',
                Rule::exists('food_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('categories', 'name')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($categoryId),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))
                    ->ignore($categoryId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'menu_id.required' => 'Please select a menu.',
            'menu_id.exists' => 'Selected menu is invalid.',
            'food_category_id.required' => 'Please select a food type for root categories.',
            'name.required' => 'Category name is required.',
            'name.unique' => 'This name is already used.',
            'slug.required' => 'Slug is required.',
            'slug.unique' => 'This slug is already used.',
            'logo.image' => 'Logo must be an image.',
            'logo.mimes' => 'Logo must be JPG, JPEG, PNG, or WEBP.',
            'logo.max' => 'Logo may not be greater than 5MB.',
        ];
    }

    private function buildTree(Collection $items, $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->values()
            ->map(function ($item) use ($items) {
                return [
                    'id' => $item->id,
                    'menu_id' => $item->menu_id,
                    'parent_id' => $item->parent_id,
                    'food_category_id' => $item->food_category_id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'sort_order' => $item->sort_order,
                    'is_active' => (bool) $item->is_active,
                    'logo_url' => $item->logo_url,
                    'children' => $this->buildTree($items, $item->id),
                ];
            })
            ->all();
    }

    private function foodCategories()
    {
        return FoodCategory::query()
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
