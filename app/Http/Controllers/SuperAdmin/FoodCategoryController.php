<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class FoodCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/FoodCategories/Index');
    }

    public function getData(Request $request)
    {
        $search = trim((string) $request->input('search.value', ''));

        $query = FoodCategory::query()
            ->with('media')
            ->select(['id', 'name', 'slug', 'sort_order', 'is_active', 'created_at'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('image_url', fn (FoodCategory $category) => $category->image_url)
            ->editColumn('is_active', fn (FoodCategory $category) => (bool) $category->is_active)
            ->editColumn('created_at', fn (FoodCategory $category) => optional($category->created_at)->format('Y-m-d H:i'))
            ->toJson();
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/FoodCategories/CreateUpdate', [
            'foodCategory' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($request, $data) {
            $foodCategory = FoodCategory::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['slug']),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            if ($request->hasFile('image')) {
                $foodCategory->addMedia($request->file('image'))
                    ->toMediaCollection('FoodCategoryImage');
            }
        });

        return redirect()
            ->route('food-categories.index')
            ->with('success', 'Food type created successfully.');
    }

    public function edit(FoodCategory $foodCategory)
    {
        $foodCategory->load('media');

        return Inertia::render('SuperAdmin/FoodCategories/CreateUpdate', [
            'foodCategory' => [
                'id' => $foodCategory->id,
                'name' => $foodCategory->name,
                'slug' => $foodCategory->slug,
                'sort_order' => $foodCategory->sort_order,
                'is_active' => (bool) $foodCategory->is_active,
                'image_url' => $foodCategory->image_url,
            ],
        ]);
    }

    public function update(Request $request, FoodCategory $foodCategory)
    {
        $data = $request->validate($this->rules($foodCategory->id), $this->messages());

        DB::transaction(function () use ($request, $data, $foodCategory) {
            $foodCategory->update([
                'name' => $data['name'],
                'slug' => Str::slug($data['slug']),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            if ($request->boolean('remove_image')) {
                $foodCategory->clearMediaCollection('FoodCategoryImage');
            }

            if ($request->hasFile('image')) {
                $foodCategory->clearMediaCollection('FoodCategoryImage');
                $foodCategory->addMedia($request->file('image'))
                    ->toMediaCollection('FoodCategoryImage');
            }
        });

        return redirect()
            ->route('food-categories.index')
            ->with('success', 'Food type updated successfully.');
    }

    public function destroy(FoodCategory $foodCategory)
    {
        $foodCategory->clearMediaCollection('FoodCategoryImage');
        $foodCategory->delete();

        return redirect()
            ->route('food-categories.index')
            ->with('success', 'Food type deleted successfully.');
    }

    private function rules(?int $foodCategoryId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('food_categories', 'slug')->ignore($foodCategoryId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Food type name is required.',
            'slug.required' => 'Slug is required.',
            'slug.unique' => 'This slug is already used.',
            'image.image' => 'Image must be an image.',
            'image.mimes' => 'Image must be JPG, JPEG, PNG, or WEBP.',
            'image.max' => 'Image may not be greater than 5MB.',
        ];
    }
}
