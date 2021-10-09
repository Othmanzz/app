<?php

namespace App\Http\Controllers;

use App\Models\Rollup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RollupController extends Controller
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
        //
    }

    public function rollup_sizes(){
        $data = [];
        $data['rollups_size'] = DB::select("select * from rollups_size  order by id desc");
        return view('admin.rollup.rollups_size' ,$data);
       }
    public function add_rollup_sizes(){
        return view('admin.rollup.add_rollup_size');
    }

    public function store_rollup_sizes(Request $request){
     $rules = [
            'name' => 'required|unique:rollups_size,name',
            'price' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            'price.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);

        DB::insert("insert into `rollups_size` (`name` ,`price`) VALUES ('" . $request->post('name') . "' , '" . $request->post('price') . "' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }
}
