<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $payload
    ) {
    }

    public function build()
    {
        return $this->subject('Low stock alert - '.$this->payload['tenant_name'])
            ->view('emails.low-stock-alert')
            ->with($this->payload);
    }
}
