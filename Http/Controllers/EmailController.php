<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Mail;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail as FacadesMail;

class MailController extends Controller {

    public function mail()
    {
        Mail::send('mail', $user, function($message) use ($user) {

	        $message->to($user['email']);

	        $message->subject('Welcome Mail');

    	});


       return 'Email sent Successfully';
    }

}
