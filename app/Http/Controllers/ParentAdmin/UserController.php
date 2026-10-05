<?php
namespace App\Http\Controllers\ParentAdmin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller {
 public function index(Request $request) {
  $parent=$request->user('parent_admin')->parentBusiness; $affiliateIds=$parent->affiliates()->pluck('id');
  $users=User::withoutGlobalScope('affiliate')->whereIn('affiliate_id',$affiliateIds)->with(['affiliate:id,name','role:id,role_name','user_plan:id,user_plan_name,plan_level'])->withCount('transactions')
   ->when($request->string('search')->isNotEmpty(),fn($q)=>$q->where(fn($q)=>$q->where('email','like','%'.$request->search.'%')->orWhere('first_name','like','%'.$request->search.'%')->orWhere('last_name','like','%'.$request->search.'%')))
   ->when($request->affiliate_id,fn($q,$id)=>$q->where('affiliate_id',$id))->latest()->paginate(50)->withQueryString();
  return view('parent-admin.users.index',['users'=>$users,'affiliates'=>$parent->affiliates()->orderBy('name')->get(['id','name'])]);
 }

 public function update(Request $request, int $user) {
  $parent=$request->user('parent_admin')->parentBusiness; $affiliateIds=$parent->affiliates()->pluck('id');
  $user=User::withoutGlobalScope('affiliate')->whereIn('affiliate_id',$affiliateIds)->findOrFail($user);
  $data=$request->validate([
   'first_name'=>['required','string','max:255'],
   'last_name'=>['required','string','max:255'],
   'phone_number'=>['nullable','string','max:50'],
   'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],
   'active'=>['required','boolean'],
  ]);
  $user->forceFill($data)->save();
  return back()->with('success','Affiliate user updated.');
 }
}
