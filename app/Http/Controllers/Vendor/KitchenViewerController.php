<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\Ingredient;
use App\Models\KitchenAlertSetting;
use App\Models\PosKitchenTicket;
use App\Models\PosKitchenTicketItem;
use App\Models\ProductIngredient;
use App\Services\Inventory\LowStockAlertMailer;
use App\Models\StockMovement;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KitchenViewerController extends Controller
{
    use ResolvesTenantContext;

    public function index(Request $request)
    {
        abort_unless($request->user('vendor')?->can('pos-kitchen.view'), 403);

        $setting = KitchenAlertSetting::query()
        ->where('tenant_id', $this->tenantId())
        ->first();

    $orders = PosKitchenTicket::query()
        ->with([
            'items.options',
            'register:id,name,branch_id',
            'register.branch:id,name',
            'table:id,name',
        ])
        ->where('tenant_id', $this->tenantId())
        ->where(function ($query) {
            $query->whereIn('status', ['pending', 'preparing'])
                ->orWhere(function ($query) {
                    $query->where('status', 'ready')
                        ->where('ready_at', '>', now()->subSeconds(3));
                });
        })
        ->latest('sent_to_kitchen_at')
        ->get();

    return Inertia::render('VendorAdmin/POS/Kitchen/Index', [
        'orders' => $orders,
        'tenantId' => $this->tenantId(),
        'kitchenAlertSoundEnabled' => $setting?->sound_enabled ?? true,
        'kitchenAlertSound' => $setting?->sound ?: 'bell',
    ]);
}
    public function startPreparing(PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);
        abort_unless(auth('vendor')->user()?->can('pos-kitchen.edit'), 403);

        try {
            DB::transaction(function () use ($ticket) {
                $ticket = PosKitchenTicket::query()
                    ->where('tenant_id', $this->tenantId())
                    ->lockForUpdate()
                    ->findOrFail($ticket->id);

                if ($ticket->status === 'cancelled') {
                    throw new Exception('Cancelled orders cannot be prepared.');
                }

                if ($ticket->status === 'served') {
                    throw new Exception('Completed orders cannot be changed.');
                }

                if ($ticket->status !== 'preparing') {
                    $this->recordPreparedStockMovements($ticket);
                }

                $ticket->update([
                    'status' => 'preparing',
                    'prepared_at' => $ticket->prepared_at ?: now(),
                ]);

                $ticket->items()
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'preparing',
                        'started_at' => now(),
                    ]);
            });
        } catch (Exception $ex) {
            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to prepare order.',
            ]);
        }

        return back()->with('success', 'Order moved to preparing successfully.');
    }

    public function markReady(PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);
        abort_unless(auth('vendor')->user()?->can('pos-kitchen.edit'), 403);

        if ($ticket->status === 'cancelled') {
            return back()->withErrors([
                'general' => 'Cancelled orders cannot be marked ready.',
            ]);
        }

        $ticket->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        $ticket->items()
            ->whereIn('status', ['pending', 'preparing'])
            ->update([
                'status' => 'ready',
                'ready_at' => now(),
            ]);

        return back()->with('success', 'Order marked as ready successfully.');
    }

    public function startItemPreparing(PosKitchenTicketItem $item)
    {
        $item->loadMissing('ticket');
        abort_unless((int) $item->ticket?->tenant_id === (int) $this->tenantId(), 404);
        abort_unless(auth('vendor')->user()?->can('pos-kitchen.edit'), 403);

        try {
            DB::transaction(function () use ($item) {
                $item = PosKitchenTicketItem::query()
                    ->with('ticket')
                    ->lockForUpdate()
                    ->findOrFail($item->id);

                $ticket = PosKitchenTicket::query()
                    ->where('tenant_id', $this->tenantId())
                    ->lockForUpdate()
                    ->findOrFail($item->pos_kitchen_ticket_id);

                if ($ticket->status === 'cancelled') {
                    throw new Exception('Cancelled orders cannot be prepared.');
                }

                if ($ticket->status === 'served') {
                    throw new Exception('Completed orders cannot be changed.');
                }

                if ($item->status === 'ready') {
                    throw new Exception('Ready items cannot be prepared again.');
                }

                if ($item->status !== 'preparing') {
                    $this->recordPreparedStockMovementForItem($ticket, $item);

                    $item->update([
                        'status' => 'preparing',
                        'started_at' => $item->started_at ?: now(),
                    ]);
                }

                if ($ticket->status !== 'preparing') {
                    $ticket->update([
                        'status' => 'preparing',
                        'prepared_at' => $ticket->prepared_at ?: now(),
                    ]);
                }
            });
        } catch (Exception $ex) {
            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to prepare item.',
            ]);
        }

        return back()->with('success', 'Item moved to preparing successfully.');
    }

    public function markItemReady(PosKitchenTicketItem $item)
    {
        $item->loadMissing('ticket');
        abort_unless((int) $item->ticket?->tenant_id === (int) $this->tenantId(), 404);
        abort_unless(auth('vendor')->user()?->can('pos-kitchen.edit'), 403);

        try {
            DB::transaction(function () use ($item) {
                $item = PosKitchenTicketItem::query()
                    ->with('ticket')
                    ->lockForUpdate()
                    ->findOrFail($item->id);

                $ticket = PosKitchenTicket::query()
                    ->where('tenant_id', $this->tenantId())
                    ->lockForUpdate()
                    ->findOrFail($item->pos_kitchen_ticket_id);

                if ($ticket->status === 'cancelled') {
                    throw new Exception('Cancelled orders cannot be marked ready.');
                }

                if ($ticket->status === 'served') {
                    throw new Exception('Completed orders cannot be changed.');
                }

                if ($item->status === 'pending') {
                    throw new Exception('Start preparing this item first.');
                }

                if ($item->status !== 'ready') {
                    $item->update([
                        'status' => 'ready',
                        'ready_at' => now(),
                    ]);
                }

                $remainingItems = $ticket->items()
                    ->where('status', '!=', 'ready')
                    ->count();

                if ($remainingItems === 0) {
                    $ticket->update([
                        'status' => 'ready',
                        'ready_at' => $ticket->ready_at ?: now(),
                    ]);
                } elseif ($ticket->status !== 'preparing') {
                    $ticket->update([
                        'status' => 'preparing',
                        'prepared_at' => $ticket->prepared_at ?: now(),
                    ]);
                }
            });
        } catch (Exception $ex) {
            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to update item.',
            ]);
        }

        return back()->with('success', 'Item marked as ready successfully.');
    }

    public function markServed(PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);
        abort_unless(auth('vendor')->user()?->can('pos-kitchen.edit'), 403);

        if ($ticket->status === 'cancelled') {
            return back()->withErrors([
                'general' => 'Cancelled orders cannot be completed.',
            ]);
        }

        $ticket->update([
            'status' => 'served',
            'served_at' => now(),
        ]);

        return back()->with('success', 'Order completed successfully.');
    }

    public function undoStatus(Request $request, PosKitchenTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);
        abort_unless($request->user('vendor')?->can('pos-kitchen.edit'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,preparing,ready'],
        ]);

        try {
            DB::transaction(function () use ($ticket, $validated) {
                $ticket = PosKitchenTicket::query()
                    ->where('tenant_id', $this->tenantId())
                    ->lockForUpdate()
                    ->findOrFail($ticket->id);

                if ($ticket->status === 'cancelled') {
                    throw new Exception('This order status cannot be undone.');
                }

                $status = $validated['status'];
                $updates = [
                    'status' => $status,
                    'served_at' => null,
                ];

                if ($status !== 'ready') {
                    $updates['ready_at'] = null;
                }

                $ticket->update($updates);

                if ($status === 'pending') {
                    $ticket->items()
                        ->whereIn('status', ['preparing', 'ready'])
                        ->update([
                            'status' => 'pending',
                            'started_at' => null,
                            'ready_at' => null,
                        ]);
                } elseif ($status === 'preparing') {
                    $ticket->items()
                        ->where('status', 'ready')
                        ->update([
                            'status' => 'preparing',
                            'ready_at' => null,
                        ]);
                }
            });
        } catch (Exception $ex) {
            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to undo status.',
            ]);
        }

        return back()->with('success', 'Status change undone successfully.');
    }

    public function undoItemStatus(Request $request, PosKitchenTicketItem $item)
    {
        $item->loadMissing('ticket');
        abort_unless((int) $item->ticket?->tenant_id === (int) $this->tenantId(), 404);
        abort_unless($request->user('vendor')?->can('pos-kitchen.edit'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,preparing'],
        ]);

        try {
            DB::transaction(function () use ($item, $validated) {
                $item = PosKitchenTicketItem::query()
                    ->with('ticket')
                    ->lockForUpdate()
                    ->findOrFail($item->id);

                $ticket = PosKitchenTicket::query()
                    ->where('tenant_id', $this->tenantId())
                    ->lockForUpdate()
                    ->findOrFail($item->pos_kitchen_ticket_id);

                if (in_array($ticket->status, ['cancelled', 'served'], true)) {
                    throw new Exception('This item status cannot be undone.');
                }

                $status = $validated['status'];
                $updates = ['status' => $status];

                if ($status === 'pending') {
                    $updates['started_at'] = null;
                }

                $updates['ready_at'] = null;
                $item->update($updates);

                $hasReadyItems = $ticket->items()
                    ->where('status', 'ready')
                    ->exists();
                $hasPreparingItems = $ticket->items()
                    ->where('status', 'preparing')
                    ->exists();

                if ($hasReadyItems || $hasPreparingItems) {
                    $ticket->update([
                        'status' => 'preparing',
                        'ready_at' => null,
                    ]);
                } else {
                    $ticket->update([
                        'status' => 'pending',
                        'ready_at' => null,
                    ]);
                }
            });
        } catch (Exception $ex) {
            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to undo status.',
            ]);
        }

        return back()->with('success', 'Status change undone successfully.');
    }

    private function recordPreparedStockMovements(PosKitchenTicket $ticket): void
    {
        $legacyRecorded = StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('source_id', $ticket->id)
            ->where('source_name', 'Kitchen Order')
            ->exists();

        if ($legacyRecorded) {
            return;
        }

        $ticket->loadMissing('items');

        foreach ($ticket->items as $item) {
            $this->recordPreparedStockMovementForItem($ticket, $item);
        }
    }

    private function recordPreparedStockMovementForItem(PosKitchenTicket $ticket, PosKitchenTicketItem $item): void
    {
        $alreadyRecorded = StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('source_id', $item->id)
            ->where('source_name', 'Kitchen Order Item')
            ->exists();

        $legacyRecorded = StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('source_id', $ticket->id)
            ->where('source_name', 'Kitchen Order')
            ->exists();

        if ($alreadyRecorded || $legacyRecorded || !$item->product_id || (float) $item->qty <= 0) {
            return;
        }

        $recipeRows = ProductIngredient::query()
            ->where('product_id', $item->product_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($recipeRows as $recipeRow) {
            $ingredient = Ingredient::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->find($recipeRow->ingredient_id);

            if (!$ingredient) {
                continue;
            }

            $usedQuantity = $this->usedIngredientQuantity($recipeRow, (float) $item->qty);

            if ($usedQuantity <= 0) {
                continue;
            }

            $previousStock = (float) $ingredient->current_stock;
            $previousAlertQuantity = (float) $ingredient->alert_quantity;
            $newStock = (float) $ingredient->current_stock - $usedQuantity;

            if ($newStock < 0) {
                throw new Exception(
                    'STOCK_SHORTAGE|'
                    . $ingredient->name . '|'
                    . number_format((float) $ingredient->current_stock, 3, '.', '') . '|'
                    . number_format($usedQuantity, 3, '.', '')
                );
            }

            $ingredient->current_stock = $newStock;
            $ingredient->save();

            app(LowStockAlertMailer::class)->notifyIfLow($ingredient, $previousStock, $previousAlertQuantity);

            StockMovement::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $ticket->branch_id,
                'ingredient_id' => $ingredient->id,
                'type' => 'out',
                'quantity' => $usedQuantity,
                'note' => 'Used for order #' . $ticket->id . ' - ' . $item->product_name,
                'source_id' => $item->id,
                'source_name' => 'Kitchen Order Item',
            ]);
        }
    }

    private function usedIngredientQuantity(ProductIngredient $recipeRow, float $itemQty): float
    {
        $baseQuantity = (float) $recipeRow->quantity * $itemQty;
        $lossQuantity = $baseQuantity * ((float) $recipeRow->loss_pct / 100);

        return round($baseQuantity + $lossQuantity, 4);
    }
}
