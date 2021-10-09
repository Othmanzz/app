<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StickerController extends Controller
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

   public function paper_types(){
    $data = [];
    $data['paper_types'] = DB::select("select * from stickers_paper_type  order by id desc");
    return view('admin.stickers.paper_type' ,$data);
   }

    public function paper_price_list($id, Request $request){
        $data = [];
        $data['id'] = $id;
        $data['papers_price'] = DB::select("select * from stickers_paper_prices  order by id desc");
        return view('admin.stickers.price_list' ,$data);
    }


    public function add_paper_types(){
        return view('admin.stickers.add_paper_type');
    }

    public function store_paper_types(Request $request){
   $rules = [
            'name' => 'required|unique:stickers_paper_type,name',
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);

        DB::insert("insert into `stickers_paper_type` (`name`) VALUES ('" . $request->post('name') . "' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function paper_sizes(){
        $data = [];
        $data['paper_sizes'] = DB::select("select * from stickers_paper_size  order by id desc");
        return view('admin.stickers.paper_size' ,$data);
       }
    public function add_paper_sizes(){
        return view('admin.stickers.add_paper_size');
    }

    public function store_paper_sizes(Request $request){
     $rules = [
            'name' => 'required|unique:stickers_paper_size,name',
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);

        DB::insert("insert into `stickers_paper_size` (`name`) VALUES ('" . $request->post('name') . "' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function paper_shapes(){
        $data = [];
        $data['paper_shapes'] = DB::select("select * from stickers_paper_shape  order by id desc");
        return view('admin.stickers.paper_shape' ,$data);
       }
    public function add_paper_shapes(){
        return view('admin.stickers.add_paper_shape');
    }

    public function store_paper_shapes(Request $request){
     $rules = [
            'name' => 'required|unique:stickers_paper_shape,name',
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);

        DB::insert("insert into `stickers_paper_shape` (`name`) VALUES ('" . $request->post('name') . "' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }





    public function stickers_prices(){
        $data = [];
        $data['price_list'] = DB::select("select * from stickers_paper_prices  order by id desc");
        return view('admin.stickers.price_list' ,$data);
    }


    public function add_stickers_prices(){


            $data = [] ;

            $data['papers_type'] =  DB::select("select * from stickers_paper_type   order by id desc");
            $data['papers_size'] =  DB::select("select * from stickers_paper_size   order by id desc");
            $data['papers_shape'] =  DB::select("select * from stickers_paper_shape   order by id desc");

         return view('admin.stickers.add_price_list',$data);
    }

    public function store_stickers_prices(Request $request){

   $rules = [
            'name' => 'required|unique:stickers_paper_prices',
            'paper_type' => 'required',
            'paper_size' => 'required',
            'paper_shape' => 'required',
            'price' => 'required',



        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            'type.required' => __('public.filed_required'),
            'paper_type.required' => __('public.filed_required'),
            'paper_size.required' => __('public.filed_required'),
            'paper_shape.required' => __('public.filed_required'),
            'price.required' => __('public.filed_required'),
            'price.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post("name");
        $price = intval($request->post("price"));
        $type = $request->post("paper_type") ?? 0;
        $shape = $request->post("paper_shape") ?? 0;
        $size = $request->post("paper_size") ?? 0 ;
        $checkPrice = DB::selectOne("select * from  `stickers_paper_prices` where
        `paper_type` = '$type' and `paper_type` = '$type' and `paper_size` = '$size'
        and `paper_shape` = '$shape'  ");
        if($checkPrice){
            return redirect()->back()->with('error', __('public.price_already_exist'));

        }

        DB::insert("INSERT INTO `stickers_paper_prices` (`name`, `paper_shape`,`paper_size`, `paper_type`,`price`)
          VALUES ('$name' , '$shape' ,'$size', '$type' , '$price') ");
        return redirect()->back()->with('success', __('public.added_success'));
    }



    public function edit_price_list($id, Request $request){
        $data = [];
        $data['id'] = $id;
        $data = [] ;
        $data["paper_list"] = DB::selectOne("select * from stickers_paper_prices  where id = '$id'");


        if(!$data['paper_list']){
            return view(404);
        }
        $paper = DB::selectOne("select * from papers_size where id = '".$data["paper_list"]->paper_id."' ");

        $data['id'] = $id;
     $data['papers_type'] =  DB::select("select * from stickers_paper_type   order by id desc");
        $data['papers_size'] =  DB::select("select * from stickers_paper_size   order by id desc");
        $data['papers_shape'] =  DB::select("select * from stickers_paper_shape   order by id desc");

        return view('admin.stickers.edit_price' ,$data);
    }
    public function update_price_list($id, Request $request){

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required|unique:papers_size,name,'.$id,
            'type' => 'required',
            'printer_type' => 'required',
            'printer_color' => 'required',
            'printer_method' => 'required',
            'price' => 'required|integer|min:1',
            'papers_slice' => 'required|integer|min:1',


        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            'type.required' => __('public.filed_required'),
            'printer_type.required' => __('public.filed_required'),
            'printer_color.required' => __('public.filed_required'),
            'printer_method.required' => __('public.filed_required'),
            'price.required' => __('public.filed_required'),
            'paper_slice.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post("name");
        $price = intval($request->post("price"));
        $type = $request->post("type");
        $printer_type = $request->post("printer_type") ?? 0;
        $printer_color = $request->post("printer_color") ?? 0 ;
        $printer_method = $request->post("printer_method") ?? 0;
        $paper_slice = $request->post("papers_slice") ?? 0;
        $get_paper_id = DB::selectOne("select paper_id from `price_list`  where id = '".$id."' ");
        $checkPrice = DB::selectOne("select * from  `price_list` where `paper_id` = '$$get_paper_id->paper_id' and
        `paper_type` = '$type' and `printer_type` = '$printer_type' and `printer_color` = '$printer_color'
        and `printer_method` = '$printer_method' and  `paper_slice` = '$paper_slice' and id != $id");
        if($checkPrice){
            return redirect()->back()->with('error', __('public.price_already_exist'));

        }

        DB::update("UPDATE  `price_list` set `paper_type` = '$type' ,`name` = '$name', `paper_id` = '$id',
         `printer_type` = '$type', `printer_method` = '$printer_method', `printer_color` = '$printer_color',
          `paper_slice`= '$paper_slice', `created_at` = '$created_at', `updated_at` = '$updated_at',
          `price` = '$price' where `id` = '$id'");
        return redirect()->back()->with('success', __('public.added_success'));
    }
}
