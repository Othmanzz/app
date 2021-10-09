<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mail;
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $data = array();

        $data['notifications']  = DB::select("select * from `notifications` where user_id =" . Auth::id())[0];
        return view('admin.notifications', $data);
    }
    public function real_time_notification(){
      $data = [];

      $notifications_query = "select * from `notifications` where  `flashed` = 0 and `user_id` = '".Auth::id()."'   ";
      $notifications = DB::select($notifications_query." order by id desc");
      $data["notifications"] = [];
      foreach ($notifications as $notification) {
        $data["notifications"][]  = array(
          'content' => __(''.$notification->content),
        );
        DB::update("update `notifications` set `flashed` = 1 where `id` = '".$notification->id."' ");

      }
      return response()->json(['success' => '1', 'data' =>  $data]);
    }

    public static  function add_notification( $user_id, $content_id = 0, $content, $normal_user = 0, $route)
    {
        $create_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        DB::insert('insert into `notifications` (user_id , content_id , content ,
         `normal_user` , `route` , `created_at` , `updated_at`)
         VALUES (?,?,? ,?,? ,?,?)', [ $user_id, 0, $content, 1, $route, $create_at, $updated_at]);

        $id = DB::getPdo()->lastInsertId();
        $route =  $route . 'notification_id=' . $id;
        DB::update("update `notifications` set `route` = '$route' where `id` = '$id' ");

  $user = DB::selectOne("select email  ,name  from users where id = '".$user_id."'");


        if($user){


   $to_name = $user->name;
   $to_email = $user->email;
   $data = array('name'=>$to_name, "body"=> $content);

 //   Mail::send('admin.mail', $data, function($message) use ($to_name, $to_email) {
 //   $message->to($to_email, $to_name)
 //   ->subject('اشعار');
 //   $message->from('ejad@ejad.sa','اشعار');
 // });



        }
    }
}
