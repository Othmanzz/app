<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index()
    {
        $data = [];

        $data["branchs"] = DB::select("select * from branchs order by id desc ");
        return view('admin.branchs.index', $data);
    }

    public function create()
    {
        $data = [];

        return view('admin.branchs.add', $data);
    }


    public function store(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'desc' => 'required',
            'google_map' => 'required',
            'photo' => 'required',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'desc.required' => __('public.filed_required'),
            'google_map.required' => __('public.filed_required'),
            'photo.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $desc = $request->post('desc');

        $link = $request->post('google_map');


        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/banners/';
            $photo->move($destinationPath, $input);
        }

        DB::insert("insert into `branchs` (`name` ,`desc`, `google_map`,`photo` ,  `created_at`  , `updated_at`)
        VALUES ('$name','$desc','$link','$input',  '$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function edit($id)
    {
        $data = [];
        $data["branch"] = DB::selectOne("select * from `branchs` where `id` = '$id'");

        if (!$data["branch"]) {
            return view(404);
        }

        return view('admin.branchs.edit', $data);
    }

    public function update($id, Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'link' => 'required',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'link.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $link = $request->post('link');
        $photo = $request->file('photo');
        $input = '';
        if (isset($photo)) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/branchs/';
            $photo->move($destinationPath, $input);
        } else {
            $input = $request->post('old_image');
        }

        DB::insert("update `branchs` set `name` ='$name', `google_map` = '$link',`photo` = '$input' ,
        `created_at` = '$created_at' , `updated_at` = '$updated_at'
         where id = $id ");
        return redirect()->back()->with('success', __('public.updated_success'));
    }

}
