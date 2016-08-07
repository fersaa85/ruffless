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
use Twitter;


use App\User;
use App\Models\UserInfo;
use App\Models\Team;
use App\Models\Mathces;
use App\Models\Season;
use App\Models\FootballPools;
use App\Models\CodeByMathces;

use  App\Http\Controllers\SecureController;
use  App\Http\Controllers\DBController;




class GroupController extends Controller
{
    public $core;
    public $secure;
    public $db;


    public function __construct()
    {
        $this->core = new CoreController();
        $this->secure = new SecureController();
        $this->db = new DBController();

        //$this->core->loginAuthCheck();
    }

    public function getIndex(){

       
        return View::make('groups.index');
    }


    public function getAdd(){

        return View::make('groups.add');
    }
    
    public function postAdd(){

        $validateRequest = $this->secure->validateRequest("default" ,$this->secure->_getRuleGroup());
        if( $validateRequest !== true ){ return  $validateRequest; }

        $validateGroupUnique  = $this->secure->validateGroupUnique(Request::input('name'),  "json");
        if( $validateGroupUnique !== true ){ return  $validateGroupUnique; }

        $this->db-> insertOrUpdateGroup();

        return Redirect::to('grupos');
    }

    public function getDelete($id){
        return View::make('groups.delete', compact('id'));
    }

    public function deleteDelete(){

        $this->db->deleteGroup(Request::input('id'));

        return Redirect::to('grupos');
    }
    
    
    
    
    public function getInvitar(){

        return View::make('groups.add');
    }

    public function postInvitar(){
        $group_id = 2;
        $user_id = 3;
       $validateGroupByUsersUnique = $this->secure->validateGroupByUsersUnique($group_id, $user_id, "json");
        if( $validateGroupByUsersUnique !== true ){ return  $validateGroupByUsersUnique; }

        $this->db->insertFriends($group_id, $user_id);

        return Redirect::to('grupos');
    }
    
    
    public function putInvitar($id=null){
        $this->db->updateFriends($id);
        
    }
    
    
    public function deleteInvitar($id=null){
        
        $this->db->deleteFriends($id);
    }
}