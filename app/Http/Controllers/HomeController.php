<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', 0);


use Auth;
use Config;
use DB;
use File;
use Hash;
use Input;
use Mail;
use Redirect;
use Request;
use Response;
use Session;
use Storage;
use URL;
use Validator;
use View;

use Image;
use Facebook;


class HomeController extends Controller
{

    public function getIndex(){
        return Redirect::to('facebook-login');
    }

   
  
}