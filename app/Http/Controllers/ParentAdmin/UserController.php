<?php
namespace App\Http\Controllers\ParentAdmin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
class UserController extends Controller {
 public function index(Request $request) {
  $parent=$request->user('parent_admin')->parentBusiness; $affiliateIds=$parent->affiliates()->pluck('id');
  $users=User::withoutGlobalScope('affiliate')->whereIn('affiliate_id',$affiliateIds)->with(['affiliate:id,name','role:id,role_name','user_plan:id,user_plan_name,plan_level'])->withCount('transactions')
   ->when($request->string('search')->isNotEmpty(),fn($q)=>$q->where(fn($q)=>$q->where('email','like','%'.$request->search.'%')->orWhere('first_name','like','%'.$request->search.'%')->orWhere('last_name','like','%'.$request->search.'%')))
   ->when($request->affiliate_id,fn($q,$id)=>$q->where('affiliate_id',$id))->latest()->paginate(50)->withQueryString();
  return view('parent-admin.users.index',['users'=>$users,'affiliates'=>$parent->affiliates()->orderBy('name')->get(['id','name'])]);
 }
}
