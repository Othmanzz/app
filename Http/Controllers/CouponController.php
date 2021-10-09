<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{

    public function __construct()
    {
        // this for admin and publisher
        $this->middleware('auth');
        $this->middleware('addcoupon');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        
            $data["coupons"] = DB::select("select * from coupon where `user_id` = '".Auth::id()."' order by id desc");

        
        return view('admin.coupons.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [];
      $data["code"]  ="PRINTLY-".rand()."E";
        return view('admin.coupons.add', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
          'name' => 'required|unique:coupon,name',
            'code' => 'required|unique:coupon,code',
            'type' => 'required',


        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'code.required' => __('public.filed_required'),


            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $code = $request->post('code');
        $type = $request->post('type');
         $date_start = $request->post('date_start');
          $date_end = $request->post('date_end');
          $uses_customer = $request->post('uses_customer');
          $status = $request->post('status');
 $discount = $request->post('precentage') ?? 0;
 $total = $request->post('money') ?? 0;

       
        DB::insert("INSERT INTO `coupon`(`name`, `user_id`, `code`, `type`, `discount`, `total`, `date_start`, `date_end`, `uses_total`, `uses_customer`, `status`, `created_at`, `updated_at`)
         VALUES ('$name','".Auth::id()."','$code','$type','$discount',
         '$total','$date_start','$date_end','0','$uses_customer','$status','$created_at','$updated_at')");
        return redirect()->back()->with('success', __('public.added_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit($id , Product $product)
    {
        $data = [];
  
            $data["coupon"] = DB::selectOne("select * from `coupon` where `id` = '$id' ");

        return view('admin.coupons.edit', $data);
    }

  
    public function update($id ,Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
           'name' => 'required|unique:coupon,name,'.$id,
            'code' => 'required|unique:coupon,code,'.$id,
            'type' => 'required',


        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'code.required' => __('public.filed_required'),


            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $code = $request->post('code');
        $type = $request->post('type');
         $date_start = $request->post('date_start');
          $date_end = $request->post('date_end');
          $uses_customer = $request->post('uses_customer');
          $status = $request->post('status');
 $discount = $request->post('precentage') ?? 0;
 $total = $request->post('money') ?? 0;

       
        DB::insert("update  `coupon` set `name` ='$name' , `code` ='$code', `type`='$type', `discount`='$discount', `total`='$total', `date_start`='$date_start', `date_end`='$date_end',  `uses_customer`='$uses_customer', `status`='$status',  `updated_at` = '$updated_at' where id = '$id' and user_id = '".Auth::id()."'");
        return redirect()->back()->with('success', __('public.update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::delete("delete  from `coupon` where id = '$id' and user_id = '".Auth::id()."'");
        return redirect()->back()->with('success', __('public.deleted_success'));
    }
}
