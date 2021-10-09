<?php

namespace App\Http\Controllers;

use App\Models\Faq as faqs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{

    public function index(Request $request)
    {
        $data = [];
        $search = $request->get('s');


        $where = '';
        if($search){
            $where = " and question like '%$search%' ";
        }
        $data["faqs"] = DB::select("select * from faqs where id > 0 $where order by id desc ");
        return view('admin.faqs.index', $data);
    }

    public function create()
    {
        $data = [];

        return view('admin.faqs.add', $data);
    }


    public function store(Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'question' => 'required',
            'answer' => 'required',

        ];
        $rules_messages = [
            'question.required' => __('public.filed_required'),
            'answer.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $question = $request->post('question');
        $answer = $request->post('answer');




        DB::insert("insert into `faqs` (`question` , `answer` ,  `created_at`  , `updated_at`)
        VALUES ('$question','$answer',  '$created_at', '$updated_at' ) ");
        return redirect()->back()->with('success', __('public.added_success'));
    }


    public function edit($id)
    {
        $data = [];
        $data["faq"] = DB::selectOne("select * from `faqs` where `id` = '$id'");

        if (!$data["faq"]) {
            return view(404);
        }

        return view('admin.faqs.edit', $data);
    }

    public function update($id, Request $request)
    {
        $created_at = date('Y-m-d h:i:s');
        $updated_at = date('Y-m-d h:i:s');
        $rules = [
            'question' => 'required',
            'answer' => 'required',

        ];
        $rules_messages = [
            'question.required' => __('public.filed_required'),
            'answer.required' => __('public.filed_required'),

        ];
        $request->validate($rules, $rules_messages);
        $question = $request->post('question');
        $answer = $request->post('answer');


        DB::insert("update `faqs` set `question` ='$question', `answer` = '$answer',
        `created_at` = '$created_at' , `updated_at` = '$updated_at'
         where id = $id ");
        return redirect()->back()->with('success', __('public.updated_success'));
    }





}
