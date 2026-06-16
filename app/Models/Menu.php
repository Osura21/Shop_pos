<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_ids',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'branch_ids' => 'array',
    ];

    public function branches()
    {
        return Branch::whereIn('id', $this->branch_ids ?? [])->get();
    }

    public function onlineMenus()
    {
        return $this->hasMany(OnlineMenu::class);
    }
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}