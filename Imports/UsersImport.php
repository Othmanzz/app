<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

      // dd($row[0]);
      // $user = new User([
      //
      //     'name' => $row[0],
      //     'name_ar' => $row[1],
      //     'email' => $row[2],
      //     'password' => Hash::make($row[3]),
      //     'birth_day' => $row[4],
      //     'birth_day_ar' => $row[5],
      //     'ministry_joined_date' => $row[6],
      //     'phone' => $row[7] ,
      //     'extra_education' => $row[8] ,
      //     'nationality' => $row[9] ,
      //     'civil_registry' => $row[10] ,
      //     'job_title' => $row[11] ,
      //     'direct_statement_date' => $row[12] ,
      //     'gender' => $row[14] ,
      //
      // ]);
  // DB::insert("insert into users (`name`, `name_ar` , `email` , `birth_day`) VALUES ('".$row[0]."','".$row[1]."','".$row[2]."','".$row[4]."')");
  $get_email_and_civil_registry = DB::selectOne("select email , civil_registry from `users` where email = '".$row[2]."'  or civil_registry = '".$row[10]."' ");

  if ($get_email_and_civil_registry || $row[0] == null) {
  return null;
  }
  // dd($row);
      $employer = new User();
      $employer->name = $row[0];
      $employer->name_ar = $row[1];
      $employer->email =  $row[2];
      $employer->birth_day =  $row[4];
      $employer->birth_day_ar = $row[5];
      $employer->civil_registry = $row[10];
      // $employer->specialization = $request->post('specialization');
      $employer->ministry_joined_date = $row[6];
      $employer->direct_statement_date = $row[12];

// dd($employer);
      // $employer->job_number = $request->post('job_number');
      $employer->phone =$row[7];
      $employer->job_title =$row[11];
      // $employer->career_angel = $request->post('career_angel');
      // $employer->type_of_contract = $request->post('type_of_contract');
      $employer->nationality = $row[9] ?? 1;
      $employer->gender =  $row[14] ?? 1;
      $employer->career_angel =  $row[15] ?? '';
      $employer->authority_expiry_date =  $row[16] ?? '';
      $employer->contract_starting_date =  $row[17] ?? '';
      $employer->specialist =  $row[18] ?? '';
      $employer->specialization =  $row[19] ?? '';
      $employer->education =  $row[20] ?? '';
      $employer->ministry_data =  $row[21] ?? '';


      // $employer->education = $request->post('education');
      $employer->extra_education = $row[8] ;
      $employer->hired = 1 ;
      $employer->h_credence = 1 ;
      $employer->old = 1 ;

      // $employer->specialist = $request->post('specialist');
      // $employer->ministry_data = $request->post('ministry_data');
      $employer->password = Hash::make($row[3]);
      $employer->save();
      $user_id = $employer->id;

      DB::insert("insert into employer_status (`user_id`) VALUES ('$user_id')");
      $department_id =  $row[13];
    $check_d =  DB::selectOne("select * from `departments` where `id` = '$department_id' ");
    if($check_d){
      DB::insert('insert into `employers_departments` (department_id , user_id , hired) VALUES (?,?,?)', [$check_d->id, $user_id , 1]);

    }
    $nationality_id =  $row[13];
  $check_n =  DB::selectOne("select * from `nationality` where `id` = '$nationality_id' ");
  if($check_n){
    DB::update("update  `users` set `nationality` = '".$check_n->id."' where id = '".$user_id."'");

  }
        return $employer;
    }
}
