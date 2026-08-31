<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Services\VirtualAccountService;
use App\Models\AdminColorSetting;
use App\Models\LandingPagesSetting;
use App\Models\SiteImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


#its destiny has changed oh
class NewLoginController extends Controller
{
    // Show login page (Inertia React)
    public function create(Request $request)
    {

        $upline = $request->ref ?? '';
        $data = [];
        $data['upline'] = $upline;
        // dd($upline);

       
        // dd($data);
        $landing_data = LandingPagesSetting::where('field_name','support_whatsapp_number')->first();
        $data[$landing_data->field_name] = $landing_data->field_details;
        

        $site_images_data = SiteImage::where('affiliate_id',session('affiliate')->id)->get();
        if(count($site_images_data) > 0){
            foreach($site_images_data as $site_image){
                $data[$site_image->image_category] = $site_image->image_name;
            }
        }


        $site_colors = AdminColorSetting::get();
        if(count($site_colors) > 0){
            foreach($site_colors as $site_color){
                $data[$site_color->color_name] = $site_color->color_value;
            }
        }

        return view('auth.login')->with($data);


        // return Inertia::render('Auth/Login');

        // return inertia_location(route('login'));
    }

    // Handle login form submission
    public function store(Request $request)
    {
        // Keep the former `email` payload working while the refreshed form uses
        // the more accurate `login` name for email, username, or phone.
        $request->merge([
            'login' => trim((string) ($request->input('login') ?: $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ], [
            'login.required' => 'Enter your email, username or phone number.',
        ]);

        $affiliateId = session('affiliate')?->id;
        $user = $affiliateId
            ? User::withoutGlobalScope('affiliate')
                ->where('affiliate_id', $affiliateId)
                ->where(function ($query) use ($credentials) {
                    $query->where('email', $credentials['login'])
                        ->orWhere('username', $credentials['login'])
                        ->orWhere('phone_number', $credentials['login']);
                })
                ->first()
            : null;

        if ($user && Auth::attempt([
            'id' => $user->id,
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();

            $data['user'] = $user;
    
            (new VirtualAccountService())->generate_accounts($data);

            return redirect()->intended('/dashboard');
            // return redirect()->route('dashboard');
        }

     


        // dd(auth()->user());


        return back()->withErrors([
            'login' => 'These login details do not match an account on this website.',
        ])->onlyInput('login');
    }
}
