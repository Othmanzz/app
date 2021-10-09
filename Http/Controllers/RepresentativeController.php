<?php

namespace App\Http\Controllers;

use App\Models\Representative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepresentativeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('isrepesntitive');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        $get_order = DB::select("select * from orders where represnt_id = '" . Auth::id() . "' order by id desc");
        // if (!$get_order) {
        //     return view(404);
        // }
        $data["order_route"] = 'reporders';
        $data["show_order_route"] = 'show_rporder';
        $data["orders"] = $get_order;
        return view('admin.orders.orders', $data);
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
     * @param  \App\Models\Representative  $representative
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = [];
        $get_order = DB::selectOne("select * from orders where represnt_id = '" . Auth::id() . "' and `id` = '$id' ");
        if (!$get_order) {
            return view(404);
        }
        $data["order_route"] = 'reporders';
        $data["show_order_route"] = 'show_rporder';
        $data["change_order_status"] = 'rpchange_order_status';
        $data["admin"] = false;

        $data["order"] = $get_order;
        $data['user'] = ProfileController::getUser($get_order->user_id);
        $data["order_status"] = DB::select("select * from order_status order by id desc");
        $data["order_history"] = DB::select("select *  from order_history where order_id = $id order by id desc");

        $data["order_products"] = DB::select("select * from `orders_products` as op left join products as p on op.product_id = p.id where op.type = 0 and op.order_id =  '$id'");
        $data["order_custom_products"] = DB::select("select * from `orders_products` as op left join
        custom_products as p on op.product_id = p.id where op.type = 1 and op.order_id =  '$id'");

        $data["covers"] = [];
foreach($data["order_custom_products"] as $get_file){

    if ($get_file->product_id) {
        $covers = DB::select("select m.price ,c.name , m.cover_id , c.photo,m.custom_product , m.files , m.id as  m_cover_id from cover_type as c left join merged_files_cover as m on c.id = m.cover_id where m.custom_product = $get_file->id  ");
foreach($covers as $cover){
        $files = DB::select("select * from `custom_products_files` where custom_product = $get_file->id and id in ($cover->files) ");

            $filesarray= '';

            foreach($files as $file){
                $get_file_prop = DB::selectOne("select * from price_list where id = $file->price_id");
                if($get_file_prop){
                    $paper_type = DB::selectOne("select * from papers_type where id = $get_file_prop->paper_type");
                    $paper_size = DB::selectOne("select * from papers_size where id = $get_file_prop->paper_id");
                    $printer_color = DB::selectOne("select * from printer_color where id = $get_file_prop->printer_color");
                    $printer_method = DB::selectOne("select * from printer_method where id = $get_file_prop->printer_method");
                    $paper_slice = DB::selectOne("select * from papers_slice where id = $get_file_prop->paper_slice");
                    $printer_type = DB::selectOne("select * from printer_type where id = $get_file_prop->printer_type");

                    $prop = $paper_size->name.'-'.$paper_type->name.'-'.$printer_color->name.'-'.$printer_method->name.'-'.$printer_type->name.'-'.$paper_slice->name;
                }else{
                    $prop = 'لم يتم تحديد الخصائص بعد';
                }

                // );
            }
            $data["covers"][] = array(
                'id' => $cover->cover_type,
                'name' =>$cover->name,
                'photo' =>'/uploads/cover_type/'.$cover->photo,

                'files' => $files,
            );
           }
        }


    }

        $data["order_stickers_products"] = DB::select("select * from `orders_products` as op left join stickers_products as p
        on op.product_id = p.id left join stickers_paper_prices as sp on p.price_id = sp.id where op.type = 2 and op.order_id =  '$id'");

        $data["order_personal_card_products"] = DB::select("select * from `orders_products` as op left join personal_cards_products as p
        on op.product_id = p.id left join personal_cards_prices as sp on p.price_id = sp.id where op.type = 3 and op.order_id =  '$id'");

        $data["order_posters_products"] = DB::select("select * from `orders_products` as op left join posters_products as p
        on op.product_id = p.id left join posters_size as sp on p.price_id = sp.id where op.type = 5 and op.order_id =  '$id'");

        $data["order_rollups_products"] = DB::select("select * from `orders_products` as op left join rollups_products  as p
        on op.product_id = p.id left join rollups_size  as sp on p.price_id = sp.id where op.type = 5 and op.order_id =  '$id'");

        return view('admin.orders.show', $data);
    }

    public function change_order_status($id, Request $request)
    {
        $status = $request->post('status') ?? 1;
        $comment = $request->post('comment') ?? '';

        $order = DB::selectOne("select id from orders where id = '$id' ");
        if ($order) {
            DB::update("update orders set `status` = '$status' where `id` = '$id' ");
               OrderController::add_order_history($id , $comment);
        }

        return redirect()->back()->with('success', __('public.sent_done'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Representative  $representative
     * @return \Illuminate\Http\Response
     */
    public function edit(Representative $representative)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Representative  $representative
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Representative $representative)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Representative  $representative
     * @return \Illuminate\Http\Response
     */
    public function destroy(Representative $representative)
    {
        //
    }


}
