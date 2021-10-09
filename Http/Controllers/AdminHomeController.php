<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class AdminHomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $data= [];
      




   
    if(ProfileController::get_user_info(Auth::id())->type == 1){
        $users = DB::select("select id from users ");
        $orders = DB::select("select id from orders ");
        $revenue = DB::selectOne("select SUM(total) as total  from orders  ");

          $data["number_of_users"] = count($users);
          $data["number_of_orders"] = count($orders);


          $latest_orders = DB::select("select * from orders order by id desc limit 6");
          $data["orders"] = $latest_orders;
          $data["revenue"] = $revenue->total ?? 0;

          $users = User::select('id', 'created_at')
          ->get()
          ->groupBy(function($date) {
              //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
              return Carbon::parse($date->created_at)->format('m'); // grouping by months
          });

          $usermcount = [];
          $userArr = [];

          foreach ($users as $key => $value) {
              $usermcount[(int)$key] = count($value);
          }

          for($i = 1; $i <= 12; $i++){
              if(!empty($usermcount[$i])){
                  $userArr[$i] = $usermcount[$i];
              }else{
                  $userArr[$i] = 0;
              }
          }
    $data["users_gained"] = $userArr;


    $revneues = Order::select('total','created_at')
          ->get()
          ->groupBy(function($date) {
              //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
              return Carbon::parse($date->created_at)->format('m'); // grouping by months
          });

          $revnuemcount = [];
          $revnueArr = [];

          foreach ($revneues as $key => $value) {
              $t = 0;
              foreach($value as $v){
                $t = $t + $v->total;

              }
              $revnuemcount[(int)$key] = $t;
          }

          for($i = 1; $i <= 12; $i++){
              if(!empty($revnuemcount[$i])){
                  $revnueArr[$i] = $revnuemcount[$i];
              }else{
                  $revnueArr[$i] = 0;
              }
          }
          $data["revnues"] = $revnueArr;





          $orders = Order::select('id', 'created_at')
          ->get()
          ->groupBy(function($date) {
              //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
              return Carbon::parse($date->created_at)->format('m'); // grouping by months
          });

          $ordercount = [];
          $orderArr = [];

          foreach ($orders as $key => $value) {
              $ordercount[(int)$key] = count($value);
          }

          for($i = 1; $i <= 12; $i++){
              if(!empty($ordercount[$i])){
                  $orderArr[$i] = $ordercount[$i];
              }else{
                  $orderArr[$i] = 0;
              }
          }
    $data["orders_gained"] = $orderArr;
       $data["customers_number"] = count(DB::select("select id from users where type = 0"));
    $data["rep_number"] = count(DB::select("select id from users where type = 3"));
    $data["publishers_number"] = count(DB::select("select id from users where type = 2"));
    $data["printmans_number"] = count(DB::select("select id from users where type = 4"));
       return view('admin.home',$data);

    }elseif(ProfileController::get_user_info(Auth::id())->type == 7){
     
       $data["money"] =DB::selectOne("select SUM(total) as total from safer_profit where user_id = '".Auth::id()."'");
         $data["orders"] = DB::selectOne("select  COUNT(o.id) as total from orders as o left join safer_profit as s on s.order_id = o.id where where s.user_id = '".Auth::id()."' 
         ");
 return view('admin.rep_home',$data);
    }

        
    }




}
