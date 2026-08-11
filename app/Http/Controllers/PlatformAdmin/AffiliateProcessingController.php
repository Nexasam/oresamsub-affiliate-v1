<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateProcessingChangeRequest;
use App\Services\AffiliateProcessingProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateProcessingController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.affiliate-processing.index', [
            'requests' => AffiliateProcessingChangeRequest::with(['affiliate:id,name,contact_email', 'parentBusiness:id,name'])
                ->latest()->paginate(30),
        ]);
    }

    public function review(Request $request, AffiliateProcessingChangeRequest $changeRequest, AffiliateProcessingProfileService $service): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:500']]);

        if ($data['decision'] === 'approved') {
            $service->approve($changeRequest, $request->user('platform_admin'));
        } else {
            $service->reject($changeRequest, $request->user('platform_admin'), $data['rejection_reason']);
        }

        return back()->with('success', 'Processing change reviewed.');
    }
}
