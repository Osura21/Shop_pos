<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Models\TenantCurrencySetting;

trait ResolvesTenantContext
{
    protected function tenantId(): int
    {
        return (int) (auth('vendor')->user()?->tenant_id ?? 0);
    }

    protected function defaultBranchId(): ?int
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->value('id');
    }

    protected function currencySetting(): ?TenantCurrencySetting
    {
        return TenantCurrencySetting::query()
            ->where('tenant_id', $this->tenantId())
            ->first();
    }

    protected function baseCurrencyCode(): string
    {
        return $this->currencySetting()?->base_currency_code ?: 'LKR';
    }

    protected function baseCurrencySymbol(): string
    {
        return TenantCurrencySetting::getCurrencySymbol($this->baseCurrencyCode());
    }

    protected function secondaryCurrencyCode(): ?string
    {
        return $this->currencySetting()?->secondary_currency_code ?: null;
    }

    protected function secondaryCurrencySymbol(): ?string
    {
        $code = $this->secondaryCurrencyCode();

        if (! $code) {
            return null;
        }

        return TenantCurrencySetting::getCurrencySymbol($code);
    }

    protected function currencySymbol(?string $currencyCode): string
    {
        return TenantCurrencySetting::getCurrencySymbol($currencyCode ?: $this->baseCurrencyCode()) ?: '';
    }
}
