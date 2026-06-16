<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OptionTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'select' => 'Select',
        'multiple_select' => 'Multiple Select',
        'checkbox' => 'Checkbox',
        'radio_button' => 'Radio Button',
        'date' => 'Date',
        'time' => 'Time',
    ];

    public const PRICE_TYPES = [
        'fixed' => 'Fixed',
        'percentage' => 'Percentage',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_ids',
        'name',
        'type',
        'is_required',
        'base_price',
        'secondary_price',
        'price_type',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'base_price' => 'decimal:3',
        'secondary_price' => 'decimal:3',
        'branch_ids' => 'array',
    ];

    public function branches()
    {
        return Branch::whereIn('id', $this->branch_ids ?? [])->get();
    }

    public function values()
    {
        return $this->hasMany(OptionTemplateValue::class)->orderBy('sort_order');
    }
}