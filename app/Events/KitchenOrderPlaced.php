<?php

namespace App\Events;

use App\Models\PosKitchenTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KitchenOrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PosKitchenTicket $ticket)
    {
        $this->ticket->loadMissing([
            'items.options',
            'register:id,name,branch_id',
            'register.branch:id,name',
            'table:id,name',
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('tenant.' . $this->ticket->tenant_id . '.kitchen');
    }

    public function broadcastAs(): string
    {
        return 'kitchen.order.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->ticket->id,
                'uuid' => $this->ticket->uuid,
                'tenant_id' => $this->ticket->tenant_id,
                'branch_id' => $this->ticket->branch_id,
                'channel' => $this->ticket->channel,
                'status' => $this->ticket->status,
                'payment_status' => $this->ticket->payment_status,
                'customer_name' => $this->ticket->customer_name,
                'waiter_name' => $this->ticket->waiter_name,
                'sent_to_kitchen_at' => optional($this->ticket->sent_to_kitchen_at)->toISOString(),
                'created_at' => optional($this->ticket->created_at)->toISOString(),
                'table' => $this->ticket->table ? [
                    'id' => $this->ticket->table->id,
                    'name' => $this->ticket->table->name,
                ] : null,
                'register' => $this->ticket->register ? [
                    'id' => $this->ticket->register->id,
                    'name' => $this->ticket->register->name,
                    'branch_id' => $this->ticket->register->branch_id,
                    'branch' => $this->ticket->register->branch ? [
                        'id' => $this->ticket->register->branch->id,
                        'name' => $this->ticket->register->branch->name,
                    ] : null,
                ] : null,
                'items' => $this->ticket->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'qty' => $item->qty,
                    'status' => $item->status ?: 'pending',
                    'options' => $item->options->map(fn ($option) => [
                        'id' => $option->id,
                        'option_name' => $option->option_name,
                        'value_label' => $option->value_label,
                        'value_input' => $option->value_input,
                    ])->values(),
                ])->values(),
            ],
        ];
    }
}
