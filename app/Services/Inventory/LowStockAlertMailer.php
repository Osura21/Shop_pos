<?php

namespace App\Services\Inventory;

use App\Mail\LowStockAlertMail;
use App\Models\Ingredient;
use App\Models\ProductIngredient;
use App\Models\Tenant;
use App\Models\VendorMailSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LowStockAlertMailer
{
    public function notifyIfLow(Ingredient $ingredient, ?float $previousStock = null, ?float $previousAlertQuantity = null): void
    {
        $ingredient->refresh()->load('unit:id,name,symbol');

        if (! $this->crossedIntoLowStock($ingredient, $previousStock, $previousAlertQuantity)) {
            return;
        }

        $this->sendForTenant((int) $ingredient->tenant_id, $ingredient);
    }

    public function sendForTenant(int $tenantId, ?Ingredient $triggeredIngredient = null): void
    {
        $setting = VendorMailSetting::query()
            ->where('tenant_id', $tenantId)
            ->first();

        $to = $this->parseAddressList($setting?->to_addresses);

        if (! count($to)) {
            return;
        }

        $lowIngredients = Ingredient::query()
            ->with('unit:id,name,symbol')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('alert_quantity', '>', 0)
            ->whereColumn('current_stock', '<=', 'alert_quantity')
            ->orderByRaw('(alert_quantity - current_stock) DESC')
            ->get();

        if ($lowIngredients->isEmpty()) {
            return;
        }

        $tenant = Tenant::query()->find($tenantId);
        $payload = [
            'tenant_name' => $tenant?->name ?: 'Restaurant',
            'triggered_ingredient' => $triggeredIngredient
                ? $this->ingredientRow($triggeredIngredient)
                : null,
            'ingredients' => $lowIngredients->map(fn (Ingredient $row) => $this->ingredientRow($row))->values()->all(),
            'affected_products' => $this->affectedProducts($tenantId, $lowIngredients->pluck('id')->all()),
            'generated_at' => now()->format('Y-m-d h:i A'),
        ];

        try {
            $message = Mail::to($to);

            $cc = $this->parseAddressList($setting?->cc_addresses);
            $bcc = $this->parseAddressList($setting?->bcc_addresses);

            if (count($cc)) {
                $message->cc($cc);
            }

            if (count($bcc)) {
                $message->bcc($bcc);
            }

            $message->send(new LowStockAlertMail($payload));
        } catch (\Throwable $e) {
            Log::warning('Low stock alert email failed', [
                'tenant_id' => $tenantId,
                'ingredient_id' => $triggeredIngredient?->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function crossedIntoLowStock(Ingredient $ingredient, ?float $previousStock, ?float $previousAlertQuantity = null): bool
    {
        $currentStock = (float) $ingredient->current_stock;
        $alertQuantity = (float) $ingredient->alert_quantity;

        if ($alertQuantity <= 0 || $currentStock > $alertQuantity) {
            return false;
        }

        if ($previousStock === null) {
            return true;
        }

        $oldAlertQuantity = $previousAlertQuantity ?? $alertQuantity;
        $wasLow = $oldAlertQuantity > 0 && $previousStock <= $oldAlertQuantity;

        return ! $wasLow;
    }

    private function ingredientRow(Ingredient $ingredient): array
    {
        $currentStock = (float) $ingredient->current_stock;
        $alertQuantity = (float) $ingredient->alert_quantity;
        $unit = $ingredient->unit?->symbol ?: $ingredient->unit?->name ?: '';

        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'current_stock' => round($currentStock, 3),
            'alert_quantity' => round($alertQuantity, 3),
            'shortage' => round(max(0, $alertQuantity - $currentStock), 3),
            'unit' => $unit,
        ];
    }

    private function affectedProducts(int $tenantId, array $ingredientIds): array
    {
        if (! count($ingredientIds)) {
            return [];
        }

        $rows = ProductIngredient::query()
            ->with([
                'product:id,tenant_id,name,is_active',
                'product.ingredients.ingredient:id,tenant_id,name,current_stock,alert_quantity,unit_id',
                'product.ingredients.ingredient.unit:id,name,symbol',
            ])
            ->whereIn('ingredient_id', $ingredientIds)
            ->whereHas('product', fn ($query) => $query
                ->where('tenant_id', $tenantId)
                ->where('is_active', true))
            ->get();

        return $rows
            ->pluck('product')
            ->filter()
            ->unique('id')
            ->map(fn ($product) => $this->productAvailabilityRow($product))
            ->sortBy('can_make')
            ->values()
            ->all();
    }

    private function productAvailabilityRow($product): array
    {
        $limits = [];

        foreach ($product->ingredients as $recipeRow) {
            $ingredient = $recipeRow->ingredient;

            if (! $ingredient) {
                continue;
            }

            $required = $this->recipeQuantityPerProduct($recipeRow);

            if ($required <= 0) {
                continue;
            }

            $canMake = (int) floor(max(0, (float) $ingredient->current_stock) / $required);
            $unit = $ingredient->unit?->symbol ?: $ingredient->unit?->name ?: '';

            $limits[] = [
                'ingredient' => $ingredient->name,
                'current_stock' => round((float) $ingredient->current_stock, 3),
                'alert_quantity' => round((float) $ingredient->alert_quantity, 3),
                'required_per_product' => round($required, 4),
                'can_make' => $canMake,
                'unit' => $unit,
                'is_low' => (float) $ingredient->alert_quantity > 0
                    && (float) $ingredient->current_stock <= (float) $ingredient->alert_quantity,
            ];
        }

        $canMake = count($limits) ? min(array_column($limits, 'can_make')) : null;
        $limiting = collect($limits)->sortBy('can_make')->first();

        return [
            'name' => $product->name,
            'can_make' => $canMake,
            'limiting_ingredient' => $limiting['ingredient'] ?? '-',
            'ingredients' => $limits,
        ];
    }

    private function recipeQuantityPerProduct(ProductIngredient $recipeRow): float
    {
        $baseQuantity = (float) $recipeRow->quantity;
        $lossQuantity = $baseQuantity * ((float) $recipeRow->loss_pct / 100);

        return round($baseQuantity + $lossQuantity, 4);
    }

    private function parseAddressList(?string $value): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
