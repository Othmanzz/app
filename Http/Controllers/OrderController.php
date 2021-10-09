<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler\Profile;

class OrderController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = [];
        $orders = DB::select("select * from orders where user_id = '".Auth::id()."' order by id desc");

$data["orders"] = [];
        foreach($orders as $order){
        $number_of_printed_file =DB::select("select order_id from orders_products where order_id  = $order->id and type =1 ") ;
        $number_of_products = DB::select("select order_id from orders_products where order_id  = $order->id and type = 0") ;
            $data["orders"][] = array(
                'order_id' => $order->id,
                'status' => OrderController::getOrderStatus($order->id),
                'number_of_printed_file' => count($number_of_printed_file),
                'number_of_products' =>  count($number_of_products),
                'total' => $order->total,
                'date' => $order->created_at,

            );
        }

        return view('site.cart.orders',$data);
    }



    public function show_order($id){
        $data = [];
      $data["order"] = DB::selectOne("select * from orders where id = $id ");
      if(!$data["order"]){
return view(404);
      }
      if($data["order"]->payment_method == "cod"){
        $data["payment_method"] = "الدقع عند الاستلام";

      }elseif($data["order"]->payment_method == "wallet"){
        $data["payment_method"] = "المحفظة";

      }
        $data["order_products"] = DB::select("select * from `orders_products` as op left join products
        as p on op.product_id = p.id where op.type = 0 and op.order_id =  '$id'");
        $data["order_custom_products"] = DB::select("select * from `orders_products` as op left join
        custom_products_files as p on op.product_id = p.custom_product where op.type = 1 and op.order_id =  '$id' and p.id > 0");
        $data["number_of_files"] = count($data["order_custom_products"] );
//

           $data["order_stickers_products"] = DB::select("select * from `orders_products` as op left join stickers_products as p
           on op.product_id = p.id left join stickers_paper_prices as sp on p.price_id = sp.id where op.type = 2 and op.order_id =  '$id'");

           $data["order_personal_card_products"] = DB::select("select * from `orders_products` as op left join personal_cards_products as p
           on op.product_id = p.id left join personal_cards_prices as sp on p.price_id = sp.id where op.type = 3 and op.order_id =  '$id'");

           $data["order_posters_products"] = DB::select("select * from `orders_products` as op left join posters_products as p
           on op.product_id = p.id left join posters_size as sp on p.price_id = sp.id where op.type = 5 and op.order_id =  '$id'");

           $data["order_rollups_products"] = DB::select("select * from `orders_products` as op left join rollups_products  as p
           on op.product_id = p.id left join rollups_size  as sp on p.price_id = sp.id where op.type = 5 and op.order_id =  '$id'");

        return view('site.cart.order',$data);
    }

    public function add_order_review($id,Request $request){


    $print_rate = $request->post("print_rate") ?? 0;
    $cover_rate = $request->post("cover_rate") ?? 0;
    $recieved_rate = $request->post("recieved_rate") ?? 0;
    $delever_rate = $request->post("delever_rate") ?? 0;
    $notes = $request->post("notes") ?? '';
         DB::insert("insert into order_review (`order_id`,`user_id`,`print_rate`,`cover_rate`,`recieved_rate`,`delever_rate`,`notes`)
          VALUES ('$id','".Auth::id()."','$print_rate','$cover_rate','$recieved_rate','$delever_rate','$notes')");
          return redirect()->back()->with('success','تم تقيم الطلب');
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
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $payment_method = session()->get('pay_type');
        $address = session()->get('address_id');

        $currency = $request->post('currency') ?? 'SAR';
        $orderId = 0;

        $cart = new CartController();
        $get_products = $cart->myCartsForOrder(Auth::id());
        $total = session()->get("order_total");
        $user = ProfileController::getUser(Auth::id());
        if ($get_products["cart_total"] > 0) {
            // ready to create order
            DB::insert("insert into orders (`name`,`total`,`user_id`,`payment_method` ,
            `address`,`zone`,`city` ,`currency`,`created_at`,`updated_at`,`delivered`,`note`,`delivery_price`)
            VALUES ('$user->name' , '$total' , '" . Auth::id() . "','$payment_method' ,'$address','','','$currency','$created_at','$updated_at','0','".session()->get('note')."','".session()->get('delivery_price')."') ");
            $orderId =  DB::getPdo()->lastInsertId();

            foreach ($get_products["products"] as $product) {
                if($product["type"]){
                    DB::update("update custom_products set complete = 2 where id = '".$product["product_id"]."'");
                }
                DB::insert("insert into `orders_products` (`product_id`,`type`,`order_id` ,`quantity` , `total`)
                  VALUES ('" . $product["product_id"] . "' ,'" . $product["type"] . "','$orderId' , '" . $product["quantity"] . "' , '" . $product["total"] . "')");
                DB::delete("delete from carts where product_id = '" . $product["product_id"] . "' and user_id = '" . Auth::id() . "'");
            }

            $comment = "طلب جديد من $user->name";
         NotificationController::add_notification(Auth::id(),$orderId, $comment, 1, '/admin/show_aorder/'.$orderId.'?');
         $this->use_code(session()->get('code'),Auth::id() ,$orderId,session()->get('discount') );
         session()->forget('note');
         session()->forget('delivery_price');

          session()->forget('code');
           session()->forget('pay_type');
            session()->forget('order_total');
 session()->forget('discount');
        }

        return redirect('/checkout/' . $orderId);
    }
    public static function use_code($code_id , $user_id , $order_id,$amount){
         $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $coupon = DB::selectOne("select * from coupon where id = '$code_id' ");
        if($coupon){
            if($coupon->type == 3){
                DB::insert("INSERT INTO `safer_profit`( `user_id`, `order_id`, `total`, `created_at`) VALUES
                ('$coupon->user_id','$order_id','".session()->get('safer_discount')."','$created_at')");

            }else{
                DB::update("update coupon set uses_total = uses_total + 1 where id = '$code_id' ");
              $get_owner_compession =ProfileController::get_user_comission( $coupon->user_id , session()->get("order_total"));
                DB::insert("insert into comission (`user_id` , `total` , `order_id` , `coupon_id`) VALUES ('$coupon->user_id',' $get_owner_compession','$order_id','$coupon->coupon_id')");

            }
            DB::insert("INSERT INTO `coupon_history`(`coupon_id`, `order_id`, `customer_id`, `amount`, `date_added`, `created_at`, `updated_at`)
            VALUES ('$code_id','$order_id','$user_id','$amount','$created_at','$created_at','$updated_at')");

      }
    }


    public function add_address(Request $request)
    {

        $city = $request->post('city');
        $area = $request->post('area');
        $street = $request->post('street');
        $more = $request->post('more') ;

            // ready to create order
            DB::insert("insert into address (`city`,`area`,`street`,`more`,`user_id`)
            VALUES ('$city' , '$area' , '$street','$more'  , '".Auth::id()."') ");
            $orderId =  DB::getPdo()->lastInsertId();

            return response()->json(['success' => '1', 'message' => 'تم اضافة عنوان']);

    }

    public function remove_address(Request $request)
    {

        $id = $request->post('id');

            DB::insert("delete from address where id = $id and user_id = '".Auth::id()."'");

            return response()->json(['success' => '1', 'message' => 'تم حذف عنوان']);

    }


    public function areas(Request $request){
        $data = [];
        $city = $request->post('city');

        $data["area"] = DB::select("select * from area where city_id = '".$city."'");



        return response()->json(['success' => '1', 'data' => $data]);

    }
public static function get_address($address_id){
      $add = DB::selectOne("select * from address where   id =$address_id");
if($add){
    $city = DB::selectOne("select * from city where id ='".$add->city."'");
    $area = DB::selectOne("select * from area where id ='".$add->area."'");


        return  $city->name .'-'.$area->name .'-'.$add->street .'-'.$add->more;

}
return 'العنوان غير متاح';
}
    public function confirm_address(Request $request){
        $address_id = $request->post('address_id');
        session()->forget('address_id');

          session()->put('address_id', $address_id);
          $add = DB::selectOne("select * from address where user_id = '".Auth::id()."' and id =$address_id");

              $city = DB::selectOne("select * from city where id ='".$add->city."'");
              $area = DB::selectOne("select * from area where id ='".$add->area."'");

              $data['id'] = $add->id;

                  $data['city'] = $city->name ?? '';
                  $data['area'] = $area->name ?? '';
                  $data['street'] = $add->street;
                  $data['more'] = $add->more;


         return response()->json(['success' => '1',  'message'=>'تم تأكيد العنوان','data'=>$data]);
    }

    public function confirm_pay(Request $request){
        $pay_type = $request->post('pay_type');
        session()->forget('pay_type');

          session()->put('pay_type', $pay_type);


         return response()->json(['success' => '1']);
    }


    public function address(){
        $data = [];
        $address = DB::select("select * from address where user_id = '".Auth::id()."'");
        foreach($address as $add){
            $city = DB::selectOne("select * from city where id ='".$add->city."'");
            $area = DB::selectOne("select * from area where id ='".$add->area."'");
            $data["address"][] = array(
                'id' => $add->id,

                'city' => $city->name ?? '',
                'area' => $area->name ?? '',
                'street' => $add->street,
                'more' => $add->more
            );
        }


        return response()->json(['success' => '1', 'data' => $data]);

    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show($orderId)
    {
        $data = [];
        $get_order = DB::selectOne("select * from orders where id = '$orderId' and user_id = '" . Auth::id() . "'");
        if (!$get_order) {
            return view(404);
        }
        $data["order_custom_products"] = DB::select("select * from `orders_products` as op left join custom_products as p on op.product_id = p.id where op.type = 1 and op.order_id =  '$id'");

        $data["order_products"] = DB::select("select * from orders_products where order_id = '$get_order->id' ");

        return view('site.orders.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }

    public static function get_address_price($address_id){
$price = 0;
     $address = DB::selectOne("select * from `address` where `id` = '$address_id' ");
     if($address){
        $get_price = DB::selectOne("select * from `area` where `id` = '$address->area' ");

        if($get_price){
            $price = $get_price->price;

        }


     }

     return $price;
    }


    public static function add_order_history($orderId, $comment = '')
    {
        $get_order = DB::selectOne("select u.name , u.email , o.id ,o.user_id from orders as o left join users as u on o.user_id = u.id where o.id = '$orderId'");
        if (!$get_order) {
            return null;
        }
        if ($comment == "" || empty($comment)) {
            $comment = "تم تغير حالة الطلب الي " . OrderController::getOrderStatus($orderId);
        }
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        DB::insert("insert into order_history (`order_id`, `comment` ,`created_at` , `updated_at`)
       VALUES ('$orderId', '$comment' , '$created_at','$updated_at')");
        // MailController::html_email($get_order->name, $get_order->email, 'تحديث الطلب', $comment);
    }



    public static function getOrderStatus($id)
    {

        $order_status = DB::selectOne("select o.status , os.name , os.id from orders as o left join order_status as os on o.status = os.id where o.id = '$id'");
        return isset($order_status) ? $order_status->name : '';
    }
}
