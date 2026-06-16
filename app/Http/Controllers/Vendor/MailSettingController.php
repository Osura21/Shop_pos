<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\VendorMailSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class MailSettingController extends Controller
{
    use ResolvesTenantContext;

    public function edit(Request $request): Response
    {
        abort_unless($request->user('vendor')?->can('settings-mail.view'), 403);

        $setting = $this->setting();

        return Inertia::render('VendorAdmin/Settings/MailSettings', [
            'setting' => [
                'active' => (bool) ($setting?->active ?? false),
                'from_address' => $setting?->from_address ?? '',
                'from_name' => $setting?->from_name ?? '',
                'mail_method' => $setting?->mail_method ?? 'smtp',
                'smtp_host' => $setting?->smtp_host ?? '',
                'smtp_port' => $setting?->smtp_port ?? '',
                'smtp_username' => $setting?->smtp_username ?? '',
                'smtp_password' => $setting?->smtp_password ?? '',
                'mail_encryption' => $setting?->mail_encryption ?? 'tls',
                'has_smtp_password' => filled($setting?->smtp_password),
            ],
            'canEdit' => $request->user('vendor')?->can('settings-mail.edit') ?? false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user('vendor')?->can('settings-mail.edit'), 403);

        $data = $this->validatedMailSettings($request);
        $this->saveSetting($data);

        return redirect()
            ->route('vendor.settings.mail')
            ->with('success', 'Mail settings updated successfully.');
    }

    public function recipients(Request $request): Response
    {
        abort_unless($request->user('vendor')?->can('settings-mail.view'), 403);

        $setting = $this->setting();

        return Inertia::render('VendorAdmin/Settings/MailRecipients', [
            'setting' => [
                'to_addresses' => $setting?->to_addresses ?? '',
                'cc_addresses' => $setting?->cc_addresses ?? '',
                'bcc_addresses' => $setting?->bcc_addresses ?? '',
            ],
            'canEdit' => $request->user('vendor')?->can('settings-mail.edit') ?? false,
        ]);
    }

    public function updateRecipients(Request $request): RedirectResponse
    {
        abort_unless($request->user('vendor')?->can('settings-mail.edit'), 403);

        $data = $this->validatedRecipients($request);

        VendorMailSetting::query()->updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            [
                'tenant_id' => $this->tenantId(),
                'to_addresses' => $this->normalizeAddressList($data['to_addresses'] ?? null),
                'cc_addresses' => $this->normalizeAddressList($data['cc_addresses'] ?? null),
                'bcc_addresses' => $this->normalizeAddressList($data['bcc_addresses'] ?? null),
            ]
        );

        return redirect()
            ->route('vendor.settings.mail.recipients')
            ->with('success', 'Mail recipient settings updated successfully.');
    }

    public function testRecipients(Request $request): RedirectResponse
    {
        abort_unless($request->user('vendor')?->can('settings-mail.edit'), 403);

        $data = $this->validatedRecipients($request, true);
        $to = $this->parseAddressList($data['to_addresses'] ?? null);
        $cc = $this->parseAddressList($data['cc_addresses'] ?? null);
        $bcc = $this->parseAddressList($data['bcc_addresses'] ?? null);

        if (! count($to)) {
            return back()->withErrors([
                'to_addresses' => 'At least one To Mail address is required to send a test.',
            ])->withInput();
        }

        $setting = $this->setting();

        if ($setting?->active) {
            $setting->applyToConfig();
        }

        try {
            Mail::raw(
                'This is a recipient test email from your restaurant POS.',
                function ($message) use ($to, $cc, $bcc) {
                    $message->to($to)->subject('Vendor mail recipients test');

                    if (count($cc)) {
                        $message->cc($cc);
                    }

                    if (count($bcc)) {
                        $message->bcc($bcc);
                    }
                }
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'to_addresses' => 'Test mail failed: '.$e->getMessage(),
            ])->withInput();
        }

        return back()->with('success', 'Recipient test mail sent successfully.');
    }

    public function test(Request $request): RedirectResponse
    {
        abort_unless($request->user('vendor')?->can('settings-mail.edit'), 403);

        $data = $this->validatedMailSettings($request, [
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        if ($request->boolean('active')) {
            $setting = $this->setting();
            $smtpPassword = filled($data['smtp_password'] ?? null)
                ? $data['smtp_password']
                : $setting?->smtp_password;

            $this->applyMailConfig($data, $smtpPassword);
        }

        try {
            Mail::raw(
                'This is a test email from your restaurant POS. Your mail settings are working.',
                function ($message) use ($data) {
                    $message = $message
                        ->to($data['test_email'])
                        ->subject('Vendor mail settings test');

                    if ($data['active']) {
                        $message->from($data['from_address'], $data['from_name']);
                    }
                }
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test_email' => 'Test mail failed: '.$e->getMessage(),
            ])->withInput();
        }

        return back()->with('success', 'Test mail sent successfully.');
    }

    private function setting(): ?VendorMailSetting
    {
        return VendorMailSetting::query()
            ->where('tenant_id', $this->tenantId())
            ->first();
    }

    private function saveSetting(array $data): VendorMailSetting
    {
        $setting = VendorMailSetting::query()->firstOrNew([
            'tenant_id' => $this->tenantId(),
        ]);

        $setting->fill([
            'tenant_id' => $this->tenantId(),
            'active' => (bool) ($data['active'] ?? false),
            'from_address' => $data['from_address'],
            'from_name' => $data['from_name'],
            'mail_method' => $data['mail_method'],
            'smtp_host' => $data['smtp_host'] ?? null,
            'smtp_port' => $data['smtp_port'] ?? null,
            'smtp_username' => $data['smtp_username'] ?? null,
            'mail_encryption' => ($data['mail_encryption'] ?? null) === 'none'
                ? null
                : ($data['mail_encryption'] ?? null),
        ]);

        if (filled($data['smtp_password'] ?? null)) {
            $setting->smtp_password = $data['smtp_password'];
        }

        $setting->save();

        return $setting;
    }

    private function validatedMailSettings(Request $request, array $extraRules = []): array
    {
        $active = $request->boolean('active');
        $smtpRequired = $active && $request->input('mail_method') === 'smtp';

        return $request->validate(array_merge([
            'active' => ['required', 'boolean'],
            'from_address' => [$active ? 'required' : 'nullable', 'email', 'max:255'],
            'from_name' => [$active ? 'required' : 'nullable', 'string', 'max:255'],
            'mail_method' => ['required', 'string', 'in:smtp,sendmail,log,array'],
            'smtp_host' => [$smtpRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'smtp_port' => [$smtpRequired ? 'required' : 'nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:2000'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
        ], $extraRules));
    }

    private function validatedRecipients(Request $request, bool $requireTo = false): array
    {
        return $request->validate([
            'to_addresses' => [$requireTo ? 'required' : 'nullable', 'string', 'max:4000', $this->validEmailListRule('To Mail')],
            'cc_addresses' => ['nullable', 'string', 'max:4000', $this->validEmailListRule('CC Mail')],
            'bcc_addresses' => ['nullable', 'string', 'max:4000', $this->validEmailListRule('BCC Mail')],
        ]);
    }

    private function validEmailListRule(string $label): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
            foreach ($this->parseAddressList($value) as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail("The {$label} field contains an invalid email address: {$email}.");

                    return;
                }
            }
        };
    }

    private function parseAddressList(?string $value): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeAddressList(?string $value): ?string
    {
        $emails = $this->parseAddressList($value);

        return count($emails) ? implode(', ', $emails) : null;
    }

    private function applyMailConfig(array $data, ?string $smtpPassword): void
    {
        $mailMethod = $data['mail_method'];

        config([
            'mail.default' => $mailMethod,
            'mail.from.address' => $data['from_address'],
            'mail.from.name' => $data['from_name'],
        ]);

        if ($mailMethod === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $data['smtp_host'] ?? null,
                'mail.mailers.smtp.port' => $data['smtp_port'] ?? null,
                'mail.mailers.smtp.username' => $data['smtp_username'] ?? null,
                'mail.mailers.smtp.password' => $smtpPassword,
                'mail.mailers.smtp.encryption' => ($data['mail_encryption'] ?? null) === 'none'
                    ? null
                    : ($data['mail_encryption'] ?? null),
            ]);
        }

        app('mail.manager')->purge($mailMethod);
    }
}
