<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\PmsIntegrationSetting;
use App\Models\PosInvoice;
use App\Models\PosKitchenTicket;
use App\Services\Pms\PmsClient;
use App\Services\Pms\PmsOrderPoster;
use App\Services\Pms\PmsRoomChargePoster;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PmsIntegrationController extends Controller
{
    use ResolvesTenantContext;

    public function edit()
    {
        $setting = $this->setting();

        return Inertia::render('VendorAdmin/Settings/PmsIntegration', [
            'setting' => [
                'property_id' => $setting?->property_id,
                'pms_base_url' => $setting?->pms_base_url,
                'pms_api_key' => $setting?->pms_api_key,
                'active' => (bool) ($setting?->active ?? false),
                'has_api_key' => filled($setting?->pms_api_key),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $setting = $this->setting();

        $validated = $request->validate([
            'property_id' => ['nullable', 'string', 'max:255'],
            'pms_base_url' => ['required', 'url', 'max:255'],
            'pms_api_key' => [$setting ? 'nullable' : 'required', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'vendor_id' => $this->tenantId(),
            'property_id' => $validated['property_id'] ?? null,
            'pms_base_url' => rtrim($validated['pms_base_url'], '/'),
            'active' => (bool) ($validated['active'] ?? false),
        ];

        if (filled($validated['pms_api_key'] ?? null)) {
            $payload['pms_api_key'] = $validated['pms_api_key'];
        }

        PmsIntegrationSetting::query()->updateOrCreate(
            ['vendor_id' => $this->tenantId()],
            $payload
        );

        return redirect()
            ->route('vendor.settings.pms')
            ->with('success', 'PMS integration settings updated successfully.');
    }

    public function customers()
    {
        return response()->json($this->client()->getCustomers());
    }

    public function rooms()
    {
        return response()->json($this->client()->getRooms());
    }

    public function checkedInGuests()
    {
        return response()->json($this->client()->getCheckedInGuests());
    }

    public function roomCharge(Request $request)
    {
        $payload = $request->validate([
            'booking_id' => ['required', 'string', 'max:255'],
            'room_key_id' => ['required', 'string', 'max:255'],
            'invoice_reference' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'subtotal' => ['required', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'service_charge' => ['nullable', 'numeric'],
            'total_amount' => ['required', 'numeric'],
        ]);

        return response()->json($this->client()->postRoomCharge($payload));
    }

    public function retryRoomCharge(PosInvoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === (int) $this->tenantId(), 404);

        if (!in_array($invoice->pms_posting_status, ['pending', 'failed'], true)) {
            return response()->json([
                'message' => 'Only pending or failed PMS room charges can be retried.',
            ], 422);
        }

        if (!filled($invoice->pms_booking_id) || !filled($invoice->pms_room_key_id)) {
            return response()->json([
                'message' => 'This invoice does not have PMS booking and room details.',
            ], 422);
        }

        app(PmsRoomChargePoster::class)->post($invoice, $this->clientSetting());

        return response()->json([
            'message' => $invoice->fresh()->pms_posting_status === 'posted'
                ? 'Room charge posted successfully.'
                : 'Room charge retry failed. The invoice remains saved.',
            'invoice' => $invoice->fresh(),
        ]);
    }

    public function retryOrder(PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

        if (!in_array($ticket->pms_posting_status, ['pending', 'failed'], true)) {
            return response()->json([
                'message' => 'Only pending or failed PMS orders can be retried.',
            ], 422);
        }

        if (!filled($ticket->pms_booking_id) || !filled($ticket->pms_room_key_id)) {
            return response()->json([
                'message' => 'This order does not have PMS booking and room details.',
            ], 422);
        }

        app(PmsOrderPoster::class)->post($ticket, $this->clientSetting());

        return response()->json([
            'message' => $ticket->fresh()->pms_posting_status === 'posted'
                ? 'PMS order synced successfully.'
                : 'PMS order sync failed. The order remains saved.',
            'order' => $ticket->fresh(),
        ]);
    }

    private function client(): PmsClient
    {
        return new PmsClient($this->clientSetting());
    }

    private function clientSetting(): PmsIntegrationSetting
    {
        $setting = $this->setting();

        if (!$setting || !$setting->active) {
            abort(response()->json([
                'message' => 'No active PMS integration is configured for this vendor.',
            ], 422));
        }

        return $setting;
    }

    private function setting(): ?PmsIntegrationSetting
    {
        return PmsIntegrationSetting::query()
            ->where('vendor_id', $this->tenantId())
            ->first();
    }
}
