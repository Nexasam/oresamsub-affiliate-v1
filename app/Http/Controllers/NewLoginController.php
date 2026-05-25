<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Services\VirtualAccountService;
use App\Models\AdminColorSetting;
use App\Models\LandingPagesSetting;
use App\Models\SiteImage;
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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = auth()->user();
            $data['user'] = $user;
    
            (new VirtualAccountService())->generate_accounts($data);

            return redirect()->intended('/dashboard');
            // return redirect()->route('dashboard');
        }

     


        // dd(auth()->user());


        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->onlyInput('email');
    }
}
