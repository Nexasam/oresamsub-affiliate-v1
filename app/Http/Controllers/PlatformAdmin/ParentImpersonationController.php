<?php
namespace App\Http\Controllers\PlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ParentImpersonationController extends Controller {
 public function store(Request $request,ParentBusiness $parent){abort_if($request->session()->has('parent_impersonation')||$request->session()->has('platform_impersonation'),409,'Exit the current impersonation first.');$target=$parent->parentAdmins()->where('active',true)->firstOrFail();Auth::guard('parent_admin')->login($target);$request->session()->put('parent_impersonation',['platform_admin_id'=>$request->user('platform_admin')->id,'parent_admin_id'=>$target->id,'parent_business_id'=>$parent->id,'started_at'=>now()->toIso8601String()]);return redirect()->route('parent-admin.dashboard')->with('success','You are viewing '.$parent->name.' as its parent administrator.');}
 public function destroy(Request $request){abort_unless($request->session()->pull('parent_impersonation'),404);Auth::guard('parent_admin')->logout();return redirect()->route('platform-admin.parent-businesses.index')->with('success','Parent impersonation ended.');}
}
