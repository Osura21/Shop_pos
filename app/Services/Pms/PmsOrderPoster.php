<?php

namespace App\Services\Pms;

use App\Models\PmsIntegrationSetting;
use App\Models\PosKitchenTicket;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class PmsOrderPoster
{
    public function post(PosKitchenTicket $ticket, PmsIntegrationSetting $setting): void
    {
        try {
            $payload = $this->buildPayload($ticket, $setting);

            $response = (new PmsClient($setting))->postOrder($payload);

            $ticket->update([
                'pms_posting_status' => 'posted',
                'pms_posted_at' => now(),
                'pms_response' => $response,
            ]);
        } catch (RequestException $exception) {
            $body = $exception->response?->json() ?: $exception->response?->body();

            $ticket->update([
                'pms_posting_status' => 'failed',
                'pms_response' => [
                    'message' => $exception->getMessage(),
                    'response' => $body,
                ],
            ]);

            $this->logFailure($ticket, $exception);
        } catch (\Throwable $exception) {
            $ticket->update([
                'pms_posting_status' => 'failed',
                'pms_response' => [
                    'message' => $exception->getMessage(),
                ],
            ]);

            $this->logFailure($ticket, $exception);
        }
    }

    private function buildPayload(PosKitchenTicket $ticket, PmsIntegrationSetting $setting): array
    {
        $ticket->load(['items.options', 'customer', 'register.branch', 'table']);

        $guest = $ticket->pms_guest_snapshot ?: [];
        $paidAmount = (float) ($ticket->paid_amount ?? 0);
        $grandTotal = (float) ($ticket->grand_total ?? 0);

        return [
            'property_id' => $setting->property_id,
            'booking_id' => $ticket->pms_booking_id ?: ($guest['booking_id'] ?? null),
            'booking_reference' => $guest['booking_reference'] ?? null,
            'room_key_id' => $ticket->pms_room_key_id ?: ($guest['room_key_id'] ?? null),
            'room_no' => $guest['room_no'] ?? null,
            'room_name' => $guest['room_name'] ?? null,
            'order_reference' => 'POS-' . $ticket->id,
            'pos_order_id' => $ticket->id,
            'pos_order_uuid' => $ticket->uuid,
            'channel' => $ticket->channel,
            'status' => $ticket->status,
            'customer' => [
                'id' => $ticket->customer_id ?: ($guest['customer_id'] ?? null),
                'name' => $ticket->customer_name ?: ($guest['guest_name'] ?? null),
                'phone' => $ticket->customer?->phone ?? $ticket->customer?->phone_number,
            ],
            'guest' => [
                'name' => $guest['guest_name'] ?? $ticket->customer_name,
                'customer_id' => $guest['customer_id'] ?? null,
            ],
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'paid_amount' => $paidAmount,
            'due_amount' => max(0, $grandTotal - $paidAmount),
            'currency_code' => $ticket->currency_code ?: 'LKR',
            'total_amount' => $grandTotal,
            'subtotal' => (float) ($ticket->subtotal ?? 0),
            'tax_total' => (float) ($ticket->tax_total ?? 0),
            'discount_total' => (float) ($ticket->discount_total ?? 0),
            'branch' => [
                'id' => $ticket->branch_id,
                'name' => $ticket->register?->branch?->name,
            ],
            'table' => [
                'id' => $ticket->dining_table_id,
                'name' => $ticket->table?->name,
            ],
            'items' => $ticket->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'option_total' => (float) $item->option_total,
                    'subtotal' => (float) $item->line_subtotal,
                    'tax_total' => (float) $item->tax_total,
                    'line_total' => (float) $item->line_total,
                    'options' => $item->options->map(function ($option) {
                        return [
                            'option_name' => $option->option_name,
                            'value' => $option->value_label ?? $option->value_input,
                            'price' => (float) $option->price,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'notes' => $ticket->notes,
            'sent_to_kitchen_at' => optional($ticket->sent_to_kitchen_at)->toISOString(),
            'created_at' => optional($ticket->created_at)->toISOString(),
            'updated_at' => optional($ticket->updated_at)->toISOString(),
        ];
    }

    private function logFailure(PosKitchenTicket $ticket, \Throwable $exception): void
    {
        Log::warning('PMS order posting failed', [
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
