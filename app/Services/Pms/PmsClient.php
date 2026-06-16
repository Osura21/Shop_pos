<?php

namespace App\Services\Pms;

use App\Models\PmsIntegrationSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PmsClient
{
    public function __construct(private readonly PmsIntegrationSetting $setting)
    {
    }

    public function getCustomers(): array
    {
        return $this->get('/api/pos/v1/customers');
    }

    public function getRooms(): array
    {
        return $this->get('/api/pos/v1/rooms');
    }

    public function getCheckedInGuests(): array
    {
        return $this->get('/api/pos/v1/checked-in-guests');
    }

    public function postRoomCharge(array $payload): array
    {
        return $this->request()
            ->post('/api/pos/v1/room-charge', $payload)
            ->throw()
            ->json() ?? [];
    }

    public function postOrder(array $payload): array
    {
        return $this->request()
            ->post('/api/pos/v1/order', $payload)
            ->throw()
            ->json() ?? [];
    }

    public function getBooking(string|int $bookingId): array
    {
        return $this->get('/api/bookings/' . $bookingId);
    }

    private function get(string $uri): array
    {
        return $this->request()
            ->get($uri)
            ->throw()
            ->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->setting->pms_base_url, '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->withHeaders([
                'x-api-key' => $this->setting->pms_api_key,
            ]);
    }
}
