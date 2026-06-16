<?php

namespace App\Services\Pms;

use App\Models\PmsIntegrationSetting;
use App\Models\PosInvoice;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class PmsRoomChargePoster
{
    public function post(PosInvoice $invoice, PmsIntegrationSetting $setting): void
    {
        $roomChargeAmount = (float) ($invoice->pms_room_charge_amount ?: $invoice->total);
        $invoiceTotal = max((float) $invoice->total, 0.0001);
        $ratio = min(1, max(0, $roomChargeAmount / $invoiceTotal));
        $tax = round((float) $invoice->tax_total * $ratio, 3);
        $subtotal = max(0, round($roomChargeAmount - $tax, 3));

        try {
            $response = (new PmsClient($setting))->postRoomCharge([
                'booking_id' => $invoice->pms_booking_id,
                'room_key_id' => $invoice->pms_room_key_id,
                'invoice_reference' => $invoice->invoice_no,
                'description' => 'POS invoice ' . $invoice->invoice_no,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'service_charge' => 0,
                'total_amount' => $roomChargeAmount,
            ]);

            $invoice->update([
                'pms_posting_status' => 'posted',
                'pms_posted_at' => now(),
                'pms_response' => $response,
            ]);
        } catch (RequestException $exception) {
            $body = $exception->response?->json() ?: $exception->response?->body();

            $invoice->update([
                'pms_posting_status' => 'failed',
                'pms_response' => [
                    'message' => $exception->getMessage(),
                    'response' => $body,
                ],
            ]);

            $this->logFailure($invoice, $exception);
        } catch (\Throwable $exception) {
            $invoice->update([
                'pms_posting_status' => 'failed',
                'pms_response' => [
                    'message' => $exception->getMessage(),
                ],
            ]);

            $this->logFailure($invoice, $exception);
        }
    }

    private function logFailure(PosInvoice $invoice, \Throwable $exception): void
    {
        Log::warning('PMS room charge posting failed', [
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
