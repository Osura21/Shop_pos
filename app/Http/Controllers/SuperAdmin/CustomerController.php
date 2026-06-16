<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/Customers/Index');
    }

    public function getData(Request $request)
    {
        $search = trim((string) $request->input('search.value', ''));

        $query = Customer::query()
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->editColumn('status', fn(Customer $c) => $c->status ?? 'active')
            ->editColumn('created_at', fn(Customer $c) => optional($c->created_at)->format('Y-m-d H:i'))
            ->toJson();
    }

    public function toggleStatus(Customer $customer)
    {
        $current = strtolower((string) ($customer->status ?? 'active'));
        $customer->status = $current === 'active' ? 'inactive' : 'active';
        $customer->save();

        return back()->with('success', 'Customer status updated.');
    }

    public function view(Customer $customer)
    {
        $customer->load([
            'vehicles' => function ($query) {
                $query->latest()->with([
                    'manufacture:id,title',
                    'vehicleModel:id,title',
                ]);
            },
            'defaultPlan:id,subscription_name,subscription_plan_code,price,billing_interval,status',
            'subscribedPlan:id,subscription_name,subscription_plan_code,price,billing_interval,status',
        ]);

        $availablePlans = SubscriptionPlan::query()
            ->where('status', 'active')
            ->orderByRaw('CASE WHEN is_default = 1 THEN 0 ELSE 1 END')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get([
                'id',
                'subscription_name',
                'subscription_plan_code',
                'price',
                'billing_interval',
                'is_default',
                'status',
            ]);

        $effectivePlan = $customer->subscribedPlan ?: $customer->defaultPlan;

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status ?? 'active',
                'dob' => $customer->dob,
                'gender' => $customer->gender,
                'location' => $customer->location,
                'c_job' => $customer->c_job,
                'created_at' => optional($customer->created_at)->format('M d, Y, h:i A'),

                'default_subscribed_plan_id' => $customer->default_subscribed_plan,
                'subscribed_plan_id' => $customer->subscribed_plan,

                'default_subscription_plan' => $customer->defaultPlan ? [
                    'id' => $customer->defaultPlan->id,
                    'subscription_name' => $customer->defaultPlan->subscription_name,
                    'subscription_plan_code' => $customer->defaultPlan->subscription_plan_code,
                    'price' => $customer->defaultPlan->price,
                    'billing_interval' => $customer->defaultPlan->billing_interval,
                    'status' => $customer->defaultPlan->status,
                ] : null,

                'subscribed_subscription_plan' => $customer->subscribedPlan ? [
                    'id' => $customer->subscribedPlan->id,
                    'subscription_name' => $customer->subscribedPlan->subscription_name,
                    'subscription_plan_code' => $customer->subscribedPlan->subscription_plan_code,
                    'price' => $customer->subscribedPlan->price,
                    'billing_interval' => $customer->subscribedPlan->billing_interval,
                    'status' => $customer->subscribedPlan->status,
                ] : null,

                'effective_subscription_plan' => $effectivePlan ? [
                    'id' => $effectivePlan->id,
                    'subscription_name' => $effectivePlan->subscription_name,
                    'subscription_plan_code' => $effectivePlan->subscription_plan_code,
                    'price' => $effectivePlan->price,
                    'billing_interval' => $effectivePlan->billing_interval,
                    'status' => $effectivePlan->status,
                ] : null,

                'available_subscription_plans' => $availablePlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'subscription_name' => $plan->subscription_name,
                        'subscription_plan_code' => $plan->subscription_plan_code,
                        'price' => $plan->price,
                        'billing_interval' => $plan->billing_interval,
                        'is_default' => (int) $plan->is_default,
                        'status' => $plan->status,
                    ];
                })->values(),

                'vehicles' => $customer->vehicles->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'title' => trim(($vehicle->manufacture?->title ?? '') . ' ' . ($vehicle->vehicleModel?->title ?? '')),
                        'year' => $vehicle->year,
                        'price' => $vehicle->price,
                        'status' => $vehicle->status,
                        'created_at' => optional($vehicle->created_at)->format('M d, Y'),
                    ];
                })->values(),
            ],
        ]);
    }


    public function updateSubscription(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'subscribed_plan' => ['nullable', 'exists:subscription_plans,id'],
        ]);

        $defaultPlanId = SubscriptionPlan::query()
            ->where('is_default', 1)
            ->value('id');

        if ($defaultPlanId) {
            $customer->default_subscribed_plan = $defaultPlanId;
        }

        $selectedPlanId = $validated['subscribed_plan'] ?? null;

        // if selected plan is same as default, clear override
        $customer->subscribed_plan = ($selectedPlanId && (int) $selectedPlanId !== (int) $defaultPlanId)
            ? $selectedPlanId
            : null;

        $customer->save();

        return $this->view($customer->fresh());
    }
    public function destroy(Customer $customer)
    {
        DB::transaction(function () use ($customer) {
            // delete all customer vehicles first
            $customer->vehicles()->delete();

            // then delete customer
            $customer->delete();
        });

        return back()->with('success', 'Customer and related ads deleted successfully.');
    }
}