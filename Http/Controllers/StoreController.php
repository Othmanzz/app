<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = [];
        $s = request()->get("search");
        $from = request()->get("from");

        $data["categories"] = DB::select("select * from categories order by id desc");
         if(isset($s) && $s != ""){
            $data['products']  = DB::table("products")->join('users','products.from', '=', 'users.id')->where('products.product_name', 'like', '%' . $s . '%')->where('products.accepted','=','1')->orderByRaw('products.id DESC')->paginate(20)->appends(request()->query());

         }else{
            $data['products']  = DB::table("products")->join('users','products.from', '=', 'users.id')->where('products.accepted','=','1')->orderByRaw('products.id DESC')->paginate(20)->appends(request()->query());

         }

        return view('site.store.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [];
        $data["categories"] = DB::select("select * from categories order by id desc");


        return view('admin.products.add', $data);
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
            'name' => 'required',
            'desc' => 'required',
            'photo' => 'required',
            'categories' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'desc.required' => __('public.filed_required'),
            'photo.required' => __('public.filed_required'),
            'categories.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $price = $request->post('price');
        $desc = $request->post('desc');
        $categroy = $request->post('categories');


        $from = Auth::id();
        if($from == 0){
            $from = 0;
        }

        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/products/';
            $photo->move($destinationPath, $input);
        }

        DB::insert("insert into `products` (`from`,`name` ,`category`, `price`, `desc`,`image` ,  `created_at`  , `updated_at`)
        VALUES ('$from','$name' , '$categroy','$price', '$desc','$input',  '$created_at', '$updated_at' ) ");
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
        $data["product"] = DB::selectOne("select * from `products` where `id` = '$id' ");
        if(!$data["product"]){
            return view(404);
        }

        $data["categories"] = DB::select("select * from categories order by id desc");
        return view('admin.products.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update($id ,Request $request, Product $product)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'desc' => 'required',
            'categories' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'desc.required' => __('public.filed_required'),
            'categories.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $price = $request->post('price');
        $desc = $request->post('desc');
        $categroy = $request->post('categories');


        $from = Auth::id();
        if($from == 0){
            $from = 0;
        }

        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/products/';
            $photo->move($destinationPath, $input);
        }else{
            $input = $request->post('old_photo');
        }

        DB::insert("update  `products` set `name` = '$name' ,`category` = '$categroy', `price` = '$price',
        `desc` = '$desc',`image` = '$input' ,  `created_at` = '$created_at'  , `updated_at`= '$updated_at' where `id` = '$id'");
        return redirect()->back()->with('success', __('public.added_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
