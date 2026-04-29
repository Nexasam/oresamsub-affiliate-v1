<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AffiliateUserPlan;
use Illuminate\Support\Facades\Auth;

class AffiliateUserPlanController extends Controller
{
    public function index()
    {
        // $affiliate_id = $this->getId();

        // Check if plans exist
        $existingPlans = AffiliateUserPlan::count();

        if ($existingPlans === 0) {
            // Generate 6 default plans
            $plans = [
                ['Starter', 1],
                ['Bronze', 2],
                ['Silver', 3],
                ['Gold', 4],
                ['Platinum', 5],
                ['Diamond', 6],
            ];

            foreach ($plans as [$name, $level]) {
                AffiliateUserPlan::create([
                    // 'affiliate_id' => $affiliate_id,
                    'user_plan_name' => $name . ' Plan',
                    'plan_level' => $level,
                    'is_default' => 1,
                    'visibility' => 1,
                    'max_profit' => null,
                ]);
            }
        }

        $user_plans = AffiliateUserPlan::get();

        $data['user_plans'] = $user_plans;
        return view('admin.reseller_plans.index')->with($data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'updated_user_plan_name' => 'required|string|max:255',
        ]);

        $plan = AffiliateUserPlan::findOrFail($id);

        $plan->update([
            'updated_user_plan_name' => $request->updated_user_plan_name,
        ]);

        return response()->json(['message' => 'Plan updated successfully']);
    }
}
