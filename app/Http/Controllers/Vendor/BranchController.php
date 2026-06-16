<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    private const ORDER_TYPE_OPTIONS = [
        'takeaway'   => 'Takeaway',
        'dine_in'    => 'Dine-In',
        'pickup'     => 'Pick-up',
        'drive_thru' => 'Drive-Thru',
        'pre_order'  => 'Pre-Order',
        'catering'   => 'Catering',
    ];

    private const PAYMENT_METHOD_OPTIONS = [
        'cash'          => 'Cash',
        'card'          => 'Card',
        'credit'        => 'Credit',
        'bank_transfer' => 'Bank Transfer',
        'mobile_wallet' => 'Mobile Wallet',
        'gift_card'     => 'Gift Card',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Branch/Index');
    }

    public function getData(Request $request)
{
    $branches = Branch::query()
        ->where('tenant_id', $this->tenantId())
        ->select('branches.*');

    return DataTables::of($branches)
        ->filter(function ($query) use ($request) {
            $search = $request->input('search.value');

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('branches.name', 'like', "%{$search}%")
                      ->orWhere('branches.email', 'like', "%{$search}%")
                      ->orWhere('branches.city', 'like', "%{$search}%");
                });
            }
        })
        ->addColumn('full_address', function ($row) {
            return $this->branchAddress($row);
        })
        ->addColumn('contact_summary', function ($row) {
            return collect([$row->phone, $row->email])
                ->filter(fn ($value) => filled($value))
                ->implode(' | ');
        })
        ->addColumn('regional_summary', function ($row) {
            return collect([
                $row->currency ?: null,
                $row->timezone ?: 'UTC',
            ])->filter(fn ($value) => filled($value))
              ->implode(' | ');
        })
        ->addColumn('status', function ($row) {

    $isActive = (int) $row->is_active === 1;

    if ($isActive) {
        return '
            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-check-circle-fill"></i>
                Active
            </span>
        ';
    }

    return '
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-x-circle-fill text-danger"></i>
            Inactive
        </span>
    ';
})
        ->editColumn('created_at', function ($row) {
            return optional($row->created_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
        })
        ->editColumn('updated_at', function ($row) {
            return optional($row->updated_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
        })
        ->rawColumns(['status'])
        ->make(true);
}

    private function branchAddress($branch): string
    {
        return collect([
            $branch->address_line_1,
            $branch->address_line_2,
            $branch->city,
            $branch->state,
            $branch->postal_code,
            $branch->country,
        ])->filter(fn ($value) => filled($value))->implode(', ');
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Branch/CreateUpdate', [
            'branch' => null,
            'currencies' => $this->currencies(),
            'timezones' => timezone_identifiers_list(),
            'orderTypeOptions' => self::ORDER_TYPE_OPTIONS,
            'paymentMethodOptions' => self::PAYMENT_METHOD_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $branch = new Branch();
            $branch->tenant_id = $this->tenantId();
            $branch->name = $validated['name'];
            $branch->legal_name = $validated['legal_name'] ?? null;
            $branch->phone = $validated['phone'] ?? null;
            $branch->email = $validated['email'] ?? null;
            $branch->is_active = (bool) ($validated['is_active'] ?? false);

            $branch->registration_number = $validated['registration_number'] ?? null;
            $branch->vat_tin = $validated['vat_tin'] ?? null;

            $branch->currency = $validated['currency'] ?? null;
            $branch->timezone = $validated['timezone'] ?? 'UTC';

            $branch->country = $validated['country'] ?? null;
            $branch->postal_code = $validated['postal_code'] ?? null;
            $branch->address_line_1 = $validated['address_line_1'] ?? null;
            $branch->address_line_2 = $validated['address_line_2'] ?? null;
            $branch->city = $validated['city'] ?? null;
            $branch->state = $validated['state'] ?? null;

            $branch->latitude = $this->nullableNumber($validated['latitude'] ?? null);
            $branch->longitude = $this->nullableNumber($validated['longitude'] ?? null);

            $branch->order_types = array_values($validated['order_types'] ?? []);
            $branch->payment_methods = array_values($validated['payment_methods'] ?? []);

            // not null in db, so force default 0
            $branch->cash_difference_threshold = $this->nullableNumber($validated['cash_difference_threshold'] ?? 0) ?? 0;

            $branch->quick_pay_amount_1 = $this->nullableNumber($validated['quick_pay_amount_1'] ?? null);
            $branch->quick_pay_amount_2 = $this->nullableNumber($validated['quick_pay_amount_2'] ?? null);
            $branch->quick_pay_amount_3 = $this->nullableNumber($validated['quick_pay_amount_3'] ?? null);
            $branch->quick_pay_amount_4 = $this->nullableNumber($validated['quick_pay_amount_4'] ?? null);
            $branch->quick_pay_amount_5 = $this->nullableNumber($validated['quick_pay_amount_5'] ?? null);
            $branch->quick_pay_amount_6 = $this->nullableNumber($validated['quick_pay_amount_6'] ?? null);

            $branch->save();

            DB::commit();

            return redirect()
                ->route('vendor.branches.index')
                ->with('success', 'Branch created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Branch store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Unable to save branch. Please check the form fields and database column settings.',
                ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Branch store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while creating the branch.',
                ]);
        }
    }

    public function edit($id)
    {
        $branch = Branch::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Branch/CreateUpdate', [
            'branch' => $branch,
            'currencies' => $this->currencies(),
            'timezones' => timezone_identifiers_list(),
            'orderTypeOptions' => self::ORDER_TYPE_OPTIONS,
            'paymentMethodOptions' => self::PAYMENT_METHOD_OPTIONS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::where('tenant_id', $this->tenantId())->findOrFail($id);

        $validated = $request->validate($this->rules($branch->id), $this->messages());

        try {
            DB::beginTransaction();

            $branch->name = $validated['name'];
            $branch->legal_name = $validated['legal_name'] ?? null;
            $branch->phone = $validated['phone'] ?? null;
            $branch->email = $validated['email'] ?? null;
            $branch->is_active = (bool) ($validated['is_active'] ?? false);

            $branch->registration_number = $validated['registration_number'] ?? null;
            $branch->vat_tin = $validated['vat_tin'] ?? null;

            $branch->currency = $validated['currency'] ?? null;
            $branch->timezone = $validated['timezone'] ?? 'UTC';

            $branch->country = $validated['country'] ?? null;
            $branch->postal_code = $validated['postal_code'] ?? null;
            $branch->address_line_1 = $validated['address_line_1'] ?? null;
            $branch->address_line_2 = $validated['address_line_2'] ?? null;
            $branch->city = $validated['city'] ?? null;
            $branch->state = $validated['state'] ?? null;

            $branch->latitude = $this->nullableNumber($validated['latitude'] ?? null);
            $branch->longitude = $this->nullableNumber($validated['longitude'] ?? null);

            $branch->order_types = array_values($validated['order_types'] ?? []);
            $branch->payment_methods = array_values($validated['payment_methods'] ?? []);

            $branch->cash_difference_threshold = $this->nullableNumber($validated['cash_difference_threshold'] ?? 0) ?? 0;

            $branch->quick_pay_amount_1 = $this->nullableNumber($validated['quick_pay_amount_1'] ?? null);
            $branch->quick_pay_amount_2 = $this->nullableNumber($validated['quick_pay_amount_2'] ?? null);
            $branch->quick_pay_amount_3 = $this->nullableNumber($validated['quick_pay_amount_3'] ?? null);
            $branch->quick_pay_amount_4 = $this->nullableNumber($validated['quick_pay_amount_4'] ?? null);
            $branch->quick_pay_amount_5 = $this->nullableNumber($validated['quick_pay_amount_5'] ?? null);
            $branch->quick_pay_amount_6 = $this->nullableNumber($validated['quick_pay_amount_6'] ?? null);

            $branch->save();

            DB::commit();

            return redirect()
                ->route('vendor.branches.index')
                ->with('success', 'Branch updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Branch update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Unable to update branch. Please check the form fields and database column settings.',
                ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Branch update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while updating the branch.',
                ]);
        }
    }

    public function destroy($id)
    {
        try {
            $branch = Branch::where('tenant_id', $this->tenantId())->findOrFail($id);
            $branch->delete();

            return redirect()
                ->route('vendor.branches.index')
                ->with('success', 'Branch deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Branch delete failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete branch.',
            ]);
        }
    }

    private function rules($branchId = null): array
    {
        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('branches', 'name')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($branchId)
            ],
            'legal_name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('branches', 'legal_name')
                    ->where('tenant_id', $this->tenantId())
                    ->ignore($branchId)
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('branches', 'email')
                    ->where(function ($query) {
                        return $query->where('tenant_id', $this->tenantId());
                    })
                    ->ignore($branchId),
            ],
            'is_active' => ['nullable', 'boolean'],

            'registration_number' => ['nullable', 'string', 'max:100'],
            'vat_tin' => ['nullable', 'string', 'max:100'],

            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['required', 'timezone'],

            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'order_types' => ['nullable', 'array'],
            'order_types.*' => ['string', Rule::in(array_keys(self::ORDER_TYPE_OPTIONS))],

            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', Rule::in(array_keys(self::PAYMENT_METHOD_OPTIONS))],

            'cash_difference_threshold' => ['required', 'numeric', 'min:0'],

            'quick_pay_amount_1' => ['nullable', 'numeric', 'min:0'],
            'quick_pay_amount_2' => ['nullable', 'numeric', 'min:0'],
            'quick_pay_amount_3' => ['nullable', 'numeric', 'min:0'],
            'quick_pay_amount_4' => ['nullable', 'numeric', 'min:0'],
            'quick_pay_amount_5' => ['nullable', 'numeric', 'min:0'],
            'quick_pay_amount_6' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Branch name is required.',
            'name.max' => 'Branch name may not be greater than 255 characters.',
            'name.unique' => 'This branch name is already used for another branch.',

            'legal_name.required' => 'Legal name is required.',
            'legal_name.max' => 'Legal name may not be greater than 255 characters.',
            'legal_name.unique' => 'This legal name is already used for another branch.',

            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email may not be greater than 255 characters.',
            'email.unique' => 'This email is already used for another branch.',
            'timezone.required' => 'Timezone is required.',
            'timezone.timezone' => 'Please select a valid timezone.',

            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90.',

            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180.',

            'cash_difference_threshold.required' => 'Cash difference threshold is required.',
            'cash_difference_threshold.numeric' => 'Cash difference threshold must be a number.',
            'cash_difference_threshold.min' => 'Cash difference threshold cannot be negative.',

            'quick_pay_amount_1.numeric' => 'Quick Pay Amount 1 must be a number.',
            'quick_pay_amount_1.min' => 'Quick Pay Amount 1 cannot be negative.',

            'quick_pay_amount_2.numeric' => 'Quick Pay Amount 2 must be a number.',
            'quick_pay_amount_2.min' => 'Quick Pay Amount 2 cannot be negative.',

            'quick_pay_amount_3.numeric' => 'Quick Pay Amount 3 must be a number.',
            'quick_pay_amount_3.min' => 'Quick Pay Amount 3 cannot be negative.',

            'quick_pay_amount_4.numeric' => 'Quick Pay Amount 4 must be a number.',
            'quick_pay_amount_4.min' => 'Quick Pay Amount 4 cannot be negative.',

            'quick_pay_amount_5.numeric' => 'Quick Pay Amount 5 must be a number.',
            'quick_pay_amount_5.min' => 'Quick Pay Amount 5 cannot be negative.',

            'quick_pay_amount_6.numeric' => 'Quick Pay Amount 6 must be a number.',
            'quick_pay_amount_6.min' => 'Quick Pay Amount 6 cannot be negative.',
        ];
    }

    private function nullableNumber($value)
    {
        return ($value === '' || $value === null) ? null : $value;
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }

    private function currencies(): array
    {
        return [
            ['code' => 'USD', 'name' => 'US Dollar'],
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'GBP', 'name' => 'British Pound'],
            ['code' => 'LKR', 'name' => 'Sri Lankan Rupee'],
            ['code' => 'AED', 'name' => 'UAE Dirham'],
            ['code' => 'SAR', 'name' => 'Saudi Riyal'],
            ['code' => 'INR', 'name' => 'Indian Rupee'],
        ];
    }
}
