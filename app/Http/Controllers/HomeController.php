<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', 0);

use App;
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


use  App\Http\Controllers\CoreController;

class HomeController extends Controller
{

    public $core;
    
    public function __construct(){

        $this->core = new CoreController();
    }




    public function getIndex(){
        $permissions = ['user_friends', 'email', 'public_profile',];
        $callback = "http://develop.com/ruffless/public/facebook/callback";
        $fbLoginUrl =  $this->core->fbLoginUrl( $permissions,  $callback );
        return View::make('home.index', compact('fbLoginUrl'));
    }

    public function getCallback(){
        $this->core->fbCallback();
    }
  
    
    public function getDashboard(){

       $user = $this->core->fbUserAccessToken();

    }

    public function getTest(){
        
       $data =  $this->core->getfbUserFriends();
        dd($data);
    }

   
}