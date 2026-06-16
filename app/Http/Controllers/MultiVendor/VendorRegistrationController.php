<?php

namespace App\Http\Controllers\MultiVendor;

use App\Mail\VendorRequestStatusMail;
use App\Models\FoodCategory;
use App\Models\RequestedVendor;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VendorRegistrationController
{
    private const RESTAURANT_PAGE_IMAGE_DEFAULTS = [
        'hero_main' => 'multivendor/product.webp',
        'hero_deal' => 'multivendor/stock.webp',
        'hero_pizza' => 'multivendor/post-ad2.webp',
        'middle_banner_top' => 'multivendor/contact.webp',
        'middle_banner_bottom' => 'multivendor/aboutbanner.webp',
        'middle_banner_large' => 'multivendor/business-auth-image.avif',
    ];

    public function index(Request $request)
    {
        $existingRequest = null;

        if (Schema::hasTable('requested_vendor') && Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();

            $existingRequest = RequestedVendor::query()
                ->where('email', $customer->email)
                ->latest()
                ->first();
        }

        return Inertia::render('MultiVendor/VendorRegister/Index', [
            'authUser' => Auth::guard('customer')->user(),
            'existingRequest' => $existingRequest ? $this->transform($existingRequest) : null,
            'submittedRequest' => $request->session()->get('submitted_vendor_request'),
            'foodTypes' => $this->foodTypeOptions(),
            'restaurantPageImageDefaults' => $this->restaurantPageImageDefaults(),
        ]);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+947\d{8}$/'],
        ], [
            'phone.regex' => 'Please enter a valid Sri Lankan mobile number.',
        ]);

        $otp = (string) random_int(100000, 999999);
        $message = "Your Sappy Eats seller verification code is {$otp}. It expires in 10 minutes.";

        if (! app(SmsService::class)->send($data['phone'], $message)) {
            return response()->json([
                'message' => 'Unable to send OTP. Please try again.',
            ], 422);
        }

        $request->session()->put('seller_register_otp', [
            'phone' => $data['phone'],
            'otp' => $otp,
            'verified' => false,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return response()->json([
            'message' => 'OTP sent successfully.',
            'otp' => app()->environment('local') ? $otp : null,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+947\d{8}$/'],
            'otp' => ['required', 'digits:6'],
        ], [
            'phone.regex' => 'Please enter a valid Sri Lankan mobile number.',
        ]);

        $otpData = $request->session()->get('seller_register_otp');

        if (
            ! $otpData ||
            ($otpData['phone'] ?? null) !== $data['phone'] ||
            ($otpData['otp'] ?? null) !== $data['otp'] ||
            (int) ($otpData['expires_at'] ?? 0) < now()->timestamp
        ) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $otpData['verified'] = true;
        $request->session()->put('seller_register_otp', $otpData);

        return response()->json(['message' => 'Phone number verified.']);
    }

    public function locations(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ]);

        $apiKey = config('services.google.maps_api_key');

        if (! $apiKey) {
            return response()->json(['predictions' => []]);
        }

        $response = $this->googlePlacesAutocomplete($data['query'], $data['country_code'] ?? null, $apiKey);

        if (! $response->ok()) {
            return response()->json(['predictions' => []]);
        }

        return response()->json([
            'predictions' => collect($response->json('predictions', []))
                ->map(fn (array $item) => [
                    'place_id' => $item['place_id'] ?? '',
                    'description' => $item['description'] ?? '',
                    'main_text' => data_get($item, 'structured_formatting.main_text', $item['description'] ?? ''),
                    'secondary_text' => data_get($item, 'structured_formatting.secondary_text', ''),
                ])
                ->filter(fn (array $item) => $item['place_id'] !== '' && $item['description'] !== '')
                ->values(),
        ]);
    }

    public function locationDetails(Request $request)
    {
        $data = $request->validate([
            'place_id' => ['required', 'string', 'max:255'],
        ]);

        $apiKey = config('services.google.maps_api_key');

        if (! $apiKey) {
            return response()->json(['location' => null]);
        }

        $response = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $data['place_id'],
            'fields' => 'place_id,formatted_address,address_components',
            'key' => $apiKey,
        ]);

        if (! $response->ok() || $response->json('status') !== 'OK') {
            return response()->json(['location' => null]);
        }

        return response()->json([
            'location' => $this->googleLocationPayload($response->json('result', [])),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+947\d{8}$/'],
            'legal_business_name' => ['required', 'string', 'max:255'],
            'store_display_name' => ['required', 'string', 'max:255'],
            'business_address' => ['required', 'string', 'max:5000'],
            'business_email' => ['required', 'email', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'business_registration_number' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'search_location' => ['nullable', 'string', 'max:255'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'food_types' => ['required', 'array', 'min:1'],
            'food_types.*' => ['string', 'max:255'],
            'opening_from' => ['required', 'date_format:H:i'],
            'opening_to' => ['required', 'date_format:H:i'],
            'service_options' => ['required', 'array', 'min:1'],
            'service_options.*' => ['string', 'max:255'],
            'terms_accepted' => ['accepted'],
            'business_logo' => ['nullable', 'image', 'max:2048'],
            'business_photos' => ['nullable', 'array'],
            'business_photos.*' => ['image', 'max:4096'],
            'restaurant_page_images' => ['nullable', 'array'],
            'restaurant_page_images.*' => ['nullable', 'image', 'max:4096'],
            'restaurant_page_image_defaults' => ['nullable', 'array'],
            'restaurant_page_image_defaults.*' => ['nullable', 'string', 'max:255'],
            'business_license' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [
            'phone.regex' => 'Please enter a valid Sri Lankan mobile number.',
        ]);

        $otpData = $request->session()->get('seller_register_otp');

        if (! ($otpData['verified'] ?? false) || ($otpData['phone'] ?? null) !== $data['phone']) {
            return back()->withErrors([
                'phone' => 'Please verify your phone number before submitting.',
            ])->withInput();
        }

        if (! Schema::hasTable('requested_vendor')) {
            return back()->withErrors([
                'name' => 'Seller request table is not available. Please run the project migrations.',
            ])->withInput();
        }

        $logoPath = $request->file('business_logo')?->store('vendor-requests/logos', 'public');
        $licensePath = $request->file('business_license')?->store('vendor-requests/licenses', 'public');
        $photoPaths = [];

        foreach ($request->file('business_photos', []) as $photo) {
            $photoPaths[] = $photo->store('vendor-requests/photos', 'public');
        }

        $restaurantPageImagePaths = $this->storeRestaurantPageImages($request);

        $requestRecord = RequestedVendor::create([
            'name' => $data['name'],
            'admin_name' => $data['admin_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'legal_business_name' => $data['legal_business_name'],
            'store_display_name' => $data['store_display_name'],
            'business_address' => $data['business_address'],
            'business_email' => $data['business_email'],
            'owner_name' => $data['owner_name'],
            'business_registration_number' => $data['business_registration_number'] ?? null,
            'city' => $data['city'],
            'country' => $data['country'],
            'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
            'search_location' => $data['search_location'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'address_line_1' => $data['address_line_1'] ?? null,
            'address_line_2' => $data['address_line_2'] ?? null,
            'state_province' => $data['state_province'] ?? null,
            'food_types' => $data['food_types'],
            'opening_from' => $data['opening_from'],
            'opening_to' => $data['opening_to'],
            'service_options' => $data['service_options'],
            'terms_accepted' => true,
            'business_logo_path' => $logoPath,
            'business_photo_paths' => $photoPaths,
            'restaurant_page_image_paths' => $restaurantPageImagePaths,
            'business_license_path' => $licensePath,
            'status' => 'pending',
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        $request->session()->forget('seller_register_otp');
        $request->session()->flash('submitted_vendor_request', $this->transform($requestRecord));

        $this->sendRequestNotifications($requestRecord);

        return redirect()->route('seller.register.status', $requestRecord->slug);
    }

    public function status(string $slug)
    {
        abort_unless(Schema::hasTable('requested_vendor'), 404);

        $requestRecord = RequestedVendor::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('MultiVendor/VendorRegister/Index', [
            'authUser' => Auth::guard('customer')->user(),
            'existingRequest' => null,
            'submittedRequest' => $this->transform($requestRecord),
            'foodTypes' => $this->foodTypeOptions(),
            'restaurantPageImageDefaults' => $this->restaurantPageImageDefaults(),
        ]);
    }

    protected function sendRequestNotifications(RequestedVendor $requestRecord): void
    {
        try {
            Mail::to($requestRecord->email)->send(
                new VendorRequestStatusMail(
                    $requestRecord,
                    route('seller.register.status', $requestRecord->slug)
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send seller request confirmation email.', [
                'vendor_request_id' => $requestRecord->id,
                'email' => $requestRecord->email,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            Mail::raw(
                "New vendor request received.\n\n" .
                "Shop Name: {$requestRecord->name}\n" .
                "Admin Name: {$requestRecord->admin_name}\n" .
                "Email: {$requestRecord->email}\n" .
                "Phone: {$requestRecord->phone}\n" .
                "City: {$requestRecord->city}\n" .
                "Food Types: " . implode(', ', $requestRecord->food_types ?? []) . "\n" .
                "Status: {$requestRecord->status}\n" .
                "Request Code: {$requestRecord->slug}\n",
                function ($message) use ($requestRecord) {
                    $message->to('osura@weblook.com')
                        ->subject('New Vendor Request - ' . $requestRecord->name);
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send new seller request admin email.', [
                'vendor_request_id' => $requestRecord->id,
                'email' => 'osura@weblook.com',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'seller-request';
        $slug = $base;
        $count = 1;

        while (RequestedVendor::where('slug', $slug)->exists()) {
            $count++;
            $slug = "{$base}-{$count}";
        }

        return $slug;
    }

    protected function transform(RequestedVendor $requestRecord): array
    {
        return [
            'name' => $requestRecord->name,
            'admin_name' => $requestRecord->admin_name,
            'email' => $requestRecord->email,
            'phone' => $requestRecord->phone,
            'legal_business_name' => $requestRecord->legal_business_name,
            'store_display_name' => $requestRecord->store_display_name,
            'business_address' => $requestRecord->business_address,
            'business_email' => $requestRecord->business_email,
            'owner_name' => $requestRecord->owner_name,
            'business_registration_number' => $requestRecord->business_registration_number,
            'city' => $requestRecord->city,
            'country' => $requestRecord->country,
            'country_code' => $requestRecord->country_code,
            'search_location' => $requestRecord->search_location,
            'google_place_id' => $requestRecord->google_place_id,
            'postal_code' => $requestRecord->postal_code,
            'address_line_1' => $requestRecord->address_line_1,
            'address_line_2' => $requestRecord->address_line_2,
            'state_province' => $requestRecord->state_province,
            'food_types' => $requestRecord->food_types ?? [],
            'opening_from' => $requestRecord->opening_from,
            'opening_to' => $requestRecord->opening_to,
            'service_options' => $requestRecord->service_options ?? [],
            'terms_accepted' => $requestRecord->terms_accepted,
            'business_logo_url' => $requestRecord->business_logo_path ? Storage::disk('public')->url($requestRecord->business_logo_path) : null,
            'business_photo_urls' => collect($requestRecord->business_photo_paths ?? [])
                ->map(fn ($path) => Storage::disk('public')->url($path))
                ->values()
                ->all(),
            'restaurant_page_image_urls' => $this->restaurantPageImageUrls($requestRecord->restaurant_page_image_paths ?? []),
            'business_license_url' => $requestRecord->business_license_path ? Storage::disk('public')->url($requestRecord->business_license_path) : null,
            'status' => $requestRecord->status,
            'slug' => $requestRecord->slug,
            'reason' => $requestRecord->reason,
            'created_at' => optional($requestRecord->created_at)->format('Y-m-d H:i'),
            'status_url' => route('seller.register.status', $requestRecord->slug),
        ];
    }

    protected function foodTypeOptions(): array
    {
        if (! Schema::hasTable('food_categories')) {
            return [];
        }

        return FoodCategory::query()
            ->select(['name', 'slug', 'sort_order', 'is_active'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FoodCategory $category) => [
                'name' => $category->name,
                'slug' => $category->slug ?: Str::slug($category->name),
            ])
            ->values()
            ->all();
    }

    protected function restaurantPageImageDefaults(): array
    {
        return collect(self::RESTAURANT_PAGE_IMAGE_DEFAULTS)
            ->map(fn (string $path) => asset($path))
            ->all();
    }

    protected function storeRestaurantPageImages(Request $request): array
    {
        $stored = [];
        $defaultValues = $request->input('restaurant_page_image_defaults', []);

        foreach (array_keys(self::RESTAURANT_PAGE_IMAGE_DEFAULTS) as $key) {
            $file = $request->file("restaurant_page_images.{$key}");

            if ($file) {
                $stored[$key] = $file->store('vendor-requests/restaurant-page', 'public');
                continue;
            }

            $defaultPath = $defaultValues[$key] ?? self::RESTAURANT_PAGE_IMAGE_DEFAULTS[$key];
            $stored[$key] = Str::startsWith($defaultPath, ['http://', 'https://'])
                ? $defaultPath
                : ltrim(str_replace(asset(''), '', $defaultPath), '/');
        }

        return $stored;
    }

    protected function restaurantPageImageUrls(array $paths): array
    {
        return collect(self::RESTAURANT_PAGE_IMAGE_DEFAULTS)
            ->mapWithKeys(function (string $defaultPath, string $key) use ($paths) {
                $path = $paths[$key] ?? $defaultPath;
                $url = Str::startsWith($path, ['http://', 'https://'])
                    ? $path
                    : (Str::startsWith($path, ['vendor-requests/'])
                        ? Storage::disk('public')->url($path)
                        : asset(ltrim($path, '/')));

                return [$key => $url];
            })
            ->all();
    }

    private function googlePlacesAutocomplete(string $query, ?string $countryCode, string $apiKey)
    {
        $autocompleteUrl = (string) config('services.google.maps_autocomplete_url');

        if (str_contains($autocompleteUrl, '${query}')) {
            $url = str_replace(
                ['${query}', '${GOOGLE_MAPS_API_KEY}'],
                [rawurlencode($query), $apiKey],
                $autocompleteUrl
            );

            if ($countryCode) {
                $url = preg_replace(
                    '/components=country:[a-z]{2}/i',
                    'components=country:' . strtoupper($countryCode),
                    $url
                );
            }

            return Http::timeout(6)->get($url);
        }

        return Http::timeout(6)->get($autocompleteUrl, [
            'input' => $query,
            'components' => $countryCode ? 'country:' . strtolower($countryCode) : null,
            'key' => $apiKey,
        ]);
    }

    private function googleLocationPayload(array $result): array
    {
        $component = function (string $type, string $key = 'long_name') use ($result): string {
            foreach ($result['address_components'] ?? [] as $component) {
                if (in_array($type, $component['types'] ?? [], true)) {
                    return (string) ($component[$key] ?? '');
                }
            }

            return '';
        };

        $streetNumber = $component('street_number');
        $route = $component('route');
        $addressLine1 = trim(collect([$streetNumber, $route])->filter()->join(' '));

        if ($addressLine1 === '') {
            $addressLine1 = $component('premise') ?: $component('point_of_interest') ?: $component('establishment');
        }

        $city = $component('locality')
            ?: $component('postal_town')
            ?: $component('administrative_area_level_2')
            ?: $component('sublocality');

        return [
            'google_place_id' => $result['place_id'] ?? '',
            'search_location' => $result['formatted_address'] ?? '',
            'business_address' => $result['formatted_address'] ?? '',
            'address_line_1' => $addressLine1,
            'address_line_2' => $component('sublocality') ?: $component('neighborhood'),
            'city' => $city,
            'state_province' => $component('administrative_area_level_1'),
            'postal_code' => $component('postal_code'),
            'country' => $this->cleanCountryName($component('country')),
            'country_code' => strtoupper($component('country', 'short_name')),
        ];
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
