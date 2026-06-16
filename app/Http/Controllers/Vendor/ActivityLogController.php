<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuthenticationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    use ResolvesTenantContext;

    public function index(Request $request)
    {
        abort_unless($request->user('vendor')?->can('activity-logs.view'), 403);

        return Inertia::render('VendorAdmin/Activities/ActivityLogs/Index');
    }

    public function getData(Request $request)
    {
        abort_unless($request->user('vendor')?->can('activity-logs.view'), 403);

        $logs = ActivityLog::query()
            ->where('tenant_id', $this->tenantId())
            ->select('activity_logs.*');

        return DataTables::of($logs)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('user_name', 'like', "%{$search}%")
                            ->orWhere('user_email', 'like', "%{$search}%")
                            ->orWhere('user_role', 'like', "%{$search}%")
                            ->orWhere('log_name', 'like', "%{$search}%")
                            ->orWhere('event', 'like', "%{$search}%")
                            ->orWhere('subject_label', 'like', "%{$search}%")
                            ->orWhere('ip_address', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('user_initials', fn ($row) => $this->initials($row->user_name ?: $row->user_email))
            ->addColumn('user_display', fn ($row) => trim(($row->user_name ?: 'System') . ($row->user_email ? ' - ' . $row->user_email : '')))
            ->addColumn('show_url', fn ($row) => route('vendor.activities.activity-logs.show', $row->id))
            ->editColumn('ip_address', fn ($row) => $row->ip_address ?: 'Unknown')
            ->editColumn('event', fn ($row) => Str::headline($row->event))
            ->editColumn('is_desktop', fn ($row) => $row->is_desktop ? 'Yes' : 'No')
            ->editColumn('created_at', fn ($row) => $this->slDateTime($row->created_at))
            ->make(true);
    }

    public function show(Request $request, ActivityLog $activityLog)
    {
        abort_unless($request->user('vendor')?->can('activity-logs.view'), 403);
        abort_unless((int) $activityLog->tenant_id === $this->tenantId(), 404);

        return Inertia::render('VendorAdmin/Activities/ActivityLogs/Show', [
            'log' => [
                'id' => $activityLog->id,
                'user_name' => $activityLog->user_name,
                'user_email' => $activityLog->user_email,
                'user_role' => $activityLog->user_role,
                'log_name' => $activityLog->log_name,
                'event' => Str::headline($activityLog->event),
                'subject_label' => $activityLog->subject_label,
                'description' => $activityLog->description,
                'ip_address' => $activityLog->ip_address ?: 'Unknown',
                'http_method' => $activityLog->http_method ?: '-',
                'is_desktop' => $activityLog->is_desktop ? 'Yes' : 'No',
                'platform' => $activityLog->platform ?: '-',
                'browser' => $activityLog->browser ?: '-',
                'logged_at' => $this->slDateTime($activityLog->created_at),
                'changes' => $this->changes($activityLog->old_values ?? [], $activityLog->new_values ?? []),
            ],
        ]);
    }

    public function authenticationIndex(Request $request)
    {
        abort_unless($request->user('vendor')?->can('authentication-logs.view'), 403);

        return Inertia::render('VendorAdmin/Activities/AuthenticationLogs/Index');
    }

    public function authenticationData(Request $request)
    {
        abort_unless($request->user('vendor')?->can('authentication-logs.view'), 403);

        $logs = AuthenticationLog::query()
            ->where('tenant_id', $this->tenantId())
            ->select('authentication_logs.*');

        return DataTables::of($logs)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('user_name', 'like', "%{$search}%")
                            ->orWhere('user_email', 'like', "%{$search}%")
                            ->orWhere('user_role', 'like', "%{$search}%")
                            ->orWhere('ip_address', 'like', "%{$search}%")
                            ->orWhere('platform', 'like', "%{$search}%")
                            ->orWhere('browser', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('user_initials', fn ($row) => $this->initials($row->user_name ?: $row->user_email))
            ->addColumn('user_display', fn ($row) => trim(($row->user_name ?: 'System') . ($row->user_email ? ' - ' . $row->user_email : '')))
            ->addColumn('show_url', fn ($row) => route('vendor.activities.authentication-logs.show', $row->id))
            ->editColumn('ip_address', fn ($row) => $row->ip_address ?: 'Unknown')
            ->editColumn('is_desktop', fn ($row) => $row->is_desktop ? 'Yes' : 'No')
            ->editColumn('login_at', fn ($row) => $this->slDateTime($row->login_at))
            ->editColumn('logout_at', fn ($row) => $this->slDateTime($row->logout_at))
            ->make(true);
    }

    public function authenticationShow(Request $request, AuthenticationLog $authenticationLog)
    {
        abort_unless($request->user('vendor')?->can('authentication-logs.view'), 403);
        abort_unless((int) $authenticationLog->tenant_id === $this->tenantId(), 404);

        return Inertia::render('VendorAdmin/Activities/AuthenticationLogs/Show', [
            'log' => [
                'id' => $authenticationLog->id,
                'user_name' => $authenticationLog->user_name,
                'user_email' => $authenticationLog->user_email,
                'user_role' => $authenticationLog->user_role,
                'ip_address' => $authenticationLog->ip_address ?: 'Unknown',
                'is_desktop' => $authenticationLog->is_desktop ? 'Yes' : 'No',
                'platform' => $authenticationLog->platform ?: '-',
                'browser' => $authenticationLog->browser ?: '-',
                'user_agent' => $authenticationLog->user_agent ?: '-',
                'login_at' => $this->slDateTime($authenticationLog->login_at),
                'logout_at' => $this->slDateTime($authenticationLog->logout_at),
            ],
        ]);
    }

    private function changes(array $oldValues, array $newValues): array
    {
        $fields = collect(array_keys($oldValues))
            ->merge(array_keys($newValues))
            ->unique()
            ->values();

        return $fields->map(fn ($field) => [
            'field' => $field,
            'old' => $this->stringValue($oldValues[$field] ?? null),
            'new' => $this->stringValue($newValues[$field] ?? null),
        ])->all();
    }

    private function stringValue($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function initials(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 'S';
        }

        return Str::of($value)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    private function slDateTime($date): string
    {
        return $date ? $date->copy()->timezone('Asia/Colombo')->format('Y-m-d h:i A') : '-';
    }
}
