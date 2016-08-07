<?php

namespace App\Http\Controllers;

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


use App\User;
use App\Models\UserInfo;
use App\Models\Code;
use App\Models\CodeByMathces;
use App\Models\FootballPools;
use App\Models\Share;
use App\Models\Group;
use App\Models\GroupByUsers;
use App\Models\Point;
use App\Models\Mathces;

use  App\Http\Controllers\SecureController;

class DBController extends Controller
{
    public $_insert = "Registro agregado correctamente";
    public $_update = "Registro actualizado correctamente";
    public $_delete = "Registro eliminado correctamente";
    public $_check = "Registro eliminado correctamente";
    public $_message = "Respuesta en formato json";
    public $_get = "Registros recuperados correctamente en formato json";



    public $_messages = array(); //$this->db->_messages['prueba']



    public $secure;
    public function __construct(){

        // $this->_getViewsShare();
        $this->secure = new SecureController();

    }

    //DB::enableQueryLog();
    //dd( last(DB::getQueryLog()) );	

    //DB::raw("CONCAT('{$this->_path}partners/', partners.logo_image) AS file_image"),
    //DB::raw("DATE_FORMAT(created_at, '%m-%d-%Y') AS created_at"),
    //DB::raw("SUBSTRING(news,1,150) AS news"),


    //SELECT date_add(NOW(), INTERVAL -3 DAY)
    //SELECT date_sub(NOW(), INTERVAL 3 DAY)
    
    // DB::select( $sql); ejecuta un query

    /* AND (key = value OR key = value )
     * ->where(function ($query) use ($search){
            $query->orWhere('title', 'LIKE', "%{$search}%");
             $query->orWhere('title', 'LIKE', "%{$search}%");
        )
     */


    /* DB::statement(DB::raw('set @rownum=0'));
      UserInfo::select([ DB::raw('@rownum := @rownum + 1 AS rownum'),'users_info.*',])->orderBy('points', 'DESC')->get();
    */

    //dd($value->mathce->teamlocal->name);
    //dd($value->code->code);
    
    /*****************
     * CRUD´S
     ***************/

    private function getSessionFlash($messages){
        Session::flash('messages', $messages);
    }



    public function insertOrUpdateUser($params = null){
       $data =  isset($params['id'])?  User::findOrFail($params['id'])  : new User();

       $data->email = Request::input('email', null);
       $password = Request::input('password', null);
       if($password){
           $data->password = Hash::make(Request::input('password', null) );
       }
       $data->save();


        $userinfo = [ "name"         => Request::input('name'),
                      "last_name"    => Request::input('last_name'),
                      "phone"        => Request::input('phone', ''),
                      "phone2"       => Request::input('phone2', ''),
                      "email2"       => Request::input('email2', ''),
                      "photo"        => Request::input('photo', ''),
                      "team_id"      => Request::input('team_id', ''),
                    ];

        if( isset($params['id']) ){
            $data->userinfo()->update($userinfo);
            $this->getSessionFlash($this->_update);
        }else{
            $data->userinfo()->insert($userinfo);
            $this->getSessionFlash($this->_insert);
        }

        return $data;
    }
    


    public function deleteUser($id, $params = null){
        $data = User::findOrFail($id);
        $data->delete();

        $this->getSessionFlash($this->_delete);
        return $data;
    }



    public function insertOrUpdateGroup($params=null){
        $data =  isset($params['id'])?  Group::findOrFail($params['id'])  : new Group();
        $data->name = Request::input('name');
        $data->user_id =  Auth::user()->id;
        $data->save();

        $array = [ 'group_id'=>$data->id,
                     'user_id'=>Auth::user()->id,
                    'approved'=>1,
            ];
        
        if( isset($params['id']) ){
            $data->groupbyusers()->update($array);
            $this->getSessionFlash($this->_update);
        }else{
            $data->groupbyusers()->insert($array);
            $this->getSessionFlash($this->_insert);
        }

        return $data;
       
    }

    public function deleteGroup($id, $params = null){
        $data = GroupByUsers::where('group_id', '=', $id);
        $data->delete();
        
        $data = Group::findOrFail($id);
        $data->delete();

        $this->getSessionFlash($this->_delete);
        return $data;
    }




    /*****************
     * CUSTOM DB
     ***************/
    /*****************
     * @param $fbGetLikeFanPages
     ****************/

    public function fbGetLikeFanPages($points){

         $data =  UserInfo::findOrFail(Auth::user()->id);
         $data->points_facebook = 1;
         $data->points += $points;
         $data->save();
         
    }


    public function twGetFirendsIDs($points){
        
         $data =  UserInfo::findOrFail(Auth::user()->id);
         $data->points_twitter = 1;
         $data->points += $points;
         $data->save();

    }



    public function insertBurnedCode($code){

        $data =  Code::where('code', '=', $code)->whereNull('burned')->first();
        $data ->burned = 1;
        $data ->save();
        $id = $data->id;
        
        
        for($i=1; $i<=8; $i++){
            $data = new CodeByMathces();
            $data->code_id = $id;
            $data->user_id = Auth::user()->id;
            $data->save();
        }
        
    }


    public function insertFootballPools($result_matche, $values){

       list($mathce_id, $team_id) =  explode('-', $values);

        $data  = new FootballPools();

        if($result_matche == 'tie'){
             $data->result_matche = $result_matche;
             $data->team_id = 0;
        }else{
            $data->result_matche = $result_matche;
            $data->team_id = $team_id;
        }
        $data->mathce_id = $mathce_id;
        $data->user_id =  Auth::user()->id;

        $data->save();

        $this->updateCodeByMathces($mathce_id);

    }

    
    public function updateCodeByMathces($mathce_id){

        $data = CodeByMathces::where('user_id', '=', Auth::user()->id)->where('mathce_id', '=', '')->first();
        $data->mathce_id = $mathce_id;
        $data->save();

    }


    public function insertShare($share, $season_id=0){
        $points = 3;

        
        $data = Share::where('user_id', '=', Auth::user()->id)
                ->where('share', '=', $share)
                ->where('season_id', '=', $season_id)
                ->orderBy('id', 'DESC')
                ->first();
        
        if($data == null){
            $data = new Share();
            $data->user_id =  Auth::user()->id;
            $data->share =  $share;
            $data->season_id =  $season_id;
            $data->number_share =  1;
            $data->save();

            $this->updatePoints($points);
            $this->insertPoints($points, $share, $season_id);
        }
        else if( $data->share == 1 and $this->secure->validateTwoCodesSeason($season_id)){
            $data = new Share();
            $data->user_id =  Auth::user()->id;
            $data->share =  $share;
            $data->season_id =  $season_id;
            $data->number_share =  2;
            $data->save();

            $this->updatePoints($points);
            $this->insertPoints($points, $share, $season_id);
        }


    }


    public function updatePoints($points, $id = null){
        $id = !empty($id)? $id : Auth::user()->id;
        $data =  UserInfo::findOrFail($id);
        $data->points += $points;
        $data->save();
    }



    public function insertFriends($group_id, $user_id){
      
            $data = new GroupByUsers();
            $data->group_id = $group_id;
            $data->user_id = $user_id;
            $data->approved = 0;
            $data->save();

            //return User::findOrFail($user_id);
    }

    public function updateFriends($id){

        $data = GroupByUsers::findOrFail($id);
        $data->approved = 1;
        $data->save();

    }

    public function deleteFriends($id){

        $data = GroupByUsers::findOrFail($id);
        $data->delete();
    }



    public function insertPoints($points, $type, $season, $id ){
        $id = !empty($id)? $id : Auth::user()->id;
    
        $data = new Point();
        $data->points = $points;
        $data->type = $type;
        $data->user_id = $id ;
        $data->season = $season;
        $data->save();

    }

    
    public function updateMathces($result_matche, $values){

        list($mathce_id, $team_id) =  explode('-', $values);

        $data  =  Mathces::findOrFail($mathce_id);

        if( empty( $data->result_mathce) ) {
            if ($result_matche == 'tie') {
                $data->result_mathce = $result_matche;
                $data->team_id = 0;
            } else {
                $data->result_mathce = $result_matche;
                $data->team_id = $team_id;
            }
            $data->save();
        }



        return $data;
    }

}
