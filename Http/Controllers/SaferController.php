<?php

namespace App\Http\Controllers;

use App\Models\Representative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaferController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('issafer');
    }
   
    public function orders(){
        $data = [];
        $data["orders"] = DB::select("select o.id ,o.name , s.total , o.created_at , o.address from orders as o left join safer_profit as s on s.order_id = o.id where s.user_id = '".Auth::id()."' ");
        return view('admin.safer.orders', $data);

    }

}
