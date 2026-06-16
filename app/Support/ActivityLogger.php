<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\AuthenticationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityLogger
{
    public static function logModel(Model $model, string $event, array $oldValues = [], array $newValues = []): void
    {
        if (! app()->runningInConsole() && request() && Schema::hasTable('activity_logs')) {
            self::createActivity($model, $event, $oldValues, $newValues, request());
        }
    }

    public static function logLogin(Model $user, Request $request): AuthenticationLog
    {
        if (! Schema::hasTable('authentication_logs')) {
            return new AuthenticationLog();
        }

        return AuthenticationLog::create(array_merge(
            self::actorPayload($user),
            self::agentPayload($request),
            ['login_at' => now()]
        ));
    }

    public static function logLogout(Model $user, Request $request): void
    {
        if (! Schema::hasTable('authentication_logs')) {
            return;
        }

        $logId = $request->session()->get('authentication_log_id');

        $query = AuthenticationLog::query()
            ->where('user_type', $user::class)
            ->where('user_id', $user->getKey())
            ->whereNull('logout_at');

        $log = $logId ? (clone $query)->whereKey($logId)->first() : null;
        $log ??= $query->latest('login_at')->first();

        if ($log) {
            $log->update(['logout_at' => now()]);
        }
    }

    private static function createActivity(Model $model, string $event, array $oldValues, array $newValues, Request $request): void
    {
        $user = $request->user('vendor') ?: $request->user();

        ActivityLog::create(array_merge(
            self::actorPayload($user, $model),
            self::agentPayload($request),
            [
                'log_name' => self::logName($model, $event),
                'event' => $event,
                'subject_type' => $model::class,
                'subject_id' => $model->getKey(),
                'subject_label' => self::subjectLabel($model),
                'description' => self::description($model, $event),
                'old_values' => self::cleanValues($oldValues),
                'new_values' => self::cleanValues($newValues),
                'properties' => [],
                'http_method' => $request->method(),
                'url' => $request->fullUrl(),
            ]
        ));
    }

    private static function actorPayload(?Model $user, ?Model $subject = null): array
    {
        return [
            'tenant_id' => $user?->tenant_id ?? $subject?->tenant_id,
            'user_id' => $user?->getKey(),
            'user_type' => $user ? $user::class : null,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role' => self::roleName($user),
        ];
    }

    private static function agentPayload(Request $request): array
    {
        $agent = (string) $request->userAgent();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $agent,
            'is_desktop' => ! preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $agent),
            'platform' => self::platform($agent),
            'browser' => self::browser($agent),
        ];
    }

    private static function roleName(?Model $user): ?string
    {
        if (! $user || ! method_exists($user, 'getRoleNames')) {
            return null;
        }

        return $user->getRoleNames()->first();
    }

    private static function logName(Model $model, string $event): string
    {
        return Str::of(class_basename($model))
            ->snake()
            ->plural()
            ->append('.', $event)
            ->toString();
    }

    private static function subjectLabel(Model $model): string
    {
        $name = $model->name ?? $model->title ?? null;
        $fallback = Str::headline(class_basename($model)) . ' #' . $model->getKey();

        return $name ? $fallback : $fallback;
    }

    private static function description(Model $model, string $event): string
    {
        return Str::headline(class_basename($model)) . ' has been ' . $event . ' successfully.';
    }

    private static function cleanValues(array $values): array
    {
        return Arr::except($values, [
            'password',
            'remember_token',
            'deleted_at',
            'created_at',
            'updated_at',
        ]);
    }

    private static function platform(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Android') => 'AndroidOS',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS X'), str_contains($agent, 'Macintosh') => 'OS X',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Linux') => 'Linux',
            default => '-',
        };
    }

    private static function browser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => '-',
        };
    }
}
