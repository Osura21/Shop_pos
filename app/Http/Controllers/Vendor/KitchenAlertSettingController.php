<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\KitchenAlertSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KitchenAlertSettingController extends Controller
{
    use ResolvesTenantContext;

    public function edit()
    {
        abort_unless(auth('vendor')->user()?->can('settings-kitchen-alert.view'), 403);

        $setting = $this->setting();

        return Inertia::render('VendorAdmin/Settings/KitchenAlert', [
            'setting' => [
                'sound_enabled' => $setting?->sound_enabled ?? true,
                'sound' => $setting?->sound ?: 'bell',
            ],
            'sounds' => KitchenAlertSetting::options(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user('vendor')?->can('settings-kitchen-alert.view'), 403);

        $validated = $request->validate([
            'sound_enabled' => ['nullable', 'boolean'],
            'sound' => ['nullable', 'string', Rule::in(array_keys(KitchenAlertSetting::SOUND_OPTIONS))],
        ]);

        KitchenAlertSetting::query()->updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            [
                'sound_enabled' => (bool) ($validated['sound_enabled'] ?? false),
                'sound' => $validated['sound'] ?? 'bell',
            ]
        );

        return redirect()
            ->route('vendor.settings.kitchen-alert')
            ->with('success', 'Kitchen alert sound settings updated successfully.');
    }

    private function setting(): ?KitchenAlertSetting
    {
        return KitchenAlertSetting::query()
            ->where('tenant_id', $this->tenantId())
            ->first();
    }
}
