<?php

namespace App\Http\Controllers\VendorSites;

use App\Http\Controllers\Controller;
use App\Models\ContactUsTemplate;
use Inertia\Inertia;
use Inertia\Response;

class ThemeContactUsController extends Controller
{
    protected function tenantOrFail()
    {
        $tenant = tenant();

        abort_if(!$tenant, 404, 'Tenant not found');

        return $tenant;
    }

    protected function themePath(): string
    {
        return $this->tenantOrFail()->theme->path ?? 'Themes/classic';
    }

    public function index(): Response
    {
        $tenant = $this->tenantOrFail();

        $template = ContactUsTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        return Inertia::render($this->themePath() . '/ContactUs/ContactUs', [
            'tenant' => $tenant,
            'contactUsTemplate' => $template ? $this->transformTemplate($template) : null,
        ]);
    }

    private function transformTemplate(ContactUsTemplate $template): array
    {
        return [
            'id' => $template->id,
            'template_name' => $template->template_name,
            'page_title' => $template->page_title,
            'page_subtitle' => $template->page_subtitle,

            'contact_box_title' => $template->contact_box_title,
            'contact_phone' => $template->contact_phone,
            'contact_email' => $template->contact_email,
            'contact_address' => $template->contact_address,
            'contact_whatsapp' => $template->contact_whatsapp,
            'contact_working_hours' => $template->contact_working_hours,
            'contact_note' => $template->contact_note,

            'map_iframe' => $template->map_iframe,
        ];
    }
}