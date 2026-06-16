<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\TenantCurrencySetting;
use App\Support\CurrencyRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;
use Illuminate\Support\Facades\Storage;

use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function currency()
    {
        $setting = TenantCurrencySetting::query()
            ->where('tenant_id', $this->tenantId())
            ->first();

        $defaultBranchCurrency = Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->whereNotNull('currency')
            ->value('currency');

        return Inertia::render('VendorAdmin/Settings/Currency', [
            'setting' => [
                'base_currency_code' => $setting?->base_currency_code ?: ($defaultBranchCurrency ?: 'LKR'),
                'secondary_currency_code' => $setting?->secondary_currency_code,
            ],
            'currencies' => CurrencyRegistry::all(),
        ]);
    }

    public function updateCurrency(Request $request)
    {
        $currencyCodes = collect(CurrencyRegistry::all())->pluck('code')->all();

        $validated = $request->validate([
            'base_currency_code' => ['required', 'string', Rule::in($currencyCodes)],
            'secondary_currency_code' => ['nullable', 'string', Rule::in($currencyCodes)],
        ], [
            'base_currency_code.required' => 'Base currency is required.',
            'base_currency_code.in' => 'Selected base currency is invalid.',
            'secondary_currency_code.in' => 'Selected secondary currency is invalid.',
        ]);

        if (
            filled($validated['secondary_currency_code'] ?? null) &&
            $validated['base_currency_code'] === $validated['secondary_currency_code']
        ) {
            return back()->withErrors([
                'secondary_currency_code' => 'Secondary currency must be different from base currency.',
            ]);
        }

        try {
            DB::beginTransaction();

            TenantCurrencySetting::query()->updateOrCreate(
                ['tenant_id' => $this->tenantId()],
                [
                    'base_currency_code' => $validated['base_currency_code'],
                    'secondary_currency_code' => $validated['secondary_currency_code'] ?? null,
                ]
            );

            DB::commit();

            return redirect()
                ->route('vendor.settings.currency')
                ->with('success', 'Currency settings updated successfully.');
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Tenant currency settings update failed', [
                'message' => $ex->getMessage(),
                'tenant_id' => $this->tenantId(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update currency settings.',
            ]);
        }
    }

    public function logo()
    {
        $tenant = Tenant::findOrFail($this->tenantId());

        return Inertia::render('VendorAdmin/Settings/Logo', [
            'setting' => [
                'brand_name' => $tenant->brand_name,

                'logo_url' => $tenant->getFirstMediaUrl('VendorLogo'),

                'favicon_url' => $tenant->getFirstMediaUrl('VendorFavicon'),

                'active' => (bool) $tenant->active,
            ],
        ]);
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:2048'],

            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ]);

        $tenant = Tenant::findOrFail($this->tenantId());

        try {
            if ($request->boolean('remove_logo')) {
                $tenant->clearMediaCollection('VendorLogo');
            }

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');

                $tenant->clearMediaCollection('VendorLogo');

                $tenant
                    ->addMedia($logo)
                    ->toMediaCollection('VendorLogo');
            }

            if ($request->boolean('remove_favicon')) {

                $tenant->clearMediaCollection('VendorFavicon');

                $tenant
                    ->addMedia(public_path('assets/images/default-favicon.png'))
                    ->preservingOriginal()
                    ->toMediaCollection('VendorFavicon');
            }

            if ($request->hasFile('favicon')) {
                $favicon = $request->file('favicon');

                $tenant->clearMediaCollection('VendorFavicon');

                $tenant
                    ->addMedia($favicon)
                    ->toMediaCollection('VendorFavicon');
            }

            $tenant->save();

            return back()->with(
                'success',
                'Brand assets updated successfully.'
            );

        } catch (\Throwable $e) {

            Log::error('Tenant brand asset update failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
