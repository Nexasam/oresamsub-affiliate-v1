<?php
namespace App\Http\Controllers;
use App\Services\Onboarding\OnboardingChecklistService;
class AffiliateOnboardingChecklistController extends Controller { public function __invoke(OnboardingChecklistService $service) { return view('admin.onboarding', ['checklist' => $service->forAffiliate(session('affiliate'))]); } }
