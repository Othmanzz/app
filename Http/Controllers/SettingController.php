<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


    public function settings(){
        $data =  [];
        $data["settings"] = DB::select("select * from settings order by id desc");



        return view("admin.setting.settings",$data);
   }

    public function setting($id){
         $data =  [];
         $data["setting"] = DB::selectOne("select * from settings where id = $id");

         if(!$data["setting"]){
             return view(404);
         }
          $data["id"] = $id;
         return view("admin.setting.setting",$data);
    }

    public function edit_setting($id , Request $request){
        $data =  [];
        $data["setting"] = DB::selectOne("select * from settings where id = $id");

        if(!$data["setting"]){
            return view(404);
        }
        $name = $request->post('name');
        $desc = $request->post('desc');

        DB::update("update settings set `name` = '$name' , `desc` = '$desc' where `id`  = '$id'");
        return redirect()->back()->with('success', __('public.added_success'));

    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function edit(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Setting $setting)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
