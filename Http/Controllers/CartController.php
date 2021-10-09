<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Contracts\Session\Session as SessionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PharIo\Manifest\Author;
use Symfony\Component\HttpFoundation\Session\Session;

class CartController extends Controller
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

        $data = [];
        $data["covers"] = [];
        $total_price = 0;
        $data["custom_products"] = [];
        $items = 0;
        $data["products"] = DB::select("select c.id as c_id , p.image , p.id , c.quantity , p.product_name ,p.desc, p.price from `carts` as c left join products as p on
         c.product_id = p.id where c.type = '0' and c.user_id = '" . Auth::id() . "'");
         if($data["products"]){
            $items = 1;
         }


         $get_carts = DB::select("select p.id as c_id from `carts`
         as c left join custom_products as p on
         c.product_id = p.id   where c.type = '1' and c.user_id = '".Auth::id()."' ");
         $files = [];
         foreach ($get_carts as $cart) {
             $files[] = $cart->c_id;
         }
        $files = implode("','",$files);
        $files_s = "'$files'";


           $covers = DB::select("select m.price ,c.name , m.cover_id , c.photo,m.custom_product  , m.id as m_cover_id from cover_type as c left join merged_files_cover as m on c.id = m.cover_id  where m.custom_product in ($files_s)  ");
        foreach($covers as $cover){
            $filesarray= [];

           $files = DB::select("select * from `custom_products_files` as cf left join cover_files_orders as co on co.file = cf.id  where cf.id > 0 and co.m_id = $cover->m_cover_id order by co.order ");


            foreach ($files as $file) {
                $prop = CartController::get_file_prop($file->file);




            $filesarray[] =  array(
                 'number_of_pages' => $file->number_of_pages,
                 'file' => $file->preview_name,
                 'id' =>$file->file,
                 'prop' => $prop,
                 'total' => round($file->total,2),
                 'quantity' => $file->quantity

             );

        }
         $data["covers"][] = array(
             'id' => $cover->cover_id,
             'm_cover_id' => $cover->m_cover_id,
             'name' =>$cover->name,
             'cover_price' => $cover->price,
             'photo' =>$cover->photo,
              'custom_product_id' => $cover->custom_product,
             'files' => $filesarray,
         );
        }

        $get_carts =DB::select("select p.id as c_id from `carts`
        as c left join custom_products as p on
        c.product_id = p.id   where c.type = '1' and c.user_id = '".Auth::id()."' ");
        $files = [];
        foreach ($get_carts as $cart) {
            $files[] = $cart->c_id;
        }
       $files = implode("','",$files);
       $files = "'$files'";
    if($files != ""){
        $custom_products= DB::select("select * from custom_products_files  where custom_product in ($files) ");

        foreach($custom_products as $file){

      $check = DB::selectOne("select * from  cover_files_orders where  `file` = $file->id    ");
           if($check){
            $items = 1;
            $prop = $this->get_file_prop($file->id);
            $data["custom_products"][] = array(
                'number_of_pages' => $file->number_of_pages,
                'file' => $file->file,
                'id' =>$file->id,
                'custom_product' =>$file->custom_product,
                'prop' => $prop,
                'total' => $file->total,
                'quantity' => $file->quantity
            );
           }

        }

       }else{
        $data["custom_products"] =[];
       }
        if ($items == 0) {
            return redirect('/')->with('error', 'السلة فارغة');
        }

        foreach ($data["products"] as $product) {
            $total_price += $product->quantity * $product->price;
        }
         $get_total = DB::selectOne("select SUM(total) as total from  custom_products where user_id = '".Auth::id()."' and complete = 0");
        if ($get_total) {
            $total_price += $get_total->total;
        }
        $data["total_price"] = $total_price;
        return view('site.cart.cart', $data);
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
        $data = [];
        $quantity = $request->post('quantity') ?? 1;
        $productId = $request->post('product_id');
        $type = $request->post('type') ?? 0;
        $userId = Auth::id();
        $ip = $request->ip();
        $checkCart = DB::selectOne("select * from carts where `user_id` = '$userId' and `product_id` = '$productId' ");
        if ($checkCart) {
            $quantity = $checkCart->quantity + $quantity;
            DB::update("update carts set quantity = '$quantity' where product_id = '$productId' and user_id = '$userId' and `id` = '$checkCart->id' ");
            $data["message"] = "تم تحديث السلة";
        } else {
            DB::insert("insert into carts (`user_id`,`product_id`,`quantity`,`ip` , `type`)
            VALUES ('$userId','$productId','$quantity','$ip' ,'$type') ");
            $data["message"] = "تم الاضافة الي السلة";
        }
        $carts = DB::select("select * from carts  where user_id = '" . Auth::id() . "'");

        if ($type == 1) {
            DB::update("update custom_products set complete = 0 where id = $productId");
        }

        return response()->json(['success' => '1', 'total' => count($carts), 'data' =>  $data]);
    }

    public function remove_from_cart(Request $request)
    {
        $key = $request->post('key');
        $userId = Auth::id();
        $checkCart = DB::selectOne("select * from carts where `user_id` = '$userId' and `id` = '$key' ");
        if ($checkCart) {
            DB::delete("delete from carts where key = '$key'");
        }

        return redirect()->back()->with('success', __('public.deleted'));
    }


    public function update_cart(Request $request)
    {
        foreach ($request->post['quantity'] as $key => $value) {
            $this->update($key, $value);
        }
        return redirect()->back()->with('success', __('public.updated_success'));
    }
    public function  update($key, $quantity)
    {
        // $key = $request->post('key');
        // $quantity = $request->post('quantity');

        $userId = Auth::id();
        $checkCart = DB::selectOne("select * from carts where `user_id` = '$userId' and `id` = '$key' ");
        if ($checkCart) {
            DB::update("update carts set quantity = '$quantity'  where key = '$key'");
        }
    }

    public function myCartsForOrder($user_id)
    {
        $data = [];
        $carts = DB::select("select *  from `carts` where user_id = '" . $user_id . "' ");
        $data['cart_total'] = count($carts);
        foreach ($carts as $cart) {
        if($cart->type == 1){
            $product = DB::selectOne("select * from custom_products where id = $cart->product_id");
            $name = $product ?  '' :  '';
            $quantity = 1;
            $total = $product->total;
        }else{
            $product = DB::selectOne("select * from products where id = $cart->product_id");
            $name = $product->product_name;
            $quantity = $product->quantity;
            $total = $cart->quantity * $product->price;

        }


            $data["products"][] = array(
                'product_name' => $name,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'type' => $cart->type,
                'cart_id' => $cart->id,
                'total' => $total,

            );
        }

        return $data;
    }

         public function getitemsOfCart(){
            $carts = DB::select("select * from `carts`   where user_id = '" . Auth::id() . "' ");
            return count($carts);
         }
    public function getCartTotal($userId)
    {
        $data = [];
        $total = 0;
        $carts = DB::select("select c.user_id ,p.price  , c.quantity from `carts` as c left join products as p on c.product_id = p.id   where c.user_id = '" . $userId . "' ");
        foreach ($carts as $cart) {
            $total = $total + ($cart->price * $cart->quantity);
        }


        $carts = DB::select("select c.user_id ,p.total  , c.quantity from `carts` as c left join custom_products as p on c.product_id = p.id   where c.user_id = '" . $userId . "' ");
        foreach ($carts as $cart) {
            $total = $total + ($cart->total);
        }
        return $total;
    }


    public function destroy(Request $request)
    {
        $key = $request->post('key');
        $userId = Auth::id();
        $checkCart = DB::selectOne("select * from carts where `user_id` = '$userId' and `id` = '$key' ");
        if ($checkCart) {
            DB::update("DELETE FROM `carts` WHERE  `id` = '$key'");
            $carts = DB::select("select * from carts  where user_id = '" . Auth::id() . "'");

            return response()->json(['success' => '1', 'total' => count($carts), 'message' =>  'تم تعديل السلة']);
        }


        $carts = DB::select("select * from carts  where user_id = '" . Auth::id() . "'");

        return response()->json(['success' => '0', 'total' => count($carts), 'message' =>  'يةجد شئ خطا  ']);
    }

    public function use_code(Request $request)
    {

       $code = $request->post('code');
       $total = $request->post('total');
       session()->forget('code');
       session()->forget('discount');
       session()->forget('safer_discount');
 $today = date('Y-m-d h:i:s');
 $coupon = DB::selectOne("select * from coupon where code = '$code' and status = 1 ");

 if($coupon){
    $check = DB::selectOne("select * from coupon_history where coupon_id = '$coupon->id' and customer_id = '".Auth::id()."' ");
    if($check){
        session()->put('order_total', $total);

    return response()->json(['success' => '0', 'message'=>'تم استخدامه من قبل  ','total' =>$total." ريال "  ]);
    }
    if($coupon->type == 3){
        //safer

        $total =$total - (( ($coupon->discount / 100 ) * $total)+( ($coupon->discount / 100 ) * $total));
        session()->put('discount',( ($coupon->discount / 100 ) * $total));
        $get_safer = DB::selectOne("select * from representatives where user_id = '".$coupon->user_id."'");
        if($get_safer){
            session()->put('safer_discount',( ($get_safer->safer_discount / 100 ) * $total));

        }
        session()->put('code', $coupon->id);
        return response()->json(['success' => '1',  'message'=>'كود صحيح', 'total' =>round($total,2)." ريال " ]);

    }
 }else{
    session()->put('order_total', $total);

    return response()->json(['success' => '0', 'message'=>'كود خاطئ','total' =>$total." ريال "  ]);

 }
        $check_coupon = DB::selectOne("select * from coupon where code = '$code' and status = 1 and uses_total <= uses_customer and '$today' BETWEEN date_start and date_end ");
     if($check_coupon){
        $check = DB::selectOne("select * from coupon_history where id = '$check_coupon->id' and customer_id = '".Auth::id()."' ");
        if($check){
            session()->put('order_total', $total);

        return response()->json(['success' => '0', 'message'=>'تم استخدامه من قبل  ','total' =>$total." ريال "  ]);
        }
        if($check_coupon->type == 1){
    $total =$total - ( ($check_coupon->discount / 100 ) * $total);
             session()->put('discount',( ($check_coupon->discount / 100 ) * $total));

        }else{
               $total = $total - $check_coupon->total;
                            session()->put('discount', $check_coupon->total);

        }

         session()->put('code', $check_coupon->id);
        return response()->json(['success' => '1',  'message'=>'كود صحيح', 'total' =>round($total,2)." ريال " ]);

     }else{
                 session()->put('order_total', $total);

        return response()->json(['success' => '0', 'message'=>'كود خاطئ','total' =>$total." ريال "  ]);

     }
    }

    public function add_note(Request $request)
    {

       $note = $request->post('note');
       session()->forget('note');

         session()->put('note', $note);

        return response()->json(['success' => '1',  'message'=>' ']);


    }
		public static function get_file_prop($file_id){
 $prop = 'لم يتم تحديد الخصائص بعد';
$get_file = DB::selectOne("select price_id from custom_products_files where id = $file_id" );
		  $get_file_prop = DB::selectOne("select * from price_list where id = $get_file->price_id");
                if($get_file_prop){
                    $paper_type = DB::selectOne("select * from papers_type where id = $get_file_prop->paper_type");
                      if($paper_type){
						  $paper_type_name = $paper_type->name;
					  }else{
						  $paper_type_name = '';
					  }
                    $paper_size = DB::selectOne("select * from papers_size where id = $get_file_prop->paper_id");
					  if($paper_size){
						  $paper_size_name = $paper_size->name;
					  }else{
						  $paper_size_name = '';
					  }
                    $printer_color = DB::selectOne("select * from printer_color where id = $get_file_prop->printer_color");
					  if($printer_color){
						  $printer_color_name = $printer_color->name;
					  }else{
						  $printer_color_name = '';
					  }
                    $printer_method = DB::selectOne("select * from printer_method where id = $get_file_prop->printer_method");
					  if($printer_method){
						  $printer_method_name = $printer_method->name;
					  }else{
						  $printer_method_name = '';
					  }
                    $paper_slice = DB::selectOne("select * from papers_slice where id = $get_file_prop->paper_slice");
					  if($paper_slice){
						  $paper_slice_name = $paper_slice->name;
					  }else{
						  $paper_slice_name = '';
					  }
                    $printer_type = DB::selectOne("select * from printer_type where id = $get_file_prop->printer_type");
					  if($printer_type){
						  $printer_type_name = $printer_type->name;
					  }else{
						  $printer_type_name = '';
					  }

                    $prop = $paper_size_name.'-'.$paper_type_name.'-'.$printer_color_name.'-'.$printer_method_name.'-'.$printer_type_name.'-'.$paper_slice_name;
                }

				return $prop;
	}
}
