<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use App\Models\SeoFooterLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SeoFooterLinkController extends Controller
{
    private const ORDER_TYPES = [
        'delivery' => 'Delivery',
        'pickup' => 'Pickup',
        'scheduled' => 'Scheduled',
    ];

    public function index()
    {
        return Inertia::render('SuperAdmin/SeoFooterLinks/Index');
    }

    public function getData(Request $request)
    {
        $search = trim((string) $request->input('search.value', ''));

        $query = SeoFooterLink::query()
            ->select(['id', 'country', 'country_code', 'location', 'link_text', 'food_type', 'order_type', 'sort_order', 'is_active', 'created_at'])
            ->orderBy('sort_order')
            ->orderBy('country')
            ->orderBy('location');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('country', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('link_text', 'like', "%{$search}%")
                    ->orWhere('food_type', 'like', "%{$search}%")
                    ->orWhere('order_type', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->editColumn('order_type', fn (SeoFooterLink $link) => self::ORDER_TYPES[$link->order_type] ?? $link->order_type)
            ->editColumn('is_active', fn (SeoFooterLink $link) => (bool) $link->is_active)
            ->editColumn('created_at', fn (SeoFooterLink $link) => optional($link->created_at)->format('Y-m-d H:i'))
            ->toJson();
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/SeoFooterLinks/CreateUpdate', [
            'seoFooterLink' => null,
            'orderTypeOptions' => $this->orderTypeOptions(),
            'foodTypeOptions' => $this->foodTypeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        SeoFooterLink::create($this->payload($data));
        $this->clearFooterCache();

        return redirect()
            ->route('seo-footer-links.index')
            ->with('success', 'SEO footer link created successfully.');
    }

    public function edit(SeoFooterLink $seoFooterLink)
    {
        return Inertia::render('SuperAdmin/SeoFooterLinks/CreateUpdate', [
            'seoFooterLink' => [
                'id' => $seoFooterLink->id,
                'country' => $seoFooterLink->country,
                'country_code' => $seoFooterLink->country_code,
                'location' => $seoFooterLink->location,
                'link_text' => $seoFooterLink->link_text,
                'food_type' => $seoFooterLink->food_type,
                'food_type_slug' => $seoFooterLink->food_type_slug,
                'order_type' => $seoFooterLink->order_type,
                'sort_order' => $seoFooterLink->sort_order,
                'is_active' => (bool) $seoFooterLink->is_active,
            ],
            'orderTypeOptions' => $this->orderTypeOptions(),
            'foodTypeOptions' => $this->foodTypeOptions(),
        ]);
    }

    public function update(Request $request, SeoFooterLink $seoFooterLink)
    {
        $data = $this->validated($request);

        $seoFooterLink->update($this->payload($data));
        $this->clearFooterCache();

        return redirect()
            ->route('seo-footer-links.index')
            ->with('success', 'SEO footer link updated successfully.');
    }

    public function destroy(SeoFooterLink $seoFooterLink)
    {
        $seoFooterLink->delete();
        $this->clearFooterCache();

        return redirect()
            ->route('seo-footer-links.index')
            ->with('success', 'SEO footer link deleted successfully.');
    }

    public function locations(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:80'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ]);

        $apiKey = config('services.google.maps_api_key');

        if (! $apiKey) {
            return response()->json(['predictions' => []]);
        }

        $autocompleteUrl = (string) config('services.google.maps_autocomplete_url');

        if (str_contains($autocompleteUrl, '${query}')) {
            $url = str_replace(
                ['${query}', '${GOOGLE_MAPS_API_KEY}'],
                [rawurlencode($data['query']), $apiKey],
                $autocompleteUrl
            );

            if (! empty($data['country_code'])) {
                $url = preg_replace(
                    '/components=country:[a-z]{2}/i',
                    'components=country:' . strtoupper($data['country_code']),
                    $url
                );
            }

            $response = Http::timeout(6)->get($url);
        } else {
            $response = Http::timeout(6)->get($autocompleteUrl, [
                'input' => $data['query'],
                'components' => ! empty($data['country_code'])
                    ? 'country:' . strtolower($data['country_code'])
                    : null,
                'types' => '(cities)',
                'key' => $apiKey,
            ]);
        }

        if (! $response->ok()) {
            return response()->json(['predictions' => []]);
        }

        return response()->json([
            'predictions' => collect($response->json('predictions', []))
                ->map(fn (array $item) => [
                    'description' => $item['description'] ?? '',
                    'main_text' => data_get($item, 'structured_formatting.main_text', $item['description'] ?? ''),
                ])
                ->filter(fn (array $item) => $item['description'] !== '')
                ->values(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'country' => ['required', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'location' => ['required', 'string', 'max:120'],
            'link_text' => ['nullable', 'string', 'max:160'],
            'food_type' => ['required', 'string', 'max:120'],
            'food_type_slug' => ['nullable', 'string', 'max:140'],
            'order_type' => ['required', Rule::in(array_keys(self::ORDER_TYPES))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'country' => $this->cleanCountryName($data['country']),
            'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
            'location' => $data['location'],
            'link_text' => $data['link_text'] ?? null,
            'food_type' => $data['food_type'],
            'food_type_slug' => Str::slug(($data['food_type_slug'] ?? '') ?: $data['food_type']),
            'order_type' => $data['order_type'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function orderTypeOptions(): array
    {
        return collect(self::ORDER_TYPES)
            ->map(fn (string $label, string $value) => [
                'id' => $value,
                'name' => $label,
            ])
            ->values()
            ->all();
    }

    private function foodTypeOptions()
    {
        return FoodCategory::query()
            ->select(['id', 'name', 'slug', 'sort_order'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FoodCategory $foodCategory) => [
                'id' => $foodCategory->slug ?: Str::slug($foodCategory->name),
                'name' => $foodCategory->name,
                'slug' => $foodCategory->slug ?: Str::slug($foodCategory->name),
            ])
            ->values();
    }

    private function clearFooterCache(): void
    {
        Cache::forget('multivendor.seo_footer_sections');
    }

    private function cleanCountryName(?string $country): ?string
    {
        $country = trim((string) $country);

        if ($country === '') {
            return null;
        }

        return trim(preg_replace('/\s*\([^)]*\)\s*/', '', $country));
    }
}
