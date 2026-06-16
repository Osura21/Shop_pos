<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\PmsIntegrationSetting;
use App\Models\PosKitchenTicket;
use App\Models\PosRegister;
use App\Models\SaleReason;
use App\Services\Pms\PmsClient;
use App\Services\Pms\PmsOrderPoster;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SalesOrderController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        $registers = PosRegister::query()
            ->with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })
            ->orderBy('name')
            ->get()
            ->map(function ($register) {
                return [
                    'id' => $register->id,
                    'name' => $register->name ?: 'Register #' . $register->id,
                    'branch_name' => optional($register->branch)->name ?: '-',
                ];
            })
            ->values();

        $cancelReasons = SaleReason::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'cancel')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('VendorAdmin/Sales/Orders/Index', [
            'registers' => $registers,
            'cancelReasons' => $cancelReasons,
        ]);
    }

    public function getData(Request $request)
    {
        $table = (new PosKitchenTicket())->getTable();

        $orders = PosKitchenTicket::query()
            ->with(['register.branch', 'customer'])
            ->where("{$table}.tenant_id", $this->tenantId())
            ->select("{$table}.*");

        return DataTables::of($orders)
            ->filter(function ($query) use ($request, $table) {
                $search = trim((string) $request->input('search.value', ''));

                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search, $table) {
                    $q->where("{$table}.id", 'like', "%{$search}%")
                        ->orWhere("{$table}.uuid", 'like', "%{$search}%")
                        ->orWhere("{$table}.customer_name", 'like', "%{$search}%")
                        ->orWhere("{$table}.status", 'like', "%{$search}%")
                        ->orWhere("{$table}.payment_status", 'like', "%{$search}%")
                        ->orWhere("{$table}.channel", 'like', "%{$search}%")
                        ->orWhere("{$table}.currency_code", 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('register', function ($registerQuery) use ($search) {
                            $registerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhereHas('branch', function ($branchQuery) use ($search) {
                                    $branchQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->addColumn('reference_no', function ($row) {
                return $this->referenceNo($row);
            })
            ->addColumn('customer_display', function ($row) {
                return $this->customerName($row);
            })
            ->addColumn('branch_name', function ($row) {
                return optional(optional($row->register)->branch)->name ?: '-';
            })
            ->addColumn('register_name', function ($row) {
                return optional($row->register)->name ?: '-';
            })
            ->addColumn('type_label', function ($row) {
                return $this->pretty($row->channel ?: 'pos');
            })
            ->addColumn('type_badge', function ($row) {
                return $this->badge($this->pretty($row->channel ?: 'pos'), 'orange');
            })
            ->addColumn('status_label', function ($row) {
                return $this->pretty($row->status ?: 'pending');
            })
            ->addColumn('status_badge', function ($row) {
                return $this->badge(
                    $this->pretty($row->status ?: 'pending'),
                    $this->statusVariant($row->status)
                );
            })
            ->addColumn('payment_label', function ($row) {
                return $this->pretty($row->payment_status ?: 'unpaid');
            })
            ->addColumn('payment_badge', function ($row) {
                return $this->badge(
                    $this->pretty($row->payment_status ?: 'unpaid'),
                    $this->paymentVariant($row->payment_status)
                );
            })
            ->addColumn('total_display', function ($row) {
                return $this->currencySymbol($row->currency_code ?: 'LKR') . ' ' . $this->money($row->grand_total);
            })
            ->addColumn('can_cancel', function ($row) {
                return !in_array((string) $row->status, ['cancelled', 'canceled'], true);
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->rawColumns(['type_badge', 'status_badge', 'payment_badge'])
            ->make(true);
    }

    public function syncPmsPaymentStatuses(Request $request)
    {
        $setting = PmsIntegrationSetting::query()
            ->where('vendor_id', $this->tenantId())
            ->where('active', true)
            ->first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'No active PMS integration is configured for this vendor.',
                'synced' => 0,
            ]);
        }

        $limit = min(max((int) $request->input('limit', 15), 1), 50);
        $client = new PmsClient($setting);
        $synced = 0;
        $checked = 0;
        $failed = 0;
        $eventOrder = (array) $request->input('order', []);
        $orderReference = $request->input('order_reference') ?: data_get($eventOrder, 'order_reference');
        $posOrderUuid = $request->input('pos_order_uuid') ?: data_get($eventOrder, 'pos_order_uuid');
        $orderId = $request->input('order_id') ?: data_get($eventOrder, 'id');

        $specificTicketQuery = PosKitchenTicket::query()
            ->where('tenant_id', $this->tenantId())
            ->where('channel', 'pms')
            ->where('pms_posting_status', 'posted');

        if (filled($orderId)) {
            $specificTicketQuery->whereKey($orderId);
        } elseif (filled($posOrderUuid)) {
            $specificTicketQuery->where('uuid', $posOrderUuid);
        } elseif (filled($orderReference) && preg_match('/^POS-(\d+)$/i', (string) $orderReference, $matches)) {
            $specificTicketQuery->whereKey((int) $matches[1]);
        }

        if (filled($orderId) || filled($posOrderUuid) || filled($orderReference)) {
            $ticket = $specificTicketQuery->first();

            if (!$ticket) {
                return response()->json([
                    'status' => true,
                    'checked' => 0,
                    'synced' => 0,
                    'failed' => 0,
                ]);
            }

            try {
                $checked = 1;
                $synced = $this->syncTicketPmsPaymentStatus($ticket, $client) ? 1 : 0;
            } catch (RequestException) {
                $failed = 1;
            } catch (\Throwable) {
                $failed = 1;
            }

            return response()->json([
                'status' => true,
                'checked' => $checked,
                'synced' => $synced,
                'failed' => $failed,
            ]);
        }

        PosKitchenTicket::query()
            ->where('tenant_id', $this->tenantId())
            ->where('channel', 'pms')
            ->where('pms_posting_status', 'posted')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('pms_booking_id')
            ->oldest('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (PosKitchenTicket $ticket) use ($client, &$synced, &$checked, &$failed) {
                $checked++;

                try {
                    if ($this->syncTicketPmsPaymentStatus($ticket, $client)) {
                        $synced++;
                    }
                } catch (RequestException) {
                    $failed++;
                } catch (\Throwable) {
                    $failed++;
                }
            });

        return response()->json([
            'status' => true,
            'checked' => $checked,
            'synced' => $synced,
            'failed' => $failed,
        ]);
    }

    public function show(PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

        $ticket->load([
            'items.options',
            'register.branch',
            'customer',
        ]);

        $statusLogs = collect([
            [
                'status' => 'Pending',
                'changed_by' => 'System',
                'reason' => '-',
                'note' => '-',
                'changed_at' => optional($ticket->created_at)?->format('Y-m-d h:i A'),
            ],
            [
                'status' => $this->pretty((string) $ticket->status),
                'changed_by' => 'System',
                'reason' => '-',
                'note' => '-',
                'changed_at' => optional($ticket->updated_at)?->format('Y-m-d h:i A'),
            ],
        ]);

        $paidAmount = (float) (
            $ticket->paid_amount
            ?? $ticket->paid_total
            ?? (($ticket->payment_status === 'paid') ? $ticket->grand_total : 0)
        );

        $payments = collect();

        if ($paidAmount > 0 || filled($ticket->payment_method ?? null)) {
            $payments->push([
                'method' => $this->pretty($ticket->payment_method ?? $ticket->payment_type ?? 'Payment'),
                'status' => $this->pretty($ticket->payment_status ?: 'unpaid'),
                'amount' => $paidAmount,
                'currency' => $this->currencySymbol($ticket->currency_code ?: 'LKR'),
                'paid_at' => optional($ticket->updated_at)?->format('Y-m-d h:i A'),
            ]);
        }

        return Inertia::render('VendorAdmin/Sales/Orders/Show', [
            'order' => $ticket,
            'statusLogs' => $statusLogs,
            'payments' => $payments,
        ]);
    }

    private function syncTicketPmsPaymentStatus(PosKitchenTicket $ticket, PmsClient $client): bool
    {
        $bookingId = $ticket->pms_booking_id ?: data_get($ticket->pms_guest_snapshot, 'booking_id');

        if (!filled($bookingId)) {
            return false;
        }

        $response = $client->getBooking($bookingId);
        $orderReference = data_get($ticket->pms_response, 'data.order_reference')
            ?: data_get($ticket->pms_response, 'order_reference')
            ?: 'POS-' . $ticket->id;

        $matchedOrder = collect(data_get($response, 'data.pos_orders', []))
            ->first(function ($order) use ($ticket, $orderReference) {
                return (filled($orderReference) && (string) data_get($order, 'order_reference') === (string) $orderReference)
                    || (filled($ticket->uuid) && (string) data_get($order, 'pos_order_uuid') === (string) $ticket->uuid)
                    || (filled($ticket->id) && (string) data_get($order, 'pos_order_id') === (string) $ticket->id);
            });

        $paymentStatus = data_get($matchedOrder, 'payment_status');

        if (!in_array($paymentStatus, ['paid', 'unpaid', 'partial'], true)) {
            return false;
        }

        $paidAmount = round((float) (data_get($matchedOrder, 'paid_amount') ?? 0), 3);

        if ($paymentStatus === 'paid' && $paidAmount <= 0) {
            $paidAmount = round((float) (data_get($matchedOrder, 'total_amount') ?? $ticket->grand_total ?? 0), 3);
        }

        $changed = $ticket->payment_status !== $paymentStatus
            || round((float) ($ticket->paid_amount ?? 0), 3) !== $paidAmount;

        $ticket->update([
            'payment_status' => $paymentStatus,
            'paid_amount' => $paidAmount,
            'pms_response' => array_merge($ticket->pms_response ?: [], [
                'payment_status_check' => $response,
                'payment_status_order' => $matchedOrder,
                'payment_status_checked_at' => now()->toIso8601String(),
            ]),
        ]);

        return $changed;
    }

    public function cancel(Request $request, PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'register_id' => [
                'required',
                Rule::exists('pos_registers', 'id')->where(function ($query) {
                    $query->whereIn('branch_id', function ($subQuery) {
                        $subQuery
                            ->select('id')
                            ->from('branches')
                            ->where('tenant_id', $this->tenantId());
                    });
                }),
            ],
            'reason_id' => [
                'required',
                Rule::exists('sale_reasons', 'id')->where(function ($query) {
                    $query
                        ->where('tenant_id', $this->tenantId())
                        ->where('type', 'cancel')
                        ->where('is_active', true);
                }),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'register_id.required' => 'Please select a POS register.',
            'reason_id.required' => 'Please select a cancellation reason.',
        ]);

        if (in_array((string) $ticket->status, ['cancelled', 'canceled'], true)) {
            return back()->withErrors([
                'general' => 'This order is already cancelled.',
            ]);
        }

        $reason = SaleReason::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'cancel')
            ->where('is_active', true)
            ->findOrFail($validated['reason_id']);

        DB::transaction(function () use ($ticket, $validated, $reason) {
            $table = $ticket->getTable();

            $ticket->status = 'cancelled';

            if (Schema::hasColumn($table, 'cancelled_at')) {
                $ticket->cancelled_at = now();
            }

            if (Schema::hasColumn($table, 'cancelled_by')) {
                $ticket->cancelled_by = auth()->id();
            }

            if (Schema::hasColumn($table, 'cancelled_register_id')) {
                $ticket->cancelled_register_id = $validated['register_id'];
            }

            if (Schema::hasColumn($table, 'cancel_register_id')) {
                $ticket->cancel_register_id = $validated['register_id'];
            }

            if (Schema::hasColumn($table, 'cancel_reason_id')) {
                $ticket->cancel_reason_id = $reason->id;
            }

            if (Schema::hasColumn($table, 'cancelled_reason_id')) {
                $ticket->cancelled_reason_id = $reason->id;
            }

            if (Schema::hasColumn($table, 'sale_reason_id')) {
                $ticket->sale_reason_id = $reason->id;
            }

            if (Schema::hasColumn($table, 'cancel_reason')) {
                $ticket->cancel_reason = $reason->name;
            }

            if (Schema::hasColumn($table, 'cancellation_reason')) {
                $ticket->cancellation_reason = $reason->name;
            }

            if (Schema::hasColumn($table, 'cancel_note')) {
                $ticket->cancel_note = $validated['note'] ?? null;
            }

            if (Schema::hasColumn($table, 'cancellation_note')) {
                $ticket->cancellation_note = $validated['note'] ?? null;
            }

            if (Schema::hasColumn($table, 'cancelled_note')) {
                $ticket->cancelled_note = $validated['note'] ?? null;
            }

            $ticket->save();
        });

        $this->postPmsOrder($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']));

        return redirect()
            ->route('vendor.sales.orders.index')
            ->with('success', 'Order cancelled successfully.');
    }

    private function postPmsOrder(?PosKitchenTicket $ticket): void
    {
        if (!$ticket || $ticket->channel !== 'pms') {
            return;
        }

        $bookingId = $ticket->pms_booking_id ?: data_get($ticket->pms_guest_snapshot, 'booking_id');
        $roomKeyId = $ticket->pms_room_key_id ?: data_get($ticket->pms_guest_snapshot, 'room_key_id');

        if (!filled($bookingId) || !filled($roomKeyId)) {
            return;
        }

        $setting = PmsIntegrationSetting::query()
            ->where('vendor_id', $this->tenantId())
            ->where('active', true)
            ->first();

        if (!$setting) {
            $ticket->update([
                'pms_posting_status' => 'failed',
                'pms_response' => [
                    'message' => 'No active PMS integration is configured for this vendor.',
                ],
            ]);

            return;
        }

        $ticket->update([
            'pms_booking_id' => $ticket->pms_booking_id ?: $bookingId,
            'pms_room_key_id' => $ticket->pms_room_key_id ?: $roomKeyId,
            'pms_posting_status' => 'pending',
        ]);

        app(PmsOrderPoster::class)->post($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']), $setting);
    }

    private function referenceNo($row): string
    {
        $uuid = (string) ($row->uuid ?? '');

        if ($uuid !== '') {
            return 'ORD-' . strtoupper(substr($uuid, 0, 10));
        }

        return 'ORD-' . $row->id;
    }

    private function customerName($row): string
    {
        return $row->customer_name
            ?: optional($row->customer)->name
            ?: 'Walk-In Customer';
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 3, '.', ',');
    }

    private function pretty($value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }

    private function statusVariant($status): string
    {
        return match ((string) $status) {
            'pending' => 'warning',
            'preparing' => 'purple',
            'ready' => 'success',
            'served' => 'teal',
            'completed' => 'success',
            'cancelled', 'canceled' => 'danger',
            default => 'info',
        };
    }

    private function paymentVariant($status): string
    {
        return match ((string) $status) {
            'paid' => 'success',
            'partial' => 'warning',
            'unpaid' => 'danger',
            default => 'danger',
        };
    }

    private function badge(string $label, string $variant = 'info'): string
    {
        $styles = [
            'warning' => 'background:#fff5dc;color:#edae24;border:1px solid rgba(237,174,36,.25);',
            'success' => 'background:#dcfce7;color:#22c55e;border:1px solid rgba(34,197,94,.25);',
            'danger' => 'background:#ffe4e0;color:#ff6b63;border:1px solid rgba(255,107,99,.25);',
            'info' => 'background:#dce8ff;color:#4b83ff;border:1px solid rgba(75,131,255,.25);',
            'purple' => 'background:#efe5ff;color:#9a62ff;border:1px solid rgba(154,98,255,.25);',
            'teal' => 'background:#d7f6f1;color:#14b8a6;border:1px solid rgba(20,184,166,.25);',
            'orange' => 'background:#fde7c9;color:#f59e0b;border:1px solid rgba(245,158,11,.25);',
        ];

        $style = $styles[$variant] ?? $styles['info'];

        return '<span style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:24px;
            padding:0 10px;
            border-radius:8px;
            font-size:12px;
            font-weight:700;
            white-space:nowrap;
            ' . $style . '
        ">' . e($label) . '</span>';
    }
}
