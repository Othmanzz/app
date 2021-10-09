<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'min:8'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'phone2' => $data['phone2'],

            'password' => Hash::make($data['password']),
        ]);
        $user_id =DB::getPdo()->lastInsertId();

        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $check_if_exist = DB::selectOne("select user_id from users_coupons where user_id = $user_id");
        if(!$check_if_exist){
            $code = rand(0,1000);
            DB::insert("insert into users_coupons (`user_id`,`used`,`allowed`,`code`)
            VALUES ('$user_id', '0','1','$code')");

        }
        DB::insert("insert into wallets (`user_id` , `amount`,`created_at` ,`updated_at`) VALUES ('$user_id', '5000','$created_at','$updated_at')");

        $comment = "اشتراك جديد من ".$data['name'];
                 NotificationController::add_notification(1,$user_id, $comment, 1, '/admin/users/'.$user_id.'?');

        return $user;
    }
}
