<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateProcessingProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AffiliateProcessingController extends Controller
{
    public function requestChange(Request $request, Affiliate $affiliate, AffiliateProcessingProfileService $service): RedirectResponse
    {
        $data = $request->validate([
            'management_mode' => ['required', Rule::in(['parent_managed', 'affiliate_managed'])],
            'processing_engine' => ['required', Rule::in(['multi_parent', 'legacy_oresamsub'])],
            'parent_provider_connection_id' => ['nullable', 'integer'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->requestChange($affiliate, $request->user('parent_admin'), $data);

        return back()->with('success', 'Processing change submitted for platform approval.');
    }
}
