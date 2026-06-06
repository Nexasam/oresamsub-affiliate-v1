<?php

namespace App\Http\Controllers\Auth;

use App\Events\Registered;
use App\Http\Controllers\Controller;
use App\Http\Services\CrystalPayService;
use App\Http\Services\VirtualAccountService;
use App\Mail\UserRegistrationNotification;
use App\Models\AdminColorSetting;
use App\Models\AffiliateUserPlan;
use App\Models\LandingPagesSetting;
use App\Models\Role;
use App\Models\SiteImage;
use App\Models\SiteTemplate;
use App\Models\User;
use App\Models\UserPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $upline = $request->ref ?? '';
        $data = [];
        $data['upline'] = $upline;
        // dd($upline);



        // if( env('APP_NAME') == 'OresamSub') {
        //     return view('oresamsub.auth.register')->with($data);
        // }


       
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

        // dd($data);

        $siteTemplate = SiteTemplate::first();
        if(! $siteTemplate || $siteTemplate->template_name == 'template_1'){
            return view('auth.register')->with($data);
        }

    
        return view('template2.auth.register')->with($data);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // dd($request->all());
        
        $request->validate([
            'username' => ['required', 'string', 'unique:users,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'numeric', 'string','regex:/^\d{4,5}$/'],
            // 'other_names' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required','unique:users,phone_number', 'string', 'max:255'],
            // 'upline_referral_phone_number' => ['nullable', 'string','exists:users,phone_number' ,'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::min(6)]
            // 'password' => ['required', 'confirmed', Password::min(8)
            // ->letters()
            // ->mixedCase()
            // ->numbers()
            // ->symbols()
            // ->uncompromised()::defaults()],
        ]);

        // 	echo REGEX_CountMatches('as.sfad.asdferw.asdfsdf.@gmail.com','.');
        // 	echo $count = preg_match_all('/\b.\b/','as.sfad.asdferw.asdfsdf.l.@gmail.com');
        // 	echo $count = preg_match_all('/\b.\b/','ade.a@gmail.com');
        // 	echo substr_count('as.sfad.asdferw.asdfsdf.l.@gmail.com','.');
        // 	echo substr_count('sam.ade@gmail.com','.');
        $validate_email =  count(explode('.',$request->email));
        if($validate_email > 2){
            Session::flash('failure','This email is not allowed.. You can reach out to our support via whatsapp');
            return redirect()->back();
        }

        //second security check
        $new_email_array = explode('.',$request->email);
        $last_item = array_pop($new_email_array);
        // echo $last_item;
        $checked_email = implode('',$new_email_array).'.'.$last_item;

        if($request->email != $checked_email){
            Session::flash('failure','This email is not allowed.. You can reach out to our support via whatsapp...');
            return redirect()->back();
        }

        $upline_details = NULL;
        if(isset($request->upline_referral_phone_number) &&  $request->upline_referral_phone_number != NULL){
            $upline_details = User::where('phone_number',$request->upline_referral_phone_number)->first();
        }
        $upline_id = $upline_details != NULL && $request->upline_referral_phone_number != $request->phone_number ? $upline_details->id : NULL;
        // $upline_id = $upline_details->id;
       

        $role_details = Role::where('role_name','User')->first();
        $default_reseller_plan = AffiliateUserPlan::where('plan_level',1)->first();
        $data['first_name'] = $request->first_name;
        $data['last_name'] = $request->last_name;
        // $data['other_names'] = $request->other_names;
        $data['pin'] = $request->pin;
        $data['phone_number'] = $request->phone_number;
        $data['username'] = $request->username;
        $data['upline_id'] = $upline_id;
        $data['email'] = $request->email;
        $data['role_id'] = $role_details->id;
        $data['user_plan_id'] = $default_reseller_plan->id;
        $data['password'] = Hash::make($request->password);
        // $data['confirm_password'] = Hash::make($request->confirm_password);
        if(env('APP_NAME') == 'OresamSub'){
            $data['email_verified_at'] = date('Y-m-d H:i:s');
        }

        $user = User::create($data);

        // $dataaa['user'] = $user;
        // (new VirtualAccountService())->generate_accounts($dataaa);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

  

    public function store2(Request $request): RedirectResponse
    {
        // 1. Validate input
        $validated = $request->validate([
            'username' => ['required', 'string', 'unique:users,username'],
            'fullname' => ['required', 'string', 'max:255'],
          'phone_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')
                    ->where(fn ($query) => $query->where('affiliate_id', $request->affiliate_id)),
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users')
                    ->where(fn ($query) => $query->where('affiliate_id', $request->affiliate_id)),
            ],
            'password' => ['required', 'confirmed', Password::min(6)],
            // 'pin' => ['required', 'digits:4'], // 🔥 ADD THIS
            'pin' => [
                'required',
                'digits:4',
                'regex:/^[0-9]{4}$/', // strictly integers only
                function ($attribute, $value, $fail) {

                    // ❌ Reject repeated digits (1111, 2222, etc)
                    if (preg_match('/^(.)\1{3}$/', $value)) {
                        $fail('PIN cannot be repeating numbers.');
                    }

                    // ❌ Reject sequential PINs (1234, 2345, 4321, etc)
                    $sequences = [
                        '1234'
                    ];

                    if (in_array($value, $sequences)) {
                        $fail('PIN is too weak. Choose a stronger PIN.');
                    }
                }
            ],
        ]);

        // 2. Email security check
        $dotCount = substr_count($validated['email'], '.');
        if ($dotCount > 2 || preg_match('/\.+@/', $validated['email'])) {
            return back()->with('failure', 'This email is not allowed. Contact support.');
        }

        // 3. Split name
        $nameParts = explode(' ', $validated['fullname'], 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? $firstName;

        // 4. Upline check
        $uplineId = null;
        if (
            $request->filled('upline_referral_phone_number') &&
            $request->upline_referral_phone_number !== $validated['phone_number']
        ) {
            $upline = User::where('phone_number', $request->upline_referral_phone_number)->first();
            if ($upline) {
                $uplineId = $upline->id;
            }
        }

        // 5. Role & Plan
        $roleId = Role::where('role_name', 'User')->value('id');
        // $defaultPlanId = UserPlan::where('is_default', 1)->value('id');
        $defaultPlanId = AffiliateUserPlan::where('plan_level',1)->first();


        // 6. Detect Tenant (VERY IMPORTANT 🔥)
        // adjust this based on your tenancy logic (subdomain, session, referral, etc.)
        $tenantId = session('affiliate')->id ?? null;

        // 7. Create user (NO email_verified_at ❌)
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $validated['username'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'],
            'upline_id' => $uplineId,
            'affiliate_id' => $tenantId, // 👈 critical for branded emails
            // 'pin' => null,
            'role_id' => $roleId,
            'user_plan_id' => $defaultPlanId->id ?? 1,
            'password' => Hash::make($validated['password']),
            'pin' => $validated['pin'], // 🔥 STORE PIN SECURELY
            'email_verified_at' => null, // 👈 enforce verification
        ]);

        // 8. Trigger email verification
        // event(new Registered($user));
        // 8. Trigger email verification
       $user->sendEmailVerificationNotification();

        // 9. Login user
        Auth::login($user);

        // 10. Redirect to verify page
        return redirect()->route('verification.notice')
            ->with('success', 'Account created! Please verify your email.');
    }


    public function store2back(Request $request): RedirectResponse
    {
        // 1. Validate input
        $validated = $request->validate([
            'username' => ['required', 'string', 'unique:users,username'],
            'fullname' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255', 'unique:users,phone_number'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        // 2. Fast email security check
        $dotCount = substr_count($validated['email'], '.');
        if ($dotCount > 2 || preg_match('/\.+@/', $validated['email'])) {
            return back()->with('failure', 'This email is not allowed. Contact support via WhatsApp.');
        }

        // 3. Split name (fallback to fullname if only one)
        $nameParts = explode(' ', $validated['fullname'], 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? $firstName;

        // 4. Upline check
        $uplineId = null;
        if ($request->filled('upline_referral_phone_number') && $request->upline_referral_phone_number !== $validated['phone_number']) {
            $upline = User::where('phone_number', $request->upline_referral_phone_number)->first();
            if ($upline) {
                $uplineId = $upline->id;
            }
        }

        // 5. Role and Plan
        $roleId = Role::where('role_name', 'User')->value('id');
        $defaultPlanId = UserPlan::where('is_default', 1)->value('id');

        // 6. Create user
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $validated['username'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'],
            'upline_id' => $uplineId,
            'pin' => null,
            'role_id' => $roleId,
            'user_plan_id' => $defaultPlanId,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => env('APP_NAME') === 'OresamSub' ? now() : null,
        ]);

        // 7. Dispatch event to handle account setup
        event(new Registered($user));

        // 8. Login and redirect
        Auth::login($user);
        return redirect()->route('dashboard');
    }





}
