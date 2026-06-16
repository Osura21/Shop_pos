<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\GiftCardBatch;
use App\Models\GiftCardTransaction;
use App\Models\Tenant;
use App\Models\TenantCurrencySetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GiftCardSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->select('id')->chunkById(50, function ($tenants) {
            foreach ($tenants as $tenant) {
                $branch = Branch::query()->where('tenant_id', $tenant->id)->select('id')->first();

                if (!$branch) {
                    continue;
                }

                $setting = TenantCurrencySetting::query()->where('tenant_id', $tenant->id)->first();
                $baseCode = $setting?->base_currency_code ?: 'LKR';
                $secondaryCode = $setting?->secondary_currency_code;

                $batch = GiftCardBatch::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => 'Gift Card Starter Batch'],
                    [
                        'branch_id' => $branch->id,
                        'base_currency_code' => $baseCode,
                        'secondary_currency_code' => $secondaryCode,
                        'prefix' => 'GC',
                        'quantity' => 3,
                        'value' => 100,
                        'secondary_value' => $secondaryCode ? 100 : null,
                        'cards_generated' => 0,
                        'cards_used' => 0,
                    ],
                );

                if ($batch->cards_generated > 0) {
                    continue;
                }

                for ($i = 0; $i < 3; $i++) {
                    $card = GiftCard::create([
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'gift_card_batch_id' => $batch->id,
                        'base_currency_code' => $baseCode,
                        'secondary_currency_code' => $secondaryCode,
                        'code' => $this->code(),
                        'initial_balance' => 100,
                        'current_balance' => 100,
                        'secondary_initial_balance' => $secondaryCode ? 100 : null,
                        'secondary_current_balance' => $secondaryCode ? 100 : null,
                        'status' => 'active',
                        'expires_at' => now()->addYear()->toDateString(),
                        'purchased_at' => now(),
                    ]);

                    GiftCardTransaction::create([
                        'uuid' => (string) Str::uuid(),
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'gift_card_id' => $card->id,
                        'currency_mode' => 'base',
                        'currency_code' => $baseCode,
                        'type' => 'purchase',
                        'amount' => 100,
                        'balance_before' => 0,
                        'balance_after' => 100,
                        'note' => 'Starter sample gift card',
                        'occurred_at' => now(),
                    ]);
                }

                $batch->update(['cards_generated' => 3]);
            }
        });
    }

    private function code(): string
    {
        do {
            $code = 'GC-' . strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }
}
