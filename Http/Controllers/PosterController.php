<?php

namespace App\Http\Controllers;

use App\Models\Poster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosterController extends Controller
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

    public function posters_sizes(){
        $data = [];
        $data['posters_size'] = DB::select("select * from posters_size  order by id desc");
        return view('admin.poster.posters_size' ,$data);
       }
    public function add_posters_sizes(){
        return view('admin.poster.add_poster_size');
    }

    public function store_posters_sizes(Request $request){
     $rules = [
            'name' => 'required|unique:posters_size,name',
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

        DB::insert("insert into `posters_size` (`name` ,`price`) VALUES ('" . $request->post('name') . "' , '" . $request->post('price') . "' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }
}
