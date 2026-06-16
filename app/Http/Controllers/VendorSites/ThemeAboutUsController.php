<?php

namespace App\Http\Controllers\VendorSites;

use App\Http\Controllers\Controller;
use App\Models\AboutUsTemplate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ThemeAboutUsController extends Controller
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

        $template = AboutUsTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        return Inertia::render($this->themePath() . '/AboutUs/AboutUs', [
            'tenant' => $tenant,
            'aboutUsTemplate' => $template ? $this->transformTemplate($template) : null,
        ]);
    }

    private function transformTemplate(AboutUsTemplate $template): array
    {
        return [
            'id' => $template->id,
            'template_name' => $template->template_name,

            'section1_title' => $template->section1_title,
            'section1_description' => $template->section1_description,
            'section1_image_url' => $template->section1_image ? Storage::url($template->section1_image) : null,

            'section2_title' => $template->section2_title,
            'section2_description' => $template->section2_description,
            'section2_image_1_url' => $template->section2_image_1 ? Storage::url($template->section2_image_1) : null,
            'section2_image_2_url' => $template->section2_image_2 ? Storage::url($template->section2_image_2) : null,
            'section2_image_3_url' => $template->section2_image_3 ? Storage::url($template->section2_image_3) : null,

            'section3_title' => $template->section3_title,
            'section3_description' => $template->section3_description,
            'section3_image_url' => $template->section3_image ? Storage::url($template->section3_image) : null,
        ];
    }
}