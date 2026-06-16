<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCreditPayment;
use App\Models\PosInvoice;
use App\Models\PosTransaction;
use App\Models\PosTransactionPayment;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    private const CUSTOMER_TYPE_OPTIONS = [
        'regular' => 'Regular',
        'business' => 'Business',
        'vip' => 'VIP',
    ];

    private const GENDER_OPTIONS = [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'prefer_not_to_say' => 'Prefer not to say',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Customer/Index');
    }

  public function getData(Request $request)
{
    $customers = Customer::query()
        ->where('tenant_id', $this->tenantId())
        ->select('customers.*');

    return DataTables::of($customers)
        ->filter(function ($query) use ($request) {
            $search = $request->input('search.value');

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('customer_type', 'like', "%{$search}%");
                });
            }
        })

        ->addColumn('avatar_url', fn ($row) => $row->avatar_url)

        ->addColumn('customer_type_label', function ($row) {
            return self::CUSTOMER_TYPE_OPTIONS[$row->customer_type]
                ?? ucfirst((string) $row->customer_type);
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

        ->rawColumns(['status', 'avatar_url'])
        ->make(true);
}
    public function create()
    {
        return Inertia::render('VendorAdmin/Customer/CreateUpdate', [
            'customer' => null,
            'customerTypeOptions' => self::CUSTOMER_TYPE_OPTIONS,
            'genderOptions' => self::GENDER_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->boolean('quick_create')) {
            $request->merge([
                'customer_type' => $request->input('customer_type', 'regular'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        }

        $validated = $request->validate($this->rules(null, $request->boolean('quick_create')), $this->messages());

        try {
            DB::beginTransaction();

            $customer = new Customer();
            $customer->tenant_id = $this->tenantId();

            $this->fillCustomer($customer, $validated);
            $customer->save();

            if ($request->hasFile('avatar')) {
                $customer->addMedia($request->file('avatar'))
                    ->toMediaCollection('Customer_Images');
            }

            DB::commit();

            if ($request->boolean('quick_create') || $request->expectsJson()) {
                return response()->json([
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                    ],
                    'message' => 'Customer created successfully.',
                ]);
            }

            return redirect()
                ->route('vendor.customers.index')
                ->with('success', 'Customer created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Customer store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->except('password', 'password_confirmation'),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Unable to save customer. Please check the form fields and database column settings.',
                ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Customer store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->except('password', 'password_confirmation'),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while creating the customer.',
                ]);
        }
    }

    public function edit($id)
    {
        $customer = Customer::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Customer/CreateUpdate', [
            'customer' => $customer,
            'customerTypeOptions' => self::CUSTOMER_TYPE_OPTIONS,
            'genderOptions' => self::GENDER_OPTIONS,
        ]);
    }

    public function view(Customer $customer)
    {
        abort_unless((int) $customer->tenant_id === (int) $this->tenantId(), 404);

        $customer->loadMissing('loyaltyAccounts.tier');

        $transactionsQuery = PosTransaction::query()
            ->where('tenant_id', $this->tenantId())
            ->where('customer_id', $customer->id);

        $creditSalesQuery = PosTransactionPayment::query()
            ->where('payment_method', 'credit')
            ->whereHas('transaction', function ($query) use ($customer) {
                $query->where('tenant_id', $this->tenantId())
                    ->where('customer_id', $customer->id);
            });

        $creditPaymentsQuery = CustomerCreditPayment::query()
            ->where('tenant_id', $this->tenantId())
            ->where('customer_id', $customer->id);

        $receiptsQuery = PosInvoice::query()
            ->where('tenant_id', $this->tenantId())
            ->where('customer_id', $customer->id);

        $creditSalesTotal = (float) (clone $creditSalesQuery)->sum('amount');
        $creditPaymentsTotal = (float) (clone $creditPaymentsQuery)->sum('amount');
        $outstandingCredit = max(0, $creditSalesTotal - $creditPaymentsTotal);

        $recentCreditSales = (clone $creditSalesQuery)
            ->with(['transaction.register.branch'])
            ->latest()
            ->take(10)
            ->get();

        $invoiceMap = PosInvoice::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('pos_transaction_id', $recentCreditSales->pluck('pos_transaction_id')->filter()->all())
            ->get()
            ->keyBy('pos_transaction_id');

        $recentCreditPayments = (clone $creditPaymentsQuery)
            ->latest('received_at')
            ->take(10)
            ->get();

        $receipts = (clone $receiptsQuery)
            ->latest('issued_at')
            ->take(12)
            ->get();

        $loyaltyAccount = $customer->loyaltyAccounts->sortByDesc('points_balance')->first();

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'username' => $customer->username,
                'customer_type' => $customer->customer_type,
                'birthdate' => optional($customer->birthdate)->format('Y-m-d'),
                'gender' => $customer->gender,
                'note' => $customer->note,
                'registration_number' => $customer->registration_number,
                'vat_tin' => $customer->vat_tin,
                'avatar_url' => $customer->avatar_url,
                'is_active' => (bool) $customer->is_active,
                'created_at' => optional($customer->created_at)->format('Y-m-d h:i A'),
                'edit_url' => route('vendor.customers.edit', $customer->id),
            ],
            'stats' => [
                'lifetime_sales_total' => (float) (clone $transactionsQuery)->sum('grand_total'),
                'orders_count' => (int) (clone $transactionsQuery)->count(),
                'receipts_count' => (int) (clone $receiptsQuery)->count(),
                'credit_sales_total' => $creditSalesTotal,
                'credit_payments_total' => $creditPaymentsTotal,
                'outstanding_credit' => $outstandingCredit,
                'last_sale_at' => optional((clone $transactionsQuery)->latest('paid_at')->first()?->paid_at)->format('Y-m-d h:i A'),
                'loyalty_points' => (int) ($loyaltyAccount?->points_balance ?? 0),
                'loyalty_tier' => $loyaltyAccount?->tier?->name ?: null,
            ],
            'credit_sales' => $recentCreditSales->map(function ($payment) use ($invoiceMap) {
                $transaction = $payment->transaction;
                $invoice = $invoiceMap->get($payment->pos_transaction_id);

                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'transaction_id' => $transaction?->id,
                    'transaction_uuid' => $transaction?->uuid,
                    'invoice_id' => $invoice?->id,
                    'invoice_no' => $invoice?->invoice_no,
                    'branch_name' => $transaction?->register?->branch?->name,
                    'paid_at' => optional($transaction?->paid_at)->format('Y-m-d h:i A'),
                ];
            })->values(),
            'credit_payments' => $recentCreditPayments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'receipt_no' => $payment->receipt_no,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference' => $payment->reference,
                    'notes' => $payment->notes,
                    'received_at' => optional($payment->received_at)->format('Y-m-d h:i A'),
                    'print_url' => route('vendor.pos.customer-credit-payments.print', ['payment' => $payment->id, 'print' => 1]),
                ];
            })->values(),
            'receipts' => $receipts->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'total' => (float) $invoice->total,
                    'status' => $invoice->status,
                    'issued_at' => optional($invoice->issued_at)->format('Y-m-d h:i A'),
                    'print_url' => route('vendor.sales.invoices.print', $invoice->id),
                ];
            })->values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::where('tenant_id', $this->tenantId())->findOrFail($id);

        $validated = $request->validate($this->rules($customer->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillCustomer($customer, $validated);
            $customer->save();

            if ($request->boolean('remove_avatar')) {
                $customer->clearMediaCollection('Customer_Images');
            }

            if ($request->hasFile('avatar')) {
                $customer->clearMediaCollection('Customer_Images');

                $customer->addMedia($request->file('avatar'))
                    ->toMediaCollection('Customer_Images');
            }

            DB::commit();

            return redirect()
                ->route('vendor.customers.index')
                ->with('success', 'Customer updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Customer update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->except('password', 'password_confirmation'),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Unable to update customer. Please check the form fields and database column settings.',
                ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Customer update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->except('password', 'password_confirmation'),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while updating the customer.',
                ]);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::where('tenant_id', $this->tenantId())->findOrFail($id);
            $customer->clearMediaCollection('Customer_Images');
            $customer->delete();

            return redirect()
                ->route('vendor.customers.index')
                ->with('success', 'Customer deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Customer delete failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete customer.',
            ]);
        }
    }

    private function fillCustomer(Customer $customer, array $validated): void
    {
        $customer->name = $validated['name'];
        $customer->customer_type = $validated['customer_type'];
        $customer->phone = $validated['phone'] ?? null;
        $customer->birthdate = $validated['birthdate'] ?? null;
        $customer->gender = $validated['gender'] ?? null;
        $customer->is_active = (bool) ($validated['is_active'] ?? false);

        $customer->username = $validated['username'] ?? null;
        $customer->email = $validated['email'] ?? null;
        $customer->note = $validated['note'] ?? null;

        if (!empty($validated['password'])) {
            $customer->password = $validated['password'];
        }

        $customer->registration_number = $validated['registration_number'] ?? null;
        $customer->vat_tin = $validated['vat_tin'] ?? null;
    }

    private function rules($customerId = null, bool $quickCreate = false): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:/^[0-9]+$/'],
            'phone_country' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'customer_type' => ['required', 'string', Rule::in(array_keys(self::CUSTOMER_TYPE_OPTIONS))],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', Rule::in(array_keys(self::GENDER_OPTIONS))],
            'is_active' => ['nullable', 'boolean'],

            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('customers', 'username')
                    ->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))
                    ->ignore($customerId),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))
                    ->ignore($customerId),
            ],

            'note' => ['nullable', 'string'],

            'quick_create' => ['nullable', 'boolean'],

            'password' => $quickCreate
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ($customerId
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed']),

            'registration_number' => ['nullable', 'string', 'max:100'],
            'vat_tin' => ['nullable', 'string', 'max:100'],

            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'name.not_regex' => 'The name cannot contain only numbers.',
            'phone_country.regex' => 'The phone country must be a valid country name and cannot contain numbers.',
            'customer_type.required' => 'Customer type is required.',
            'customer_type.in' => 'Please select a valid customer type.',
            'phone.max' => 'Phone number may not be greater than 50 characters.',
            'birthdate.date' => 'Please enter a valid birthdate.',
            'birthdate.before_or_equal' => 'Birthdate cannot be in the future.',
            'gender.in' => 'Please select a valid gender.',
            'username.unique' => 'This username is already used for another customer.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already used for another customer.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'avatar.image' => 'Avatar must be an image.',
            'avatar.mimes' => 'Avatar must be JPG, JPEG, PNG, or WEBP.',
            'avatar.max' => 'Avatar may not be greater than 5MB.',
        ];
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
