<?php
namespace App\Http\Controllers\ParentAdmin;
use App\Http\Controllers\Controller;
use App\Services\Onboarding\OnboardingChecklistService;
use Illuminate\Http\Request;
class OnboardingController extends Controller { public function __invoke(Request $request, OnboardingChecklistService $service) { return view('parent-admin.onboarding', ['checklist' => $service->forParent($request->user('parent_admin')->parentBusiness)]); } }
