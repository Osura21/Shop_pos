<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_name',

        'section1_title',
        'section1_description',
        'section1_image',

        'section2_title',
        'section2_description',
        'section2_image_1',
        'section2_image_2',
        'section2_image_3',

        'section3_title',
        'section3_description',
        'section3_image',
    ];
}