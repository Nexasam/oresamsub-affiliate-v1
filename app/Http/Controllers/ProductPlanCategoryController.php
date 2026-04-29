<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Product;
use App\Models\UserPlan;
use App\Models\Automation;
use App\Models\ProductPlan;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\AffiliateUserPlan;
use App\Models\ProductPlanCategory;
use App\Models\AffiliateProductPlan;
use App\Models\BulkDataProductPlans;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\AffiliateProductPlanCategory;
use League\CommonMark\Renderer\Inline\TextRenderer;

class ProductPlanCategoryController extends Controller
{
   
    public function index(){
        $product_plan_categories = AffiliateProductPlanCategory::with(['product' => function($query){
            $query->where('slug','data');
        }])->latest()->get();


        $automations = Automation::select('id','automation_name')->get();
        $networks = Network::select('id','network_name')->get();
        $products = Product::select('id','product_name')->where('visibility',1)->get();
        
        $data['automations'] = $automations;
        $data['products'] = $products;
        $data['networks'] = $networks;
        $data['product_plan_categories'] = $product_plan_categories;
        // dd('tt');
       
        return view('admin.product_plan_categories.index')->with($data);
    }

    
    public function view_details_by_automation($id,$automation_id){
      $product_plan_category = AffiliateProductPlanCategory::with('automation')->where('id',$id)->first();
      $bulk_data_plans = BulkDataProductPlans::with('product_plan_category')->where('product_plan_category_id',$id)->paginate(50);
      $user_plans = AffiliateUserPlan::where('plan_level','<=',env('RESELLER_PLAN_COUNT'))->get();
      $products = Product::select('id','product_name')->get();
      $networks = Network::select('id','network_name')->get();
      $automation = Automation::select('id','automation_name')->where('id',$automation_id)->first();
      $automations = Automation::select('id','automation_name')->get();
      $product_plans = AffiliateProductPlan::where('product_plan_category_id',$id)
      ->where('automation_id',$automation_id)
      ->get();

      $data['automation'] = $automation;
      $data['automations'] = $automations;
      $data['products'] = $products;
      $data['networks'] = $networks;
      $data['user_plans'] = $user_plans;
      $data['bulk_data_plans'] = $bulk_data_plans;
      $data['product_plan_category'] = $product_plan_category;
      $data['product_plans'] = $product_plans;
      
      return view('admin.product_plan_categories.view_details_by_automation')->with($data);

    }
 
    public function view_details($id){

      $product_plan_category = AffiliateProductPlanCategory::with('automation')->where('id',$id)->first();
      $bulk_data_plans = BulkDataProductPlans::with('product_plan_category')->where('product_plan_category_id',$id)->paginate(50);
      $user_plans = AffiliateUserPlan::where('plan_level','<=',env('RESELLER_PLAN_COUNT'))->get();
      $products = Product::select('id','product_name')->get();
      $networks = Network::select('id','network_name')->get();
      $automations = Automation::select('id','automation_name')->get();
      $product_plans = AffiliateProductPlan::where('product_plan_category_id',$id)
      // ->where('automation_id',$product_plan_category->automation_id)
      ->get();

      $data['automations'] = $automations;
      $data['products'] = $products;
      $data['networks'] = $networks;
      $data['user_plans'] = $user_plans;
      $data['bulk_data_plans'] = $bulk_data_plans;
      $data['product_plan_category'] = $product_plan_category;
      $data['product_plans'] = $product_plans;
      
      return view('admin.product_plan_categories.view_details')->with($data);

    }

    public function update_details(Request $request){
      $validator = Validator::make($request->all(), [
        'product_plan_category_name' => 'required|max:255',
        // 'product_id' => 'required|exists:products,id',
        // 'network_id' => 'nullable|exists:networks,id',
        'automation_id' => 'required|exists:automations,id',
        'old_automation_id' => 'required|exists:automations,id',
        // 'discount_value' => 'required'
      ]);

      if ($validator->stopOnFirstFailure()->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
      }

      $data = $validator->validated();
     
      // dd($data);
      if($request->discount_value > 100){
        Session::flash('failure','Discount value cannot be greater than 100 percent');
       
        return redirect()->back();
        
      }

      //we should also get all the plans from the previous plan category if not same:
      if($request->old_automation_id != $request->automation_id){
        //deactivate product_plans of old automation
        // ProductPlan::where('product_plan_category_id',$request->id)
        //             ->where('automation_id',$request->old_automation_id)
        //             ->update([
        //               "visibility"=>"0",
        //               "public_visibility"=>"0",
        //               "active_status"=>"0",
        //             ]);
      }
      
      //same so nothing should change
      unset($data['old_automation_id']);
      $create_product_plan_categories = AffiliateProductPlanCategory::where('id',$request->id)->update($data);
      AffiliateProductPlan::where('product_plan_category_id',$request->id)
                    ->where('automation_id',$request->automation_id)
                    ->update([
                      "visibility"=>"1",
                      "public_visibility"=>"1",
                      "active_status"=>"1",
                    ]);
      

      if($create_product_plan_categories){
        Session::flash('success','Product plan category: '.$request->product_plan_category_name.' was successfully updated');
      }else{
        Session::flash('failure','Error occurred while updating product plan category');
      }

      return redirect()->back();
      // return redirect()->route('admin.product_plan_categories.index');
    }

    public function update_plan_prices(Request $request){

      // dd($request->all());
      foreach($request->product_plan as $key=>$product_plan){
         
         $plan_id = $product_plan;
         $cost_price = $request->cost_price[$key];
         $visibility = $request->visibility[$key];
         $default_selling_price =  $request->default_selling_price[$key];
         $user_level_1_selling_price =  $request->user_level_1_selling_price[$key];
         $user_level_2_selling_price =  $request->user_level_2_selling_price[$key];
         $user_level_3_selling_price =  $request->user_level_3_selling_price[$key];
         $user_level_4_selling_price =  $request->user_level_4_selling_price[$key];

         $user_level_1_commission =  $request->user_level_1_commission[$key];
         $user_level_2_commission =  $request->user_level_2_commission[$key];
         $user_level_3_commission =  $request->user_level_3_commission[$key];
         $user_level_4_commission =  $request->user_level_4_commission[$key];
         $commission_feature =  $request->commission_feature[$key];

         $data_size_in_mb =  $request->data_size_in_mb[$key];

         $product_plan_name =  $request->product_plan_name[$key];
         
         $validity_in_days =  $request->validity_in_days[$key];

         AffiliateProductPlan::where('id',$plan_id)->update([
          "product_plan_name" =>  $product_plan_name,
          "cost_price" =>  $cost_price,
          "visibility" =>  $visibility,
          "default_selling_price" =>  $default_selling_price,
          "user_level_1_selling_price" =>  $user_level_1_selling_price,
          "user_level_2_selling_price"=>  $user_level_2_selling_price,
          "user_level_3_selling_price" =>  $user_level_3_selling_price,
          "user_level_4_selling_price" =>  $user_level_4_selling_price,
          "user_level_1_commission" =>  $user_level_1_commission,
          "user_level_2_commission"=>  $user_level_2_commission,
          "user_level_3_commission" =>  $user_level_3_commission,
          "user_level_4_commission" =>  $user_level_4_commission,
          "commission_feature" =>  $commission_feature,
          "data_size_in_mb" =>  $data_size_in_mb,
          "validity_in_days" =>  $validity_in_days,
         ]);
      }

       Session::flash('success','Product plan category: '.$request->product_plan_category_name.' was successfully updated');
       return redirect()->back();
    
    }


    public function store(Request $request){
       
       
         $validator = Validator::make($request->all(), [
            'product_plan_category_name' => 'required|max:255|unique:product_plan_categories,product_plan_category_name',
            'product_id' => 'required|exists:products,id',
            'network_id' => 'nullable|exists:networks,id',
            'automation_id' => 'required|exists:automations,id',
          ]);

          if ($validator->stopOnFirstFailure()->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
          }

          $data = $validator->validated();
         
          // dd($data);

          $create_product_plan_categories = AffiliateProductPlanCategory::create($data);
    
          if($create_product_plan_categories){
            Session::flash('success','Product plan categories successfully created');
          }else{
            Session::flash('failure','Error occurred while creating product plan category');
          }
    
          return redirect()->back();
    }


    public function store_plan(Request $request){
      // |unique:product_plan_categories,product_plan_category_name
      $validator = Validator::make($request->all(), [
         'product_plan_name' => 'required|max:255',
          "product_plan_category_id" => "required|max:255",
          "automation_id" => "exists:automations,id",
          "automation_product_plan_id" => "required",
          "cost_price" => "required|integer",
          "data_size_in_mb" => "required|integer",
          "validity_in_days" => "required|integer",
          "default_selling_price" => "required|integer",
          "user_level_1_selling_price" => "required|integer",
          "user_level_2_selling_price" => "required|integer",
          "user_level_3_selling_price" => "required|integer",
          "user_level_4_selling_price" => "required|integer",
       ]);

       if ($validator->stopOnFirstFailure()->fails()) {
         Session::flash('failure',$validator->errors()->first());
         return redirect()->back()->withErrors($validator)->withInput();
       }

       $data = $request->all();
       unset($data['_token']);
      //  dd($data);
      $check_automation_plan_id_existence = AffiliateProductPlan::where('automation_product_plan_id',$request->automation_product_plan_id)
                                            ->where('automation_id',$request->automation_id)
                                            ->first();
      if($check_automation_plan_id_existence){
        //this combination exists for the automation
        Session::flash('failure','Sorry, the Plan ID exists for the automation');
        return redirect()->back();
 
      }

       $create_product_plan = AffiliateProductPlan::create($data);
 
       if($create_product_plan){
         Session::flash('success','Product plan was successfully created');
       }else{
         Session::flash('failure','Error occurred while creating product plan category');
       }
 
       return redirect()->back();
 }

  

    public function updateAutomation(Request $request){
          $validator = Validator::make($request->all(), [
            'product_category_id' => 'required|max:255|required|exists:product_plan_categories,id',
            'automation_id' => 'required|exists:automations,id',
          ]);
          

          if ($validator->stopOnFirstFailure()->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
          }

          $data['product_category_id'] = $request->product_category_id;
          $data['automation_id'] = $request->automation_id;
          
          AffiliateProductPlanCategory::where('id',$request->product_category_id)
                              ->update([
                                'automation_id' => $request->automation_id
                              ]);

          return response()->json(['status'=>'-1', 'message'=>'successfully updated' ]);
    }

    public function toggle_plan_category_visibility(Request $request){
      
      $validator = Validator::make($request->all(), [
        'productPlanCategoryId' => 'required|max:255|exists:product_plan_categories,id',
        'token' => 'required',
      ]);
      

      if ($validator->stopOnFirstFailure()->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
      }

      $detail = AffiliateProductPlanCategory::where('id',$request->productPlanCategoryId)->first();
      $update = $detail->visibility ? 0 : 1;
      $detail->update([
        'visibility' => $update
      ]);

      return response()->json(['status'=>'1', 'message'=>'success']);

    }

    public function toggle_hot_sales(Request $request){
      
      $validator = Validator::make($request->all(), [
        'planCategoryId' => 'required|max:255|exists:product_plan_categories,id',
        'token' => 'required',
      ]);
      

      if ($validator->stopOnFirstFailure()->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
      }

      $detail = AffiliateProductPlanCategory::where('id',$request->planCategoryId)->first();
      $update = $detail->is_hot_sales ? 0 : 1;
      $detail->update([
        'is_hot_sales' => $update
      ]);
      return response()->json(['status'=>'1', 'message'=>'success' ]);
     
    }

    public function admin_fetch_product_plan_categories(Request $request){
      $affiliateId = $this->getId(); // or however you identify the affiliate
  
      // Fetch all master categories
      $masterCategories = ProductPlanCategory::with(['product','network'])->get();
  
      // Fetch affiliate-specific categories
      $affiliateCategories = AffiliateProductPlanCategory::where('affiliate_id', $affiliateId)
          ->get()
          ->keyBy('plan_category_id'); 
          // keyBy makes it easy to check if affiliate has it or not
  
      return DataTables::of($masterCategories)
          ->addIndexColumn()
          ->addColumn('DT_RowIndex', function ($data) {
              return $data->id;
          })
          ->addColumn('product_plan_category_name', function ($data) use ($affiliateCategories) {
              $isAdded = $affiliateCategories->has($data->id);
              return $isAdded 
                  ? $data->product_plan_category_name 
                  : '<span class="text-gray-400">'.$data->product_plan_category_name.' (not added)</span>';
          })
          ->addColumn('product_id', function ($data) {
              return $data->product->product_name ?? 'nil';
          })
          ->addColumn('network_id', function ($data) {
              $net = $data->network->network_name ?? 'nil';
              // $net .= ' - '.$data->id;

              return $net;
          })
          ->addColumn('created_at', function ($data) use ($affiliateCategories) {
              $affiliateCat = $affiliateCategories->get($data->id);
              return $affiliateCat ? $affiliateCat->created_at : '-';
          })
          ->addColumn('is_hot_sales', function ($data) use ($affiliateCategories) {
              $affiliateCat = $affiliateCategories->get($data->id);
  
              if (!$affiliateCat) {
                  return '<span class="text-gray-400">N/A</span>';
              }
  
              $escapedUrl = htmlspecialchars(json_encode($affiliateCat->id));
              $token = htmlspecialchars(json_encode(csrf_token()));
              $checked = $affiliateCat->is_hot_sales == 1 ? 'checked':'';
              $actual_value = $affiliateCat->is_hot_sales;
              $checkedd = htmlspecialchars(json_encode($actual_value));
  
              $toggle_btn = '<div class="flex items-center">';
              $toggle_btn .=  '<input onchange="toggleHotSales('.$escapedUrl.','.$token.','.$checkedd.')" type="checkbox" id="hs-basic-with-description-checked'.$affiliateCat->id.'" class="ti-switch" '.$checked.'>';
              $toggle_btn .=  '<label for="hs-basic-with-description-checked" class="text-sm text-gray-500 ms-3 dark:text-white/70 "></label>';
              $toggle_btn .=  ' <span class="badge rounded-sm bg-success/10 text-success hidden" id="hot_sales_notification'.$affiliateCat->id.'"></span>  </div>';
  
              return $toggle_btn;
          })
          ->addColumn('visibility', function ($data) use ($affiliateCategories) {
              $affiliateCat = $affiliateCategories->get($data->id);
  
              if (!$affiliateCat) {
                  return '<span class="text-gray-400">N/A</span>';
              }
  
              $escapedUrl = htmlspecialchars(json_encode($affiliateCat->id));
              $token = htmlspecialchars(json_encode(csrf_token()));
              $checked = $affiliateCat->visibility == 1 ? 'checked':'';
              $actual_value = $affiliateCat->visibility;
              $checkedd = htmlspecialchars(json_encode($actual_value));
  
              $toggle_btn = '<div class="flex items-center">';
              $toggle_btn .=  '<input onchange="togglePlanCategoryVisibility('.$escapedUrl.','.$token.','.$checkedd.')" type="checkbox" id="hs-basic-with-description-checked'.$affiliateCat->id.'" class="ti-switch" '.$checked.'>';
              $toggle_btn .=  '<label for="hs-basic-with-description-checked" class="text-sm text-gray-500 ms-3 dark:text-white/70 "></label>';
              $toggle_btn .=  ' <span class="badge rounded-sm bg-success/10 text-success hidden" id="plan_cat_visibility_notification'.$affiliateCat->id.'"></span>  </div>';
  
              return $toggle_btn;
          })
          // ->addColumn('action', function ($data) use ($affiliateCategories) {
          //   $affiliateCat = $affiliateCategories->get($data->id);
        
          //   if (!$affiliateCat) {
          //     return '<button data-id="'.$data->id.'" 
          //     onclick="addAffiliateCategory('.$data->id.', \''.e($data->product_plan_category_name).'\')" 
          //     class="ti-btn ti-btn-secondary">Add</button>';
      
          //   }
        
          //   $view_route = route('admin.product_plan_categories.view_details', $affiliateCat->id);
          //   // return '<a href="'.$view_route.'" class="hs-dropdown-toggle ti-btn ti-btn-success">Details</a>';
          // })
          ->escapeColumns([])
          ->make(true);
    }

    public function generateAffiliateCategories(Request $request){
          // $affiliate = auth()->user();

          // // Check if they already have categories
          // $existing = $affiliate->productPlanCategories()->count();
          // if ($existing > 0) {
          //     return response()->json([
          //         'status' => 0,
          //         'message' => 'Affiliate already has product plan categories.'
          //     ]);
          // }

         $affiliateId = $this->getId(); // or $this->getId()
         logger("AffID: $affiliateId");

          // Example logic: duplicate global categories for this affiliate
          $globalCategories = ProductPlanCategory::get();

          foreach ($globalCategories as $cat) {
            AffiliateProductPlanCategory::updateOrCreate(
                [
                    'affiliate_id' => $affiliateId,
                    'plan_category_id' => $cat->id,
                ],
                [
                    'product_plan_category_name' => $cat->product_plan_category_name,
                    'visibility' => 1,
                    'network_id' => $cat->network_id,
                    'product_id' => $cat->product_id,
                ]
            );
          }
        

          return response()->json([
              'status' => true,
              'message' => $globalCategories->count().' categories generated successfully.'
          ]);
    }


    public function addAffiliateCategory(Request $request){
      $validator = Validator::make($request->all(), [
        'plan_category_id' => 'required|exists:product_plan_categories,id',
        'plan_category_name' => 'required|exists:product_plan_categories,product_plan_category_name',
      ]);

      if ($validator->stopOnFirstFailure()->fails()) {
        return response()->json(['status' => false, 'message' => 'Record does not exist']);
      }

      $affiliateId = $this->getId(); // or $this->getId();

      $exists = AffiliateProductPlanCategory::where('plan_category_id', $request->plan_category_id)->first();

      $check2 = ProductPlanCategory::where('id', $request->plan_category_id)->first();

      if ($exists) {
          return response()->json(['status' => true, 'message' => 'Already added']);
      }

      if (! $check2) {
        //strang
        return response()->json(['status' => false, 'message' => 'The main category does not exist']);
      }

      $affiliateCategory = AffiliateProductPlanCategory::create([
          'plan_category_id' => $request->plan_category_id,
          'product_plan_category_name' => $check2->product_plan_category_name,
          'product_id' => $check2->product_id,
          'visibility' => 1,
          'is_hot_sales' => 0,
          'network_id' =>$check2->network_id,
      ]);

      return response()->json(['status' => true, 'message' => 'Category added', 'data' => $affiliateCategory]);
    }

}
