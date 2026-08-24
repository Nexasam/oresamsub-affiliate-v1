<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Services\XixaPayService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Http\Services\VirtualAccountService;


class VirtualAccountsController extends Controller
{
    public function generate(Request $request){
        $data['user'] = $request->user();

        // Affiliate admins retain the existing ability to generate for one of
        // their customers. Customer requests always target the authenticated
        // account, even if a forged user_id is submitted.
        if ($request->filled('user_id') && $request->user()->role?->role_name === 'Admin') {
            $data['user'] = User::query()->findOrFail($request->integer('user_id'));
        }

        $generate_vas = (new VirtualAccountService())->generate_accounts($data);
        // return $generate_vas;

        if($generate_vas['status'] == 1){
            Session::flash('success',$generate_vas['message']);
            return redirect()->back();
        }

        Session::flash('failure',$generate_vas['message']);
        return redirect()->back();
        
        //generate crystal
    }

   
}
