<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\Customer;
use App\Models\LoyaltyCustomer;
use App\Models\LoyaltyGift;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTransaction;
use App\Models\PosSession;
use App\Models\PosSessionItem;
use App\Models\Product;
use App\Models\PromotionDiscount;
use App\Models\PromotionVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosLoyaltyController extends BaseLoyaltyController
{
    public function summary(PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $customerId = $session->customer_id;
        if (!$customerId) {
            return response()->json([
                'customer' => null,
                'account' => null,
                'rewards' => [],
            ]);
        }

        $program = $this->activeProgram();
        $customer = Customer::where('tenant_id', $this->tenantId())->findOrFail($customerId);
        $account = $program ? $this->accountFor($customer->id, $program) : null;

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'account' => $account ? [
                'program' => $program->name,
                'tier' => $account->tier?->name ?: 'Member',
                'tier_icon_url' => $account->tier?->icon_url,
                'points_balance' => (int) $account->points_balance,
                'lifetime_points' => (int) $account->lifetime_points,
            ] : null,
            'rewards' => $program ? $this->availableRewards($session, $account)->values() : [],
        ]);
    }

    public function redeem(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'reward_id' => ['required', 'integer'],
        ]);

        if (!$session->customer_id) {
            throw ValidationException::withMessages(['reward_id' => 'Select a customer before redeeming points.']);
        }

        $program = $this->activeProgram();
        $reward = LoyaltyReward::query()
            ->where('tenant_id', $this->tenantId())
            ->where('loyalty_program_id', $program?->id)
            ->where('is_active', true)
            ->findOrFail($validated['reward_id']);

        $account = $this->accountFor($session->customer_id, $program);
        if ($account->points_balance < $reward->points_cost) {
            throw ValidationException::withMessages(['reward_id' => 'This customer does not have enough points.']);
        }

        $session->load('items');
        $appliedGiftIds = $session->items->pluck('loyalty_gift_id')->filter()->toArray();
        $appliedRewardIds = [];
        if ($session->loyalty_reward_id) {
            $appliedRewardIds[] = (int) $session->loyalty_reward_id;
        }
        if (!empty($appliedGiftIds)) {
            $giftRewardIds = LoyaltyGift::whereIn('id', $appliedGiftIds)
                ->pluck('loyalty_reward_id')
                ->filter()
                ->toArray();
            $appliedRewardIds = array_merge($appliedRewardIds, $giftRewardIds);
        }
        $appliedRewardIds = array_unique(array_map('intval', $appliedRewardIds));

        if (in_array((int) $reward->id, $appliedRewardIds, true)) {
            throw ValidationException::withMessages(['reward_id' => 'This loyalty reward is already applied to this order.']);
        }

        $orderTotal = (float) $session->items->sum(fn ($item) => (float) $item->line_subtotal + (float) $item->tax_total);
        if (!$this->rewardMatchesSession($reward, $session, $orderTotal)) {
            throw ValidationException::withMessages(['reward_id' => 'This reward is not available for the current order.']);
        }

        DB::transaction(function () use ($reward, $account, $session) {
            $before = (int) $account->points_balance;
            $after = max(0, $before - (int) $reward->points_cost);

            $account->update([
                'points_balance' => $after,
                'last_redeemed_at' => now(),
            ]);

            $gift = null;
            if (in_array($reward->type, ['free_item', 'voucher_code', 'tier_upgrade'], true)) {
                $gift = LoyaltyGift::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $this->tenantId(),
                    'customer_id' => $session->customer_id,
                    'loyalty_program_id' => $reward->loyalty_program_id,
                    'loyalty_reward_id' => $reward->id,
                    'status' => $reward->type === 'tier_upgrade' ? 'used' : 'available',
                    'type' => $reward->type,
                    'points_spent' => $reward->points_cost,
                    'code' => $reward->type === 'voucher_code' ? $this->giftCode($reward) : null,
                    'payload' => [
                        'value_type' => $reward->value_type,
                        'value' => $reward->value,
                        'product_id' => $reward->product_id,
                        'quantity' => $reward->quantity,
                        'target_tier_id' => $reward->target_tier_id,
                    ],
                    'valid_until' => $reward->expires_in_days ? now()->addDays($reward->expires_in_days) : null,
                    'used_at' => $reward->type === 'tier_upgrade' ? now() : null,
                ]);

                if ($reward->type === 'tier_upgrade' && $reward->target_tier_id) {
                    $account->update(['loyalty_tier_id' => $reward->target_tier_id]);
                }
            }

            if ($reward->type === 'discount') {
                $previousLoyaltyDiscount = (float) ($session->loyalty_discount_total ?? 0);
                $loyaltyDiscount = $this->rewardDiscountAmount($reward, $session);

                $session->update([
                    'loyalty_reward_id' => $reward->id,
                    'loyalty_points_redeemed' => $reward->points_cost,
                    'loyalty_discount_total' => $loyaltyDiscount,
                ]);

                $this->refreshSessionTotalsAfterRedeem($session->fresh(['items']), $previousLoyaltyDiscount, $loyaltyDiscount);
            }

            LoyaltyTransaction::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'customer_id' => $session->customer_id,
                'loyalty_program_id' => $reward->loyalty_program_id,
                'loyalty_reward_id' => $reward->id,
                'loyalty_gift_id' => $gift?->id,
                'type' => 'redeem',
                'description' => 'Points redeemed for ' . $reward->name . '.',
                'points' => -1 * (int) $reward->points_cost,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency_mode' => $session->currency_mode,
                'currency_code' => $session->currency_code,
            ]);

            $reward->increment('redeemed_count');
        });

        return redirect()->route('vendor.pos.viewer', $session->pos_register_id)
            ->with('success', 'Loyalty reward redeemed successfully.');
    }

    public function cancelRedeem(PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        if ($session->loyalty_gift_id) {
            DB::transaction(function () use ($session) {
                $gift = LoyaltyGift::where('tenant_id', $this->tenantId())
                    ->find($session->loyalty_gift_id);
                if ($gift) {
                    $gift->update(['status' => 'available']);
                }

                $previousLoyaltyDiscount = (float) ($session->loyalty_discount_total ?? 0);

                $session->update([
                    'loyalty_gift_id' => null,
                    'loyalty_reward_id' => null,
                    'loyalty_points_redeemed' => 0,
                    'loyalty_discount_total' => 0,
                ]);

                $session->loadMissing('items');
                $subtotal = (float) $session->items->sum('line_subtotal');
                $taxTotal = (float) $session->items->sum('tax_total');
                $grossTotal = $subtotal + $taxTotal;

                $promotionDiscount = max(0, (float) $session->discount_total - $previousLoyaltyDiscount);

                $session->update([
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'discount_total' => $promotionDiscount,
                    'grand_total' => max(0, $grossTotal - $promotionDiscount),
                    'item_count' => (int) $session->items->sum('qty'),
                ]);
            });

            return back()->with('success', 'Loyalty reward removed successfully.');
        }

        if (!$session->loyalty_reward_id) {
            return back()->with('success', 'No loyalty reward applied.');
        }

        $program = $this->activeProgram();
        if (!$program) {
            return back()->with('error', 'Active loyalty program not found.');
        }

        $reward = LoyaltyReward::query()
            ->where('tenant_id', $this->tenantId())
            ->where('loyalty_program_id', $program->id)
            ->findOrFail($session->loyalty_reward_id);

        $account = $this->accountFor($session->customer_id, $program);
        $previousLoyaltyDiscount = (float) ($session->loyalty_discount_total ?? 0);

        DB::transaction(function () use ($reward, $account, $session, $previousLoyaltyDiscount) {
            $before = (int) $account->points_balance;
            $after = $before + (int) $reward->points_cost;

            $account->update([
                'points_balance' => $after,
            ]);

            // Create a refund transaction
            LoyaltyTransaction::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'customer_id' => $session->customer_id,
                'loyalty_program_id' => $reward->loyalty_program_id,
                'loyalty_reward_id' => $reward->id,
                'type' => 'refund',
                'description' => 'Refunded points for removing ' . $reward->name . '.',
                'points' => (int) $reward->points_cost,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency_mode' => $session->currency_mode,
                'currency_code' => $session->currency_code,
            ]);

            // Clear loyalty discount fields on the session
            $session->update([
                'loyalty_reward_id' => null,
                'loyalty_points_redeemed' => 0,
                'loyalty_discount_total' => 0,
            ]);

            $reward->decrement('redeemed_count');

            // Recalculate totals
            $session->loadMissing('items');
            $subtotal = (float) $session->items->sum('line_subtotal');
            $taxTotal = (float) $session->items->sum('tax_total');
            $grossTotal = $subtotal + $taxTotal;

            $promotionDiscount = max(0, (float) $session->discount_total - $previousLoyaltyDiscount);

            $session->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $promotionDiscount,
                'grand_total' => max(0, $grossTotal - $promotionDiscount),
                'item_count' => (int) $session->items->sum('qty'),
            ]);
        });

        return back()->with('success', 'Loyalty reward removed and points refunded.');
    }

    public function gifts(PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        if (!$session->customer_id) {
            return response()->json(['gifts' => []]);
        }

        $gifts = LoyaltyGift::query()
            ->with('reward:id,name,type,points_cost')
            ->where('tenant_id', $this->tenantId())
            ->where('customer_id', $session->customer_id)
            ->where('status', 'available')
            ->where(function ($query) {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->latest()
            ->get()
            ->map(fn ($gift) => [
                'id' => $gift->id,
                'reward' => $gift->reward?->name,
                'type' => $gift->type,
                'code' => $gift->code,
                'valid_until' => optional($gift->valid_until)?->format('Y-m-d h:i A'),
                'points_spent' => $gift->points_spent,
            ]);

        return response()->json(['gifts' => $gifts]);
    }

    private function activeProgram(): ?LoyaltyProgram
    {
        return LoyaltyProgram::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->oldest()
            ->first();
    }

    private function accountFor(int $customerId, LoyaltyProgram $program): LoyaltyCustomer
    {
        $account = LoyaltyCustomer::firstOrCreate(
            [
                'tenant_id' => $this->tenantId(),
                'customer_id' => $customerId,
                'loyalty_program_id' => $program->id,
            ],
            [
                'loyalty_tier_id' => $program->tiers()->where('is_active', true)->orderBy('minimum_spend')->value('id'),
                'points_balance' => 0,
                'lifetime_points' => 0,
                'lifetime_spend' => 0,
                'secondary_lifetime_spend' => 0,
            ]
        );

        return $account->load('tier:id,name');
    }

    private function availableRewards(PosSession $session, ?LoyaltyCustomer $account)
    {
        $session->loadMissing('items');
        $orderTotal = (float) $session->items->sum(fn ($item) => (float) $item->line_subtotal + (float) $item->tax_total);

        return LoyaltyReward::query()
            ->with('tier:id,name')
            ->where('tenant_id', $this->tenantId())
            ->where('loyalty_program_id', $account?->loyalty_program_id)
            ->where('is_active', true)
            ->orderBy('points_cost')
            ->get()
            ->filter(fn ($reward) => $account && $account->points_balance >= $reward->points_cost && $this->rewardMatchesSession($reward, $session, $orderTotal))
            ->map(fn ($reward) => [
                'id' => $reward->id,
                'name' => $reward->name,
                'type' => $reward->type,
                'points_cost' => (int) $reward->points_cost,
                'description' => $reward->description,
            ]);
    }

    private function rewardMatchesSession(LoyaltyReward $reward, PosSession $session, float $orderTotal): bool
    {
        if ($reward->starts_at && $reward->starts_at->isFuture()) {
            return false;
        }

        if ($reward->ends_at && $reward->ends_at->isPast()) {
            return false;
        }

        if ($reward->usage_limit && $reward->redeemed_count >= $reward->usage_limit) {
            return false;
        }

        $branchIds = array_filter(array_map('intval', $reward->branch_ids ?? []));
        if ($branchIds && !in_array((int) $session->branch_id, $branchIds, true)) {
            return false;
        }

        $days = $reward->available_days ?? [];
        if ($days && !in_array(strtolower(now()->format('l')), $days, true)) {
            return false;
        }

        $min = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()
            ? ($reward->secondary_minimum_order_total ?? $reward->minimum_order_total)
            : $reward->minimum_order_total;
        if ($min !== null && $orderTotal + 0.0001 < (float) $min) {
            return false;
        }

        $max = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()
            ? ($reward->secondary_maximum_order_total ?? $reward->maximum_order_total)
            : $reward->maximum_order_total;
        if ($max !== null && $orderTotal > (float) $max) {
            return false;
        }

        return true;
    }

    private function rewardDiscountAmount(LoyaltyReward $reward, PosSession $session): float
    {
        $session->loadMissing('items');
        $base = (float) $session->items->sum(fn ($item) => (float) $item->line_subtotal + (float) $item->tax_total);
        $value = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()
            ? (float) ($reward->secondary_value ?? $reward->value ?? 0)
            : (float) ($reward->value ?? 0);

        $discount = $reward->value_type === 'percentage' ? ($base * $value / 100) : $value;

        return min($base, max(0, $discount));
    }

    private function refreshSessionTotalsAfterRedeem(PosSession $session, float $previousLoyaltyDiscount, float $loyaltyDiscount): void
    {
        $session->loadMissing('items');

        $subtotal = (float) $session->items->sum('line_subtotal');
        $taxTotal = (float) $session->items->sum('tax_total');
        $grossTotal = $subtotal + $taxTotal;
        $promotionDiscount = max(0, (float) $session->discount_total - $previousLoyaltyDiscount);
        $discountTotal = min($grossTotal, $promotionDiscount + $loyaltyDiscount);

        $session->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => max(0, $grossTotal - $discountTotal),
            'item_count' => (int) $session->items->sum('qty'),
        ]);
    }

    private function giftCode(LoyaltyReward $reward): string
    {
        do {
            $code = strtoupper(($reward->code_prefix ?: 'LOY') . '-' . Str::random(8));
        } while (LoyaltyGift::where('code', $code)->exists());

        return $code;
    }

    public function applyGift(PosSession $session, LoyaltyGift $gift)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        if (!$session->customer_id) {
            throw ValidationException::withMessages(['gift' => 'Select a customer before claiming loyalty gifts.']);
        }

        if ((int) $session->customer_id !== (int) $gift->customer_id) {
            throw ValidationException::withMessages(['gift' => 'This loyalty gift does not belong to the selected customer.']);
        }

        if ($gift->status !== 'available') {
            throw ValidationException::withMessages(['gift' => 'This loyalty gift is not available.']);
        }

        if ($gift->valid_until && $gift->valid_until->isPast()) {
            throw ValidationException::withMessages(['gift' => 'This loyalty gift has expired.']);
        }

        $session->loadMissing('items');
        $appliedGiftIds = $session->items->pluck('loyalty_gift_id')->filter()->toArray();
        $appliedRewardIds = [];
        if ($session->loyalty_reward_id) {
            $appliedRewardIds[] = (int) $session->loyalty_reward_id;
        }
        if (!empty($appliedGiftIds)) {
            $giftRewardIds = LoyaltyGift::whereIn('id', $appliedGiftIds)
                ->pluck('loyalty_reward_id')
                ->filter()
                ->toArray();
            $appliedRewardIds = array_merge($appliedRewardIds, $giftRewardIds);
        }
        $appliedRewardIds = array_unique(array_map('intval', $appliedRewardIds));

        if ($gift->loyalty_reward_id && in_array((int) $gift->loyalty_reward_id, $appliedRewardIds, true)) {
            throw ValidationException::withMessages(['gift' => 'This loyalty reward is already applied to this order.']);
        }

        DB::transaction(function () use ($session, $gift) {
            if ($gift->type === 'free_item') {
                $productId = $gift->payload['product_id'] ?? null;
                if (!$productId) {
                    throw ValidationException::withMessages(['gift' => 'This gift does not specify a product.']);
                }

                $product = Product::where('tenant_id', $this->tenantId())
                    ->with('ingredients.ingredient')
                    ->findOrFail($productId);

                $quantity = (float) ($gift->payload['quantity'] ?? 1);

                // Simple ingredient stock check
                foreach ($product->ingredients as $recipeRow) {
                    $ingredient = $recipeRow->ingredient;
                    if ($ingredient && (bool) $ingredient->is_active) {
                        $branchIds = array_filter(array_map('intval', $ingredient->branch_ids ?? []));
                        if (!$session->branch_id || !$branchIds || in_array((int) $session->branch_id, $branchIds, true)) {
                            $baseQuantity = (float) $recipeRow->quantity;
                            $lossQuantity = $baseQuantity * ((float) $recipeRow->loss_pct / 100);
                            $required = round($baseQuantity + $lossQuantity, 4);
                            
                            $existingQty = (float) $session->items()->where('product_id', $product->id)->sum('qty');
                            $requested = ($existingQty + $quantity) * $required;
                            
                            if ($ingredient->current_stock < $requested) {
                                throw ValidationException::withMessages([
                                    'gift' => "{$product->name} is unavailable from recipe stock."
                                ]);
                            }
                        }
                    }
                }

                $useSecondary = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
                $currencyCode = $useSecondary ? $this->secondaryCurrencyCode() : $this->baseCurrencyCode();

                // Create POS session item for the free item
                PosSessionItem::create([
                    'pos_session_id' => $session->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'image_url' => $product->image_url,
                    'currency_mode' => $session->currency_mode,
                    'currency_code' => $currencyCode,
                    'qty' => $quantity,
                    'unit_price' => 0.00,
                    'option_total' => 0.00,
                    'line_subtotal' => 0.00,
                    'tax_total' => 0.00,
                    'line_total' => 0.00,
                    'tax_snapshot' => [],
                    'loyalty_gift_id' => $gift->id,
                ]);

                // Update gift status to claimed
                $gift->update(['status' => 'claimed']);

                $this->refreshSessionTotals($session);

            } elseif ($gift->type === 'voucher_code') {
                if ($session->loyalty_reward_id || $session->loyalty_gift_id) {
                    throw ValidationException::withMessages(['gift' => 'Another loyalty reward or gift is already applied to this session.']);
                }

                $giftDiscount = $this->giftDiscountAmount($gift, $session);

                $session->update([
                    'loyalty_gift_id' => $gift->id,
                    'loyalty_reward_id' => $gift->loyalty_reward_id,
                    'loyalty_points_redeemed' => 0,
                    'loyalty_discount_total' => $giftDiscount,
                ]);

                // Update gift status to claimed
                $gift->update(['status' => 'claimed']);

                $this->refreshSessionTotals($session);
            } else {
                throw ValidationException::withMessages(['gift' => 'Unsupported loyalty gift type.']);
            }
        });

        return back()->with('success', 'Loyalty reward claimed successfully.');
    }

    private function giftDiscountAmount(LoyaltyGift $gift, PosSession $session): float
    {
        $session->loadMissing('items');
        $base = (float) $session->items->sum(fn ($item) => (float) $item->line_subtotal + (float) $item->tax_total);
        
        $payload = $gift->payload ?? [];
        $valueType = $payload['value_type'] ?? 'fixed';
        
        $reward = $gift->reward;
        if (!$reward && $gift->loyalty_reward_id) {
            $reward = LoyaltyReward::find($gift->loyalty_reward_id);
        }
        
        if ($reward && $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()) {
            $value = (float) ($reward->secondary_value ?? $reward->value ?? 0);
        } else {
            $value = (float) ($payload['value'] ?? ($reward ? $reward->value : 0));
        }
        
        $discount = $valueType === 'percentage' ? ($base * $value / 100) : $value;
        return min($base, max(0, $discount));
    }

    private function refreshSessionTotals(PosSession $session): void
    {
        $session->load('items');

        $subtotal = (float) $session->items->sum(fn ($item) => (float) $item->line_subtotal);
        $taxTotal = (float) $session->items->sum(fn ($item) => (float) $item->tax_total);

        $discountTotal = 0;
        if ($session->discount_type && $session->discount_value !== null) {
            $discountTotal = $session->discount_type === 'percentage'
                ? ($subtotal * (float) $session->discount_value) / 100
                : (float) $session->discount_value;
                
            $promotion = null;
            if ($session->promotion_discount_id) {
                $promotion = PromotionDiscount::find($session->promotion_discount_id);
            } elseif ($session->promotion_voucher_id) {
                $promotion = PromotionVoucher::find($session->promotion_voucher_id);
            }
            
            if ($promotion) {
                $useSecondary = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
                $maxDiscount = $useSecondary ? $promotion->secondary_max_discount : $promotion->max_discount;
                if ($maxDiscount !== null) {
                    $discountTotal = min($discountTotal, (float) $maxDiscount);
                }
            }
        }

        $loyaltyDiscount = (float) ($session->loyalty_discount_total ?? 0);
        $discountTotal += min($loyaltyDiscount, max(0, ($subtotal + $taxTotal) - $discountTotal));
        $grandTotal = max(0, ($subtotal + $taxTotal) - $discountTotal);

        $session->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'item_count' => (int) $session->items->sum('qty'),
        ]);
    }
}
