<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $data = [];
        $data["user"] = DB::selectOne("select * from users as u left join customer as c on u.id = c.user_id where u.id = '".Auth::id()."'");
        $data["wallet"] = DB::selectOne("select * from `wallets`   where user_id = '".Auth::id()."'");

        return view('profile',$data);
    }
    public function wallet()
    {
        $data = [];
        $data["user"] = DB::selectOne("select * from users as u left join customer as c on u.id = c.user_id where u.id = '".Auth::id()."'");
        $data["wallet"] = DB::selectOne("select * from `wallets`   where user_id = '".Auth::id()."'");

        return view('wallet',$data);
    }


    public function update_password(Request $request){
        $rules = [

            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [


        ];
        $request->validate($rules, $rules_messages);

        $old_password = $request->post('old_password');
    $new_password = $request->post('password');


    if (Hash::check($old_password, Auth::User()->password)) {
      $new_password = Hash::make($new_password);
      DB::update("update `users` set `password` = '$new_password' where id = '" . Auth::id() . "' ");
      return redirect()->back()->with('success', __('public.update_success'));
    } else {
      return redirect()->back()->with('error', __('public.password_not_match'));
    }
    }
    public static function arrayPaginator($array,  $request)
    {
      $page = $request->get('page', 1);
      $perPage = 10;
      $offset = ($page * $perPage) - $perPage;

      return new LengthAwarePaginator(
        array_slice($array, $offset, $perPage, true),
        count($array),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
      );
    }

    public function myOrders(Request $request)
    {
        $data = [];

        $data["orders"] = DB::select("select * from `orders` where `user_id` = '" . Auth::id() . "' ");
        $data["orders"] = $this->arrayPaginator($data["orders"] , $request);
        return view('site.profile.myorder');
    }


    public static function getUser($userId)
    {
        return DB::selectOne("select * from `users` where `id` = '$userId'");
    }

    public static function getWalletBalance()
    {
         $wallet = DB::selectOne("select * from `wallets` where `user_id` = '".Auth::id()."'");
         if($wallet){
            return $wallet->amount;

         }
         return 0;
    }

     public function edit(){
         $data  =[];

         $data["user"] = DB::selectOne("select * from `users` where `id` = '".Auth::id()."'");
         return view('site.profile.edit',$data);
     }
    public function update_profile(Request $request)
    {
        $email = $request->post('email');
        $name = $request->post('name');
        $phone = $request->post('phone');
        $gender = $request->post('gender');
        $country = $request->post('country');

        $student = $request->post('radio2');

        $old_password = $request->post('old_password');
        $new_password = $request->post('password');

        if(isset($name)){
            DB::update("update `users` set  `name` = '$name' where id = '" . Auth::id() . "' ");

        }
        if(isset($country)){
            DB::update("update `users` set  `country` = '$country' where id = '" . Auth::id() . "' ");

        }
        if(isset($gender)){
            DB::update("update `users` set  `gender` = '$gender' where id = '" . Auth::id() . "' ");

        }
        ProfileController::update_customer($request ,Auth::id());

        if(isset($email)){
           $check_email = DB::selectOne("select email , id from users where email = '$email' and id != '".Auth::id()."' ");
             if($check_email){
                return redirect()->back()->with('error', __('public.email_exsist'));

             }
            DB::update("update `users` set `email` = '$email'  where id = '" . Auth::id() . "' ");

        }
        if(isset($student)){

             DB::update("update `users` set `student` = '$student'  where id = '" . Auth::id() . "' ");

         }

        if(isset($phone)){
            $check_phone = DB::selectOne("select phone , id from users where phone = '$phone' and id != '".Auth::id()."' ");
              if($check_phone){
                 return redirect()->back()->with('error', __('public.phone_exsist'));

              }
             DB::update("update `users` set `phone` = '$phone'  where id = '" . Auth::id() . "' ");

         }
        if (isset($new_password)) {
            if (Hash::check($old_password, Auth::User()->password)) {
                $new_password = Hash::make($new_password);
                DB::update("update `users` set `password` = '$new_password' where id = '" . Auth::id() . "' ");
            } else {
                return redirect()->back()->with('error', __('public.password_not_match'));
            }
        }


        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/users/';
            $photo->move($destinationPath, $input);
        }else{
            $input = $request->post('old_photo');
        }
        DB::update("update `users` set `photo` = '$input' where id = '" . Auth::id() . "' ");
        ProfileController::update_customer($request ,Auth::id());

        return redirect()->back()->with('success', __('public.update_success'));

    }



    public static function getOS()
    {

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $os_platform = "Unknown OS Platform";

        $os_array = array(
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        );

        foreach ($os_array as $regex => $value){
            if (preg_match($regex, $user_agent)){
                $os_platform = $value;

            }

        }
        return $os_platform;

    }


    public static function getBrowser()
    {

        global $user_agent;

        $browser = "Unknown Browser";

        $browser_array = array(
            '/msie/i' => 'Internet Explorer',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/edge/i' => 'Edge',
            '/opera/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i' => 'Handheld Browser'
        );

        foreach ($browser_array as $regex => $value){
            if (preg_match($regex, $user_agent)){
                $browser = $value;

            }
        }

        return $browser;

    }
        public static function add_message($ticket_id , $message,$file ){
            $get_ticket = DB::selectOne("select * from tickets where id = $ticket_id ");

            if(!$get_ticket){
               return response()->json(['success' => '0' ]);

            }
            if(!ProfileController::own_ticket($get_ticket->id , Auth::id())){
                     return response()->json(['success' => '0' ]);

            }
            $type = ProfileController::get_user_info(Auth::id())->type;
            if($type == 0){
                $company_id = Auth::id();
                 $admin_id = 0;

            }else{
                $company_id = 0;
                 $admin_id = Auth::id();
            }

            $input = '';
            $has_file = 0;
            if ($file) {
                $input = time() . '.' . $file->getClientOriginalExtension();
                $destinationPath = base_path() . '/uploads/tickets_messages/';
                $file->move($destinationPath, $input);
                $has_file = 1;
            }
            // store tikcets

            DB::insert("insert into  ticket_messages (`file` ,`has_file`, `message` ,`type`,`ticket_id`,`company_id`,`admin_id`)
            VALUES ('$input' ,'$has_file', '$message' , '$type','$ticket_id','$company_id','$admin_id') ");
         return response()->json(['success' => '1','type' => $type ,'has_file'=>$has_file , 'message' =>  $message ]);

        }
        public static function own_ticket($ticket_id , $user_id){

            if(ProfileController::get_user_info(Auth::id())->type == 0){
        $per = DB::select("select * from tickets as t left join users as u on u.id =  t.user_id   where u.user_id = '".$user_id."' and t.id = $ticket_id ");

            }elseif(ProfileController::get_user_info(Auth::id())->type == 1){
                $per = true;
            }
            if($per){
                return true;
            }
            return false;
        }
        public static function get_user_info($id){
            return DB::selectOne("select type  ,phone, name , email from users where id = $id");
        }
public static function get_user_comission($user_id , $total){
$user= DB::selectOne("select profit from users where id = $user_id");
if($user){
     return ( $user->profit / 100 ) * $total;
}
return 0;
}

public static function update_customer(Request $request , $user_id){
$user= DB::selectOne("select u.id , c.user_id from users as u left join customer as c on c.user_id = u.id where u.id = $user_id");
if($user){
    DB::update("UPDATE `customer` SET `user_id`='$user_id',`universty`='".$request->post('universty')."',`college`='".$request->post('college')."',`specialist`='".$request->post('specialist')."' WHERE `user_id` = '$user_id'");
     return true;
}
return false;
}
public static function insert_customer(Request $request , $user_id){
$user= DB::selectOne("select id from users  where id = $user_id");
if($user){
    DB::insert("insert into  `customer` (`user_id`,`universty`,`college`,`specialist`) VALUES ('$user_id','".$request->post('universty')."','".$request->post('college')."','".$request->post('specialist')."')");
     return true;
}
return false;
}
public static function insert_safer(Request $request , $user_id){
    $user= DB::selectOne("select id from users  where id = $user_id");
    if($user){
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        DB::insert("insert into  `representatives` (`user_id`,`code`,`user_discount`,`safer_discount`)
        VALUES ('$user_id','".$request->post('code')."','".$request->post('user_discount')."','".$request->post('safer_discount')."')");
        DB::insert("INSERT INTO `coupon`(`name`, `user_id`, `code`, `type`, `discount`, `total`, `date_start`, `date_end`, `uses_total`, `uses_customer`, `status`, `created_at`, `updated_at`)
        VALUES ('".$request->post('code')."','".$user_id."','".$request->post('code')."','3','".$request->post('user_discount')."',
        '','','','0','0','1','$created_at','$updated_at')");

         return true;
    }
    return false;
    }
    public static function get_safer_data($userId)
    {
        return DB::selectOne("select * from `representatives` where `user_id` = '$userId'");
    }
  public static function get_customer_data($userId)
    {
        return DB::selectOne("select * from `customer` where `user_id` = '$userId'");
    }
}
