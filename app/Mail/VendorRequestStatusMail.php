<?php

namespace App\Mail;

use App\Models\RequestedVendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorRequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public RequestedVendor $vendor;
    public string $statusUrl;
    public array $approvalData;

    public function __construct(RequestedVendor $vendor, string $statusUrl, array $approvalData = [])
    {
        $this->vendor = $vendor;
        $this->statusUrl = $statusUrl;
        $this->approvalData = $approvalData;
    }

    public function build()
    {
        return $this->subject($this->subjectLine())
            ->view('emails.vendor-request-status')
            ->with([
                'vendor' => $this->vendor,
                'statusUrl' => $this->statusUrl,
                'approvalData' => $this->approvalData,
                'heading' => $this->heading(),
                'messageText' => $this->messageText(),
                'buttonText' => $this->buttonText(),
            ]);
    }

    protected function subjectLine(): string
    {
        return match ($this->vendor->status) {
            'approved' => 'Autosale.lk - Seller Request Approved',
            'rejected' => 'Autosale.lk - Seller Request Rejected',
            default => 'Autosale.lk - Seller Request Received',
        };
    }

    protected function heading(): string
    {
        return match ($this->vendor->status) {
            'approved' => 'Your seller request has been approved',
            'rejected' => 'Your seller request has been rejected',
            default => 'Your seller request is pending review',
        };
    }

    protected function messageText(): string
    {
        return match ($this->vendor->status) {
            'approved' => 'Your seller registration request has been approved. Your vendor account has been created. Please review the account details below and keep your password secure.',
            'rejected' => 'Your seller registration request has been rejected. Please use the link below to view the latest request status and rejection reason.',
            default => 'We have received your seller registration request successfully. Your request is now pending review. Please use the link below to track the status.',
        };
    }

    protected function buttonText(): string
    {
        return match ($this->vendor->status) {
            'approved' => 'View Approved Request',
            'rejected' => 'View Rejected Request',
            default => 'View Pending Request',
        };
    }
}