<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_name',
        'page_title',
        'page_subtitle',
        'contact_box_title',
        'contact_phone',
        'contact_email',
        'contact_address',
        'contact_whatsapp',
        'contact_working_hours',
        'contact_note',
        'map_iframe',
    ];
}