<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\MailSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class MailSettingController
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user('superadmin')?->can('mail-settings.view'), 403);

        $setting = MailSetting::query()->first();

        return Inertia::render('SuperAdmin/MailSettings/Edit', [
            'setting' => [
                'from_address' => $setting?->from_address ?? config('mail.from.address'),
                'from_name' => $setting?->from_name ?? config('mail.from.name'),
                'mail_method' => $setting?->mail_method ?? config('mail.default', 'smtp'),
                'smtp_host' => $setting?->smtp_host ?? config('mail.mailers.smtp.host'),
                'smtp_port' => $setting?->smtp_port ?? config('mail.mailers.smtp.port'),
                'smtp_username' => $setting?->smtp_username ?? config('mail.mailers.smtp.username'),
                'smtp_password' => $setting?->smtp_password ?? config('mail.mailers.smtp.password') ?? '',
                'mail_encryption' => $setting?->mail_encryption ?? config('mail.mailers.smtp.encryption'),
                'smtp_verify_peer' => $setting?->smtp_verify_peer ?? config('mail.mailers.smtp.verify_peer', true),
                'has_smtp_password' => filled($setting?->smtp_password),
            ],
            'canEdit' => $request->user('superadmin')?->can('mail-settings.edit') ?? false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user('superadmin')?->can('mail-settings.edit'), 403);

        $data = $this->validatedMailSettings($request);

        $setting = MailSetting::query()->firstOrNew();
        $setting->fill([
            'from_address' => $data['from_address'],
            'from_name' => $data['from_name'],
            'mail_method' => $data['mail_method'],
            'smtp_host' => $data['smtp_host'] ?? null,
            'smtp_port' => $data['smtp_port'] ?? null,
            'smtp_username' => $data['smtp_username'] ?? null,
            'mail_encryption' => ($data['mail_encryption'] ?? null) === 'none'
                ? null
                : ($data['mail_encryption'] ?? null),
            'smtp_verify_peer' => $request->boolean('smtp_verify_peer'),
        ]);

        if (filled($data['smtp_password'] ?? null)) {
            $setting->smtp_password = $data['smtp_password'];
        }

        $setting->save();
        $setting->applyToConfig();

        return redirect()
            ->route('settings.mail.edit')
            ->with('success', 'Mail settings updated successfully.');
    }

    public function test(Request $request): RedirectResponse
    {
        abort_unless($request->user('superadmin')?->can('mail-settings.edit'), 403);

        $data = $this->validatedMailSettings($request, [
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $setting = MailSetting::query()->first();
        $smtpPassword = filled($data['smtp_password'] ?? null)
            ? $data['smtp_password']
            : $setting?->smtp_password;

        $this->applyMailConfig($data, $smtpPassword);

        try {
            Mail::raw(
                "This is a test email from {$data['from_name']}. Your mail settings are working.",
                function ($message) use ($data) {
                    $message
                        ->to($data['test_email'])
                        ->from($data['from_address'], $data['from_name'])
                        ->subject('Mail settings test');
                }
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test_email' => 'Test mail failed: '.$e->getMessage(),
            ])->withInput();
        }

        return back()->with('success', 'Test mail sent successfully.');
    }

    private function validatedMailSettings(Request $request, array $extraRules = []): array
    {
        return $request->validate(array_merge([
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'mail_method' => ['required', 'string', 'in:smtp,sendmail,log,array'],
            'smtp_host' => ['required_if:mail_method,smtp', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['required_if:mail_method,smtp', 'nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:2000'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'smtp_verify_peer' => ['required', 'boolean'],
        ], $extraRules));
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
                'mail.mailers.smtp.verify_peer' => filter_var($data['smtp_verify_peer'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        app('mail.manager')->purge($mailMethod);
    }
}
