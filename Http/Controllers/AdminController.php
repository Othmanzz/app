<?php

namespace App\Http\Controllers;

use App\Exports\OrderExport;
use App\Exports\UserExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Ui\Presets\React;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('isadmin');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $data = [];

        return view('admin.home', $data);
    }

    public function users(Request $request)
    {
        $data = [];
        $type = $request->get("type");

        if(!is_numeric($type)){
            $type = '!=1';
        }else{
            $type = '='.$type;
        }
        $search = $request->get('s');

  $sort = $request->get('sort');
        $where = '';
        if($search){
            $where = " and u.name like '%$search%' ";
        }
        $t = $request->get("type")??0;
           $titles = ["عملاء",'مشرفين','ناشرين','المندوبين','عملاء الطباعة','عمال التغليف','السائقين','السفراء'];
        if(in_array( $t, [0,1,2,3,4,5,6,7])){
             $t=  $t;
        }else{
              $t= 0;
        }

        $data = [];
        $data["type"] =  $t;
         $data['title'] = $titles[$t];

        if($sort == 1){
$data["users"] = DB::select("SELECT count(o.id) as a , u.id as id , u.name as name  FROM `orders` as o left join users u on o.user_id = u.id $where group by u.id desc");
        }elseif($sort == 2){
   $data["users"] = DB::select("SELECT count(o.id) as a , u.id as id , u.name as name  FROM `orders` as o left join users u on o.user_id = u.id $where group by u.id asc");
        }else{
             $data["users"] = DB::select("select * from users as u where  u.type   $type $where order by id desc");
        }


        return view('admin.users.index', $data);
    }
    public function add_user(Request $request)
    {
                $type = $request->get("type") ?? 0;

     $titles = ["عملاء",'مشرفين','ناشرين','المندوبين','عملاء الطباعة','عمال التغليف','السائقين','السفراء'];

        if(in_array( $type, [0,1,2,3,4,5,6,7])){
            $type= $type;
        }else{
             $type= 0;
        }
        $data = [];
        $data["type"] = $type;
         $data['title'] = $titles[$type];


        return view('admin.users.add', $data);
    }
    public function show_user($id,Request $request)
    {
        $data = [];
        $data["user"] = DB::selectOne("select * from users where id = $id and type != 1 ");
        if (!$data["user"]) {
            return view(404);
        }
          $type = $request->get("type") ?? 0;

          $titles = ["عملاء",'مشرفين','ناشرين','المندوبين','عملاء الطباعة','عمال التغليف','السائقين','السفراء'];
        if(in_array( $type, [0,1,2,3,4,5,6,7])){
            $type= $type;
        }else{
             $type= 0;
        }
        if($type == 0){
         $data["customer"] = ProfileController::get_customer_data($id);
        }

        $data["type"] = $type;
         $data['title'] = $titles[$type];
        return view('admin.users.edit', $data);
    }
    public function delete_user($id){
      $user = DB::selectOne("select id from users where id = $id ");
      if($user){
           //delete from cart
        DB::delete("delete  from carts where user_id =  $id ");
        DB::delete("delete  from orders where user_id =  $id ");
        DB::delete("delete  from custom_products where user_id =  $id ");
        DB::delete("delete  from users where id =  $id ");

           return redirect()->back()->with('success', __('public.delete_success'));
      }
         return redirect()->back()->with('error', __('public.faild_job_job'));
    }

    public function export_users(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
         $t = $request->get('type');
         if($t){
             $type = $t;
         }else{
             $type = 0;
         }
        return Excel::download(new UserExport($type) , 'users'.$created_at.'.xlsx');
    }
    public function export_orders()
    {
        $created_at = date('Y-m-d h:i:s');

        return Excel::download(new OrderExport() , 'orders'.$created_at.'.xlsx');
    }
    public function orders(Request $request)
    {
        $data = [];
        $search = $request->get('s');
        $from =$request->get('from');
        $to =$request->get('to');

        $where = '';
        if($search){
            $where = " and name like '%$search%' ";
        }

        if($from && !$to){
            $from = Carbon::parse($from);

            $where .= " and created_at > '$from' ";
        }

        if($to && !$from){
            $to = Carbon::parse($to);
            $where .= " and created_at < '$to' ";
        }

        if($from && $to ){
            $from = Carbon::parse($from);
        $to = Carbon::parse($to);

            $where .= " and created_at between '$from' and '$to' ";
        }
        $get_order = DB::select("select * from orders where id > 0  $where order by id desc");

        $data["order_route"] = 'aorders';
        $data["show_order_route"] = 'show_aorder';
        $data["orders"] = $get_order;
        return view('admin.orders.orders', $data);
    }

    public function show_order($id)
    {
        $data = [];
        $get_order = DB::selectOne("select * from orders where `id` = '$id'");
        if (!$get_order) {
            return view(404);
        }
        $data["order_route"] = 'aorders';
        $data["show_order_route"] = 'show_aporder';
        $data["change_order_status"] = 'achange_order_status';
        $data["representatives"] = DB::select("select id , name from users where type = 3 order by id desc");
        $data["order_history"] = DB::select("select *  from order_history where order_id = $id order by id desc");

        $data["order"] = $get_order;
        $data["admin"] = true;
        $data['user'] = ProfileController::getUser($get_order->user_id);
        $data["order_status"] = DB::select("select * from order_status order by id desc");
        $data["order_products"] = DB::select("select * from `orders_products` as op left join products
         as p on op.product_id = p.id where op.type = 0 and op.order_id =  '$id'");


        $data["order_custom_products"] = DB::select("select * from `orders_products` as op left join
        custom_products as p on op.product_id = p.id where op.type = 1 and op.order_id =  '$id'");

        $data["covers"] = [];
foreach($data["order_custom_products"] as $get_file){
    if ($get_file->product_id) {


    $covers = DB::select("select m.price ,c.name , m.cover_id , c.photo,m.custom_product  , m.id as m_cover_id from cover_type as c left join merged_files_cover as m on c.id = m.cover_id  where m.custom_product =  $get_file->product_id  ");
    foreach($covers as $cover){
            $files = DB::select("select * from `custom_products_files` as cf left join cover_files_orders as co on co.file = cf.id  where co.m_id = $cover->m_cover_id order by co.order ");
            $filesarray= '';

            foreach($files as $file){

                    $prop = CustomProductController::get_file_prop($file->file);


                // );
            }
            $data["covers"][] = array(
                'id' => $cover->cover_id,
                'name' =>$cover->name,
                'photo' =>'/uploads/cover_type/'.$cover->photo,
               'cover_price' => $cover->price,
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

    public function store_user(Request $request)
    {
        $type = $request->post('type') ?? 0;
        if($type == 7){
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => 'unique:users,email',
                'phone' => ['required', 'numeric', 'min:8'],
                'code' => ['required','unique:coupon,code'],
                'user_discount' => ['required', 'numeric'],
                'safer_discount' => ['required', 'numeric'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],

                // 'name_ar' => 'required|unique:papers_type,name',

            ];
            $rules_messages = [
                'name.required' => __('public.filed_required'),
                'email.exists' => __('public.already_exsist'),
                'phone.required' => __('public.filed_required'),

            ];
        }elseif($type == 0){
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => 'unique:users,email',
                'phone' => ['required', 'numeric', 'min:8'],
                'phone2' => ['required', 'numeric', 'min:8'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],

                // 'name_ar' => 'required|unique:papers_type,name',

            ];
            $rules_messages = [
                'name.required' => __('public.filed_required'),
                'email.exists' => __('public.already_exsist'),
                'phone.required' => __('public.filed_required'),
                'phone2.required' => __('public.filed_required'),

            ];
        }

        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $email = $request->post('email');
        $phone = $request->post('phone');
        $phone2 = $request->post('phone2');

        $profit = $request->post('profit');
        $code = $request->post('code');
        $discount = $request->post('code');


        $password = $request->post('password');
        $password = Hash::make($password);


        DB::insert("insert into  users  (`name` , `email` , `type` , `phone` ,`phone2`, `password` ) VALUES
         ('$name' , '$email' , '$type' , '$phone','$phone2' ,'$password') ");
$user_id = DB::getPdo()->lastInsertId();
if($user_id && $type == 0){
    ProfileController::insert_customer($request ,$user_id);

}elseif($user_id && $type == 7){
    ProfileController::insert_safer($request ,$user_id);
}
$comment = "اشتراك جديد من $name";
         NotificationController::add_notification(Auth::id(),$user_id, $comment, 1, '/admin/users/'.$user_id.'?');
         $created_at = date('Y-m-d h:i:s');
         $updated_at = date('Y-m-d h:i:s');
         $check_if_exist = DB::selectOne("select user_id from users_coupons where user_id = $user_id");
         if(!$check_if_exist){
             $code = rand(0,1000);
             DB::insert("insert into users_coupons (`user_id`,`used`,`allowed`,`code`)
             VALUES ('$user_id', '0','1','$code')");

         }
         DB::insert("insert into wallets (`user_id` , `amount`,`created_at` ,`updated_at`) VALUES ('$user_id', '5000','$created_at','$updated_at')");

        return redirect()->back()->with('success', __('public.update_success'));
    }

    public function update_user($id, Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => 'unique:users,email,' . $id,
            'phone' => ['required', 'integer', 'min:8'],

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'email.exists' => __('public.already_exsist'),
            'phone.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $email = $request->post('email');
        $phone = $request->post('phone');
                $phone2 = $request->post('phone2');

        $type = $request->post('type');
        $profit = $request->post('profit');

        $password = $request->post('password');
        if (isset($password)) {
            $password = Hash::make($password);
            DB::update("update users set password = '$password'  where `id` = $id");
        }


        DB::update("update users set `name` = '$name' ,  `email` = '$email' ,`phone` = '$phone' ,`phone2` = '$phone2' where `id` = $id");
        if($type == 0){
             ProfileController::update_customer($request ,$id);
        }
if($type == 3){
      DB::update("update users set profit = $profit where id = $id");
}
        return redirect()->back()->with('success', __('public.update_success'));
    }

    public function banners()
    {
        $data = [];
        $data["banners"] = DB::select("select * from banners order by id desc ");
        return view('admin.banners.index', $data);
    }

    public function create_banner()
    {
        $data = [];

        return view('admin.banners.add', $data);
    }


    public function store_banner(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'link' => 'required',
            'photo' => 'required',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'link.required' => __('public.filed_required'),
            'photo.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $link = $request->post('link');


        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/banners/';
            $photo->move($destinationPath, $input);
        }

        DB::insert("insert into `banners` (`name` , `link`,`photo` ,  `created_at`  , `updated_at`)
        VALUES ('$name','$link','$input',  '$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function edit_banner($id)
    {
        $data = [];
        $data["banner"] = DB::selectOne("select * from `banners` where `id` = '$id'");

        if (!$data["banner"]) {
            return view(404);
        }

        return view('admin.banners.edit', $data);
    }

    public function update_banner($id, Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'link' => 'required',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'link.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $link = $request->post('link');
        $photo = $request->file('photo');
        $input = '';
        if (isset($photo)) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/banners/';
            $photo->move($destinationPath, $input);
        } else {
            $input = $request->post('old_image');
        }

        DB::insert("update `banners` set `name` ='$name', `link` = '$link',`photo` = '$input' ,
        `created_at` = '$created_at' , `updated_at` = '$updated_at'
         where id = $id ");
        return redirect()->back()->with('success', __('public.updated_success'));
    }


    public function assgined_to($id, Request $request)
    {
        $represnt_id = $request->post('rep_id') ?? 0;
        $order = DB::selectOne("select id from orders where id = '$id' ");
        if ($order) {
            DB::update("update orders set `represnt_id` = '$represnt_id' where `id` = '$id' ");
        }

        return redirect()->back()->with('success', __('public.sent_done'));
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


    public function blogs(Request $request)
    {
        $data = [];
        $search = $request->get('s');


        $where = '';
        if($search){
            $where = " and question like '%$search%' ";
        }
        if($search){
            $data["blogs"] = DB::table("blog")->where('name','like','%'.$search.'%')->orderByRaw('id DESC')->paginate(15)->appends(request()->query());

        }else{
            $data["blogs"] = DB::table("blog")->orderByRaw('id DESC')->paginate(15)->appends(request()->query());

        }
        return view('admin.blogs.index', $data);
    }

    public function create_blog()
    {
        $data = [];


        return view('admin.blogs.add', $data);
    }
    public function store_blog(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'desc' => 'required',

            'photo' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),

            'desc.required' => __('public.filed_required'),
            'photo.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $desc = $request->post('desc');
       $accepted =  $request->post('accepted');

        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/blogs/';
            $photo->move($destinationPath, $input);
        }

        DB::insert("insert into `blog` (`name` , `desc`,`image` ,`accepted`,  `created_at`  , `updated_at`)
        VALUES ('$name' ,  '$desc','$input', '$accepted', '$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit_blog($id)
    {
        $data = [];
            $data["blog"] = DB::selectOne("select * from `blog` where `id` = '$id' ");


        if(!$data["blog"]){
            return view(404);
        }

        return view('admin.blogs.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update_blog($id ,Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'desc' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'desc.required' => __('public.filed_required'),

            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $desc = $request->post('desc');
        $accepted =  $request->post('accepted');

            $accepted =  $request->post('accepted');


        $photo = $request->file('photo');
        $input = '';
        if ($photo) {
            $input = time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path() . '/uploads/blogs/';
            $photo->move($destinationPath, $input);
        }else{
            $input = $request->post('old_photo');
        }

        DB::insert("update  `blog` set `name` = '$name' ,
        `desc` = '$desc',`image` = '$input' ,`accepted` = '$accepted',  `created_at` = '$created_at'  , `updated_at`= '$updated_at' where `id` = '$id'");
        return redirect()->back()->with('success', __('public.added_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy_blog($id)
    {
        DB::delete("delete  from `blog` where `id` = '$id' ");
        return redirect()->back()->with('success', __('public.deleted_success'));
    }

    public function country()
    {
        $data = [];
        $data['country'] = DB::select("select * from city order by id desc");
        return view('admin.country.country' ,$data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_country()
    {
        $data = [];

        return view('admin.country.add_country',$data);
    }

    public function store_country(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required|unique:city,name',
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.exists' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);

        DB::insert("insert into `city` (`name` ,  `created_at`  , `updated_at`) VALUES ('" . $request->post('name') . "' ,'$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }



    public function edit_country($id)
    {
        $data = [];
        $data['country'] = DB::selectOne("select * from `city` where `id` ='$id' ");
        if(!$data['country']){
        return view(404);
        }
        return view('admin.country.edit_country' ,$data);
    }


    public function update_country($id ,Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required|unique:city,name,' . $id,
            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'name.unique' => __('public.already_exsist'),
            // 'name_ar.required' => __('public.filed_required'),
            // 'name_ar.exists' => __('public.already_exsist'),
        ];
        $request->validate($rules, $rules_messages);
       $name = $request->post('name');


        DB::insert("update  `city`  set `name` = '$name' ,  `created_at` = '$created_at'  , `updated_at` = '$updated_at' where `id` = '$id' ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function destroy_country($id)
    {

        DB::delete("delete from `city` where id = $id ");
        DB::delete("delete from `area` where city_id = $id ");

        return redirect()->back()->with('success', __('public.deleted'));

    }
    public function area()
    {
        $data = [];
            $data["area"] = DB::select("select * from area  order by id desc");

        return view('admin.country.area', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_area()
    {
        $data = [];
        $data["country"] = DB::select("select * from city order by id desc");


        return view('admin.country.add_area', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_area(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'price' => 'required',
            'country' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'price.required' => __('public.filed_required'),
            'country.required' => __('public.filed_required'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $price = $request->post('price');
        $country = $request->post('country');





        DB::insert("insert into `area` (`name`,`city_id`  ,`price`, `created_at`  , `updated_at`)
        VALUES ('$name' , '$country','$price',  '$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }



    public function edit_area($id)
    {
        $data = [];
            $data["area"] = DB::selectOne("select * from `area` where `id` = '$id' ");


        if(!$data["area"]){
            return view(404);
        }

        $data["country"] = DB::select("select * from city order by id desc");
        return view('admin.country.edit_area', $data);
    }


    public function update_area($id ,Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'name' => 'required',
            'country' => 'required',
            'price' => 'required',

            // 'name_ar' => 'required|unique:papers_type,name',

        ];
        $rules_messages = [
            'name.required' => __('public.filed_required'),
            'country.required' => __('public.filed_required'),
        ];
        $request->validate($rules, $rules_messages);
        $name = $request->post('name');
        $price = $request->post('price');
        $country = $request->post('country');




        DB::insert("update  `area` set `name` = '$name' ,`city_id` = '$country', `price` = '$price', `created_at` = '$created_at'  , `updated_at`= '$updated_at' where `id` = '$id'");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function destroy_area($id)
    {
        DB::delete("delete  from `area` where `id` = '$id' ");
        return redirect()->back()->with('success', __('public.deleted_success'));
    }




    public function block_user(Request $request ,$user_id){
        $user= DB::selectOne("select  id from users  where id = $user_id");
if($user){
        DB::update("update users set block = 1 , block_to = '". $request->post('date_to')."'  , reason = '". $request->post('reason')."' where id = $user_id");
    }
            return redirect()->back()->with('success', __('public.send_done'));

    }
      public function block_cancel($user_id){
        $user= DB::selectOne("select  id from users  where id = $user_id");
if($user){
        DB::update("update users set block = 0 where id = $user_id");
    }
            return redirect()->back()->with('success', __('public.send_done'));

    }
}
