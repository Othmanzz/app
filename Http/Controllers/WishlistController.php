<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    public function index()
    {

        $data = [];
        $data["carts"] = DB::select("select * from `carts` where `user_id` = '" . Auth::id() . "' ");
        return view('site.checkout.wishlist', $data);
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
        $userId = Auth::id();
        $ip = $request->ip();
        $checkWishlist = DB::selectOne("select * from wishlists where `user_id` = '$userId' and `product_id` = '$productId' ");
        if ($checkWishlist) {
            $quantity = $checkWishlist->quantity + $quantity;
            DB::update("update wishlists set quantity = '$quantity' where product_id = '$productId' and user_id = '$userId' and `id` = '$checkWishlist->id' ");
            $data["message"] = "تم تحديث قائمة امنياتي";
        } else {
            DB::insert("insert into wishlists (`user_id`,`product_id`,`quantity`,`ip` )
            VALUES ('$userId','$productId','$quantity','$ip' ) ");
            $data["message"] = "تم الاضافة الي قائمة امنياتي";
        }
        $wishlists = DB::select("select * from wishlists  where user_id = '" . Auth::id() . "'");

        return response()->json(['success' => '1', 'total' => count($wishlists), 'data' =>  $data]);
    }

    public function remove_from_wishlist(Request $request)
    {
        $key = $request->post('key');
        $userId = Auth::id();
        $checkCart = DB::selectOne("select * from wishlists where `user_id` = '$userId' and `id` = '$key' ");
        if ($checkCart) {
            DB::delete("delete from wishlists where key = '$key'");
        }

        return redirect()->back()->with('success', __('public.deleted'));
    }


    public function update_wishlist(Request $request)
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
        $checkCart = DB::selectOne("select * from wishlists where `user_id` = '$userId' and `id` = '$key' ");
        if ($checkCart) {
            DB::update("update wishlists set quantity = '$quantity'  where key = '$key'");
        }
    }
    public function destroy(Request $request)
    {
$key = $request->post('key');
         $userId = Auth::id();
        $checkWishlist = DB::selectOne("select * from wishlists where `user_id` = '$userId' and `id` = '$key' ");
        if ($checkWishlist) {
            DB::update("DELETE FROM `carts` WHERE  `id` = '$key'");
            $wishlist = DB::select("select * from wishlists  where user_id = '" . Auth::id() . "'");

            return response()->json(['success' => '1', 'total' => count($wishlist), 'message' =>  'تم تعديل قائمة امنياتي']);

        }


        $wishlist = DB::select("select * from wishlists  where user_id = '" . Auth::id() . "'");

        return response()->json(['success' => '0', 'total' => count($wishlist), 'message' =>  'يةجد شئ خطا  ']);

    }
}
