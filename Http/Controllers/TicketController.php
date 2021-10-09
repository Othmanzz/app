<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Ui\Presets\React;

class TicketController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        //  $this->middleware('canTalk');
    }

    public function index()
    {
        $data = [];

        $type = ProfileController::get_user_info(Auth::id())->type;
        $data["type"] = $type;
        if($type == 0){
            $data["tickets"] = DB::table("ticket_messages")->where('user_id', '=', ''.Auth::id())->groupBy('ticket_id')->orderByRaw('id DESC')->paginate(15)->appends(request()->query());
            return view("site.help.tickets", $data);

        }else{
            $data["tickets"] = DB::table("ticket_messages")->groupBy('ticket_id')->orderByRaw('id DESC')->paginate(15)->appends(request()->query());
            return view("admin.tickets", $data);

        }

    }
    public function show_ticket($id)
    {
        $data = [];
        $ticket = DB::selectOne("select * from tickets where id = '$id' ");
        if (!$ticket) {
            return view(404);
        }
        if (!ProfileController::own_ticket($ticket->id, Auth::id())) {
            return view(404);
        }
        $type = ProfileController::get_user_info(Auth::id())->type;


        if ($ticket) {
            $data["ticket"] = $ticket;
            if ($type == 1) {
                $data["message"] = DB::select("select * from ticket_messages where ticket_id = '$ticket->id' ");
                return view("admin.ticket", $data);

            }  else {
                $data["message"] = DB::select("select * from ticket_messages where ticket_id = '$ticket->id' ");
            }
            return view("admin.ticket", $data);
        }


        return view(404);
    }
    public function send_message($id, Request $request)
    {
        $message = $request->post("message");
        $file = $request->file('file');
        ProfileController::add_message($id, $message, $file);
        return redirect()->back()->with('success', __('public.send_done'));
    }

    public static function get_ticket_user_id($id)
    {
        $ticket = DB::selectOne("select id from tickets where id = $id");
        return $ticket ? $ticket->id : 0;
    }
}
