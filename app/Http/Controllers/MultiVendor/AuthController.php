<?php
//Multivendor/Authcontroller.php
namespace App\Http\Controllers\MultiVendor;

use App\Models\Customer;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;


class AuthController
{
    public function login()
    {
        return Inertia::render('MultiVendor/Auth/Login');
    }
    public function register()
    {

        return Inertia::render('MultiVendor/Auth/Register');
    }
    public function send(Request $request, SmsService $sms)
    {
        $request->validate([
            'phone' => 'required',
        ]);


        $otp = rand(100000, 999999);

        Cache::put(
            'otp_' . $request->phone,
            $otp,
            now()->addMinutes(5)
        );

        $message = "Your Autosale.lk login OTP is {$otp}. Valid for 5 minutes.";
        Log::info($otp);
        $sms->send($request->phone, $message);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'otp_sent',
                'message' => 'OTP sent successfully',
            ]);
        }

        return back()->with('success', 'OTP sent successfully');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'otp'   => 'required',
        ]);

        $cachedOtp = Cache::get('otp_' . $request->phone);
        // Log::info($request->all());
        // Log::info($cachedOtp);
        if (!$cachedOtp || (string)$cachedOtp !== (string)$request->otp) {

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'invalid_otp',
                    'message' => 'Your OTP is wrong. Please try again.',
                ], 422);
            }

            return back()->withErrors([
                'otp' => 'Your OTP is wrong. Please try again.',
            ]);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if ($customer) {
            Auth::guard('customer')->login($customer);
            Cache::forget('otp_' . $request->phone);
            $request->session()->regenerate();

            if ($request->wantsJson()) {
                return response()->json([
                    'status'   => 'logged_in',
                    'redirect' => route('customer.account'),
                ]);
            }

            return redirect()->route('customer.account');
        }

        // New user
        $request->session()->put('phone', $request->phone);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'need_register',
            ]);
        }

        return redirect()->route('otp.register');
    }


    public function showRegister(Request $request)
    {
        // Log::info('tesfgdfg');
        // phone comes via session
        if (!session()->has('phone')) {

            Log::info(session()->has('phone'));
            return redirect()->route('multivendor.login');
        }
        // Log::info('453553');
        return Inertia::render('MultiVendor/Auth/Register', [
            'phone' => session('phone'),
        ]);
    }

    public function storeRegister(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone'    => 'required|unique:customers,phone',
                'name'     => 'required|string|max:255',
                'email'    => 'nullable|email|unique:customers,email',
                'gender'   => 'nullable|in:male,female,other',
                'location' => 'nullable|string|max:255',
                'dob'      => 'nullable|date',
                'c_job'    => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {

            Log::error('Customer registration validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            // Preserve phone in session so showRegister doesn't redirect to login
            $request->session()->put('phone', $request->phone);

            return redirect()->route('otp.register')
                ->withErrors($e->errors())
                ->withInput();
        }

        $customer = Customer::create($validated);

        Auth::guard('customer')->login($customer);

        $request->session()->regenerate();

        return redirect()->route('customer.account');
    }


    public function makeRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'phone' => ['required', 'unique:customers,phone'],
            'country' => ['nullable', 'string', 'max:5'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'country' => $validated['country'],
            'password' => Hash::make($validated['password']),
        ]);

        // Login customer using customer guard
        Auth::guard('customer')->login($customer);

        return redirect()->route('customer.account');
    }

    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $customer = Customer::where('provider', 'google')
                ->where('provider_id', $googleUser->id)
                ->first();

            // If provider account exists
            if ($customer) {
                Auth::guard('customer')->login($customer);
                $request->session()->regenerate();
                return redirect()->route('customer.account');
            }

            // If email already exists (OTP user)
            $customer = Customer::where('email', $googleUser->email)->first();

            if ($customer) {
                $customer->update([
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);

                Auth::guard('customer')->login($customer);
                $request->session()->regenerate();
                return redirect()->route('customer.account');
            }

            // Create new customer
            $customer = Customer::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'provider' => 'google',
                'provider_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
            ]);

            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();

            return redirect()->route('customer.account');
        } catch (\Exception $e) {
            return redirect()->route('multivendor.login')
                ->with('error', 'Google login failed.');
        }
    }
    public function facebookRedirect()
    {
        return Socialite::driver('facebook')
            ->scopes(['email'])
            ->redirect();
    }

    public function facebookCallback(Request $request)
    {
        try {
            $fbUser = Socialite::driver('facebook')->stateless()->user();

            $customer = Customer::where('provider', 'facebook')
                ->where('provider_id', $fbUser->id)
                ->first();

            if ($customer) {
                Auth::guard('customer')->login($customer);
                $request->session()->regenerate();
                return redirect()->route('customer.account');
            }

            $customer = Customer::where('email', $fbUser->email)->first();

            if ($customer) {
                $customer->update([
                    'provider' => 'facebook',
                    'provider_id' => $fbUser->id,
                    'avatar' => $fbUser->avatar,
                ]);

                Auth::guard('customer')->login($customer);
                $request->session()->regenerate();
                return redirect()->route('customer.account');
            }

            $customer = Customer::create([
                'name' => $fbUser->name,
                'email' => $fbUser->email,
                'provider' => 'facebook',
                'provider_id' => $fbUser->id,
                'avatar' => $fbUser->avatar,
            ]);

            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();

            return redirect()->route('customer.account');
        } catch (\Exception $e) {
            return redirect()->route('multivendor.login')
                ->with('error', 'Facebook login failed.');
        }
    }
}
