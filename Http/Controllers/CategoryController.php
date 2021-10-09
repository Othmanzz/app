<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('isadmin');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        $data['categories'] = DB::select("select * from categories order by id desc");
        return view('admin.categories.index' ,$data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [];

        return view('admin.categories.add',$data);
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
            'name' => 'required|unique:categories,name',
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.exists' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $photo = $request->file('photo');
        $input = '';
        if (isset($photo)) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/categories/';
            $photo->move($destinationPath, $input);
        }else{
            $input = '';
        }
        DB::insert("insert into `categories` (`name` , `photo`,  `created_at`  , `updated_at`) VALUES ('" . $request->post('name') . "' ,'$input','$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit($id , Category $category)
    {
        $data = [];
        $data['category'] = DB::selectOne("select * from `categories` where `id` ='$id' ");
        if(!$data['category']){
        return view(404);
        }
        return view('admin.categories.edit' ,$data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update($id ,Request $request, Category $category)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required|unique:categories,name,' . $id,
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $photo = $request->file('photo');
       $name = $request->post('name');

        $input = '';
        if (isset($photo)) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/categories/';
            $photo->move($destinationPath, $input);
        }else{
            $input = $request->post('old_photo');
        }
        DB::insert("update  `categories`  set `name` = '$name' , `photo` = '$input',  `created_at` = '$created_at'  , `updated_at` = '$updated_at' where `id` = '$id' ");
        return redirect()->back()->with('success', __('public.added_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        DB::delete("delete from `categories` where id = $id ");
        return redirect()->back()->with('success', __('public.deleted'));

    }
}
