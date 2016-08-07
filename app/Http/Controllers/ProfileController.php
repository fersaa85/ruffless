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

use App\User;

use  App\Http\Controllers\CoreController;
use  App\Http\Controllers\SecureController;
use  App\Http\Controllers\DBController;

class ProfileController extends Controller
{

    public $core;
    public $secure;
    public $db;


    public function __construct(){
        $this->core = new CoreController();
        $this->secure = new SecureController();
        $this->db = new DBController();

        $this->core->loginAuthCheck();
    }

    public function getIndex(){
      
        $params = ['table'=>'team'];
        $teams =  $this->core->_getList($params);
       
        return View::make('profile.me', compact('teams'));
    }

    public function postIndex(){



        $validateRequest = $this->secure->validateRequest("default" ,$this->secure->_getRuleProfile(true, Auth::user()->id));
        if( $validateRequest !== true ){ return  $validateRequest; }


        $params['id'] =  Auth::user()->id;
        $this->db->insertOrUpdateUser($params);

        return Redirect::back()->withInput();
        
    }


    public function getJoinFacebook(){

       $fbGetLikeFanPages =  $this->core->fbGetLikeFanPages(  Auth::user()->access_token, 'count');
       return Redirect::to('https://www.facebook.com/RufflesMX/?fref=ts');


    }


    public function getJoinTwitter(){

       $twFirendsIDs =  $this->core->twGetFirendsIDs();
       return Redirect::to('https://twitter.com/piggomx');
    }


    public function get(){
        return View::make('profile.me');
    }


    public function putMe(){


    }

}