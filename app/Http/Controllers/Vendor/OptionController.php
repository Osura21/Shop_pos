<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateValue;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class OptionController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Menu/Option/Index');
    }

    public function getData(Request $request)
    {
        $allBranches = Branch::where('tenant_id', $this->tenantId())
            ->pluck('name', 'id');

        $items = OptionTemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->select('option_templates.*');

        return DataTables::of($items)
            ->addColumn('branch_name', function ($row) use ($allBranches) {
                if (!$row->branch_ids)
                    return '-';

                $names = collect($row->branch_ids)
                    ->map(fn($id) => $allBranches[$id] ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                return count($names) ? implode(', ', $names) : '-';
            })
            ->addColumn('type_label', fn($row) => OptionTemplate::TYPES[$row->type] ?? ucfirst($row->type))
            ->editColumn('created_at', fn($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Menu/Option/CreateUpdate', [
            'option' => null,
            'branches' => $this->branches(),
            'typeOptions' => OptionTemplate::TYPES,
            'priceTypes' => OptionTemplate::PRICE_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        if ($this->usesRows($validated['type']) && !collect($validated['values'] ?? [])->contains(fn($row) => filled($row['label'] ?? null))) {
            return back()->withInput()->withErrors([
                'values' => 'Please add at least one value row.',
            ]);
        }

        try {
            DB::beginTransaction();

            $option = new OptionTemplate();
            $option->tenant_id = $this->tenantId();

            $this->fillOption($option, $validated);
            $option->save();

            $this->syncValues($option, $validated['values'] ?? []);

            DB::commit();

            return redirect()
                ->route('vendor.options.index')
                ->with('success', 'Option created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Option store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to save option.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Option store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while creating option.',
            ]);
        }
    }

    public function edit($id)
    {
        $option = OptionTemplate::query()
            ->with(['values'])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/Menu/Option/CreateUpdate', [
            'option' => $this->mapOptionForForm($option),
            'branches' => $this->branches(),
            'typeOptions' => OptionTemplate::TYPES,
            'priceTypes' => OptionTemplate::PRICE_TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $option = OptionTemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        $validated = $request->validate($this->rules($option->id), $this->messages());

        if ($this->usesRows($validated['type']) && !collect($validated['values'] ?? [])->contains(fn($row) => filled($row['label'] ?? null))) {
            return back()->withInput()->withErrors([
                'values' => 'Please add at least one value row.',
            ]);
        }

        try {
            DB::beginTransaction();

            $this->fillOption($option, $validated);
            $option->save();

            $option->values()->delete();
            $this->syncValues($option, $validated['values'] ?? []);

            DB::commit();

            return redirect()
                ->route('vendor.options.index')
                ->with('success', 'Option updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Option update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update option.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Option update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while updating option.',
            ]);
        }
    }

    public function destroy($id)
    {
        $option = OptionTemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        $option->delete();

        return redirect()
            ->route('vendor.options.index')
            ->with('success', 'Option deleted successfully.');
    }

    private function fillOption(OptionTemplate $option, array $validated): void
    {
        $option->branch_ids = $validated['branch_ids'] ?? null;
        $option->name = $validated['name'];
        $option->type = $validated['type'];
        $option->is_required = (bool) ($validated['is_required'] ?? false);

        if ($this->usesRows($validated['type'])) {
            $option->base_price = 0;
            $option->secondary_price = null;
            $option->price_type = 'fixed';
        } else {
            $option->base_price = $validated['base_price'] ?? 0;
            $option->secondary_price = $validated['secondary_price'] ?? null;
            $option->price_type = $validated['price_type'] ?? 'fixed';
        }
    }

    private function syncValues(OptionTemplate $option, array $values): void
    {
        if (!$this->usesRows($option->type)) {
            return;
        }

        foreach ($values as $index => $row) {
            if (blank($row['label'] ?? null)) {
                continue;
            }

            OptionTemplateValue::create([
                'option_template_id' => $option->id,
                'label' => $row['label'],
                'base_price' => $row['base_price'] ?? 0,
                'secondary_price' => $row['secondary_price'] ?? null,
                'price_type' => $row['price_type'] ?? 'fixed',
                'sort_order' => $index,
            ]);
        }
    }

    private function mapOptionForForm(OptionTemplate $option): array
    {
        return [
            'id' => $option->id,
            'name' => $option->name,
            'branch_ids' => $option->branch_ids,
            'type' => $option->type,
            'is_required' => (bool) $option->is_required,
            'base_price' => $option->base_price,
            'secondary_price' => $option->secondary_price,
            'price_type' => $option->price_type,
            'values' => $option->values->map(fn($row) => [
                'label' => $row->label,
                'base_price' => $row->base_price,
                'secondary_price' => $row->secondary_price,
                'price_type' => $row->price_type,
            ])->values()->all(),
        ];
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['exists:branches,id'],
            'type' => ['required', Rule::in(array_keys(OptionTemplate::TYPES))],
            'is_required' => ['nullable', 'boolean'],

            'base_price' => ['nullable', 'numeric', 'min:0'],
            'secondary_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', Rule::in(array_keys(OptionTemplate::PRICE_TYPES))],

            'values' => ['nullable', 'array'],
            'values.*.label' => ['nullable', 'string', 'max:255'],
            'values.*.base_price' => ['nullable', 'numeric', 'min:0'],
            'values.*.secondary_price' => ['nullable', 'numeric', 'min:0'],
            'values.*.price_type' => ['nullable', Rule::in(array_keys(OptionTemplate::PRICE_TYPES))],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Option name is required.',
            'branch_id.required' => 'Branch is required.',
            'type.required' => 'Option type is required.',
        ];
    }

    private function usesRows(string $type): bool
    {
        return in_array($type, ['select', 'multiple_select', 'checkbox', 'radio_button'], true);
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}