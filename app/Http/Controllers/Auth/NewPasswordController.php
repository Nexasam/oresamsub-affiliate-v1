<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\SiteImage;
use App\Models\AdminColorSetting;
use App\Models\SiteTemplate;
use Illuminate\View\View;

class NewPasswordController extends Controller
{

      /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {


        if(env('APP_NAME') == 'OresamSub') {
            return view('oresamsub.auth.reset-password', ['request' => $request]);
        }

       $site_images_data = SiteImage::get();
       $data = [];
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
        
        $siteTemplate = SiteTemplate::first();
        if(! $siteTemplate || $siteTemplate->template_name == 'template_1'){
            return view('auth.reset-password', ['request' => $request])->with($data);
        }


        // return view('auth.confirm-password')->with($data);
        return view('template2.auth.reset-password', ['request' => $request])->with($data);
    }

    
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:6'],

            'new_pin' => [
                'required',
                'digits:4',
                'confirmed',

                // Prevent weak/common pins
                function ($attribute, $value, $fail) {

                    $blockedPins = [
                        // '0000',
                        // '1111',
                        '1234',
                        // '2222',
                        // '3333',
                        // '4444',
                        // '5555',
                        // '6666',
                        // '7777',
                        // '8888',
                        // '9999',
                        // '4321',
                    ];

                    // Block common pins
                    if (in_array($value, $blockedPins)) {
                        $fail('Please choose a stronger transaction PIN.');
                    }

                    // Block sequential ascending pins
                    // if (
                    //     $value === '0123' ||
                    //     $value === '2345' ||
                    //     $value === '3456' ||
                    //     $value === '4567' ||
                    //     $value === '5678' ||
                    //     $value === '6789'
                    // ) {
                    //     $fail('Sequential PINs are not allowed.');
                    // }
                },
            ],
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user) use ($request) {

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'pin' => Hash::make($request->new_pin),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successful.')
            : back()->with('error', __($status));
    }
}