<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:120'],
            'slug' => ['required','string','max:120','unique:tenants,slug'],
            'domain' => ['required','string','max:255','unique:domains,domain'],
            'theme_id' => ['required','exists:themes,id'],
            'admin_name' => ['required','string','max:120'],
            'admin_email' => ['required','email','max:190','unique:users,email'],
            'admin_password' => ['required','string','min:8'],
        ];
    }
}
