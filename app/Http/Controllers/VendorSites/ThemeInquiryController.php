<?php

namespace App\Http\Controllers\VendorSites;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeInquiryController extends Controller
{
    protected function tenantOrFail()
    {
        $tenant = tenant();

        abort_if(!$tenant, 404, 'Tenant not found');

        return $tenant;
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->tenantOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $data['tenant_id'] = (string) $tenant->id;

        $inquiry = Inquiry::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry saved successfully.',
            'data' => [
                'id' => $inquiry->id,
            ],
        ], 201);
    }
}