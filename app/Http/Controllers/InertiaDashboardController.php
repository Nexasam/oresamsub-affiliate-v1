<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Services\CustomerPlansPricingService;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\Announcement;
use App\Models\Network;
use App\Models\Product;
use App\Models\ProductPlanCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserVirtualAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InertiaDashboardController extends Controller
{
    // Show login page (Inertia React)
    public function dashboard()
    {
        
        $data['transactions'] = Transaction::with(relations: 'product_plan')->where('user_id',auth()->id())->limit(10)->latest()->get();
        $data['announcements'] = Announcement::where('status',1)->latest()->get();
     
        return Inertia::render('Dashboard')->with($data);
    }

    public function data()
    {
        $data['networks'] = Network::get();
        // dd('test');
        return Inertia::render('BuyData')->with($data);
    }

    public function airtime()
    {
        $data['networks'] = Network::get();
        // dd('test');
        return Inertia::render('BuyAirtime')->with($data);
    }

    public function cable()
    {
        
        $product = Product::select('id')->where('slug', 'cable_subscription')->first();
        $product_plan_categories = AffiliateProductPlanCategory::select('id','product_plan_category_name')->where('product_id', $product->id)->get();
        $data['product'] = $product;
        $data['product_plan_categories'] = $product_plan_categories;
        // return Inertia::render('BuyCable')->with($data);

        return Inertia::render('BuyCable', [
            'cableProviders' => $product_plan_categories,
            'product' => $product,
        ]);
    }

    public function electricity()
    {
        $product = Product::select('id')->where('slug', 'utility_bills')->first();
        $product_plan_categories = AffiliateProductPlanCategory::select('id','product_plan_category_name')->where('product_id', $product->id)->get();
        $data['product'] = $product;
        $data['product_plan_categories'] = $product_plan_categories;
        // return Inertia::render('BuyCable')->with($data);

        return Inertia::render('BuyElectricity', [
            'electricityProviders' => $product_plan_categories,
            'product' => $product,
        ]);
    }

    public function virtual_accounts(){
        $virtualccts = UserVirtualAccount::select('id','bank_name','account_name','account_number')->where('user_id',auth()->id())->get();
        $data['virtualccts'] = $virtualccts;
        return Inertia::render('VirtualAccounts')->with($data);
    }

    public function transactions(){
        $data['transactions'] = Transaction::with(relations: 'product_plan')->where('user_id',auth()->id())->limit(200)->latest()->get();

        return Inertia::render('Transactions')->with($data);
    }

    public function set_pin(){
        return Inertia::render('SetPin');
    }

    public function store_set_pin(Request $request){
        logger('na here ');
        dd('testkls');
        // return Inertia::render('SetPin');
    }

    public function pricing(){
        $user = auth()->user();
        $user = User::with('user_plan')->where('id',auth()->id())->first();
        $datt['user'] = $user;
        $pplans = (new CustomerPlansPricingService())->fetch_plans_with_pricing($datt);
        $data['productPlans'] = $pplans['message'];
        return Inertia::render('Pricing')->with($data);
        
    }


    // Show Profile page
    public function profile()
    {
        $user = auth()->user();
        return Inertia::render('Profile', [
            'auth' => ['user' => $user],
        ]);
    }

    // Update Password
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = auth()->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password does not match',
                'errors' => ['current_password' => ['Current password does not match']],
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    // Update PIN
    public function updatePin(Request $request)
    {
        $validated = $request->validate([
            'current_pin' => ['required'],
            'new_pin' => ['required', 'confirmed', 'digits:4'],
        ]);

        $user = auth()->user();

        if ($user->pin && $validated['current_pin'] !== $user->pin) {
            return response()->json([
                'message' => 'Current PIN does not match',
                'errors' => ['current_pin' => ['Current PIN does not match']],
            ], 422);
        }

        $user->pin = $validated['new_pin'];
        $user->save();

        return response()->json(['message' => 'PIN updated successfully']);
    }


}
