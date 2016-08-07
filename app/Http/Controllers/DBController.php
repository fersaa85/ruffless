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


class DBController extends Controller
{
    public $_insert = "Registro insertado correctamente";
    public $_update = "Registro actualizado correctamente";
    public $_delete = "Registro eliminado correctamente";
    public $_check = "Registro eliminado correctamente";
    public $_message = "Respuesta en formato json";
    public $_get = "Registros recuperados correctamente en formato json";

    //DB::raw("CONCAT('{$this->_path}partners/', partners.logo_image) AS file_image"),
    //DB::raw("DATE_FORMAT(created_at, '%m-%d-%Y') AS created_at"),
    //DB::raw("SUBSTRING(news,1,150) AS news"),

    // DB::select( $sql); ejecuta unquery

    /* AND (key = value OR key = value )
     * ->where(function ($query) use ($search){
            $query->orWhere('title', 'LIKE', "%{$search}%");
             $query->orWhere('title', 'LIKE', "%{$search}%");
        )
     */

    public function insertUser($params = null){

        $x = explode("@", Input::get('email'));
        if(!isset($x[1]) || $x[1]!='danone.com'){
            return false;
        }
        $User = new User();
        //$User->name = Input::get('username');
        $User->email = Input::get('email') ;
        $User->employed_number  = Input::get('employed_number', 0);
        $User->password =  Hash::make( Input::get('password') );
        //$User->password =  bcrypt( Input::get('password') );
        $User->username = Input::get('username', Input::get('email'));
        $User->remember_token = '';
        $User->save();

        $userinfo = array(
            "name"          => Input::get('name'),
            "last_name"     => Input::get('last_name'),
            "file_image"    => $params["_uploadFile"],
            "user_id"	    => $User->id,
            "username"      => $User->name = Input::get('username', Input::get('email')),
            "region_id"		=> Input::get('region_id'),
            "site_id"		=> Input::get('site_id'),

        );

        $User->userinfo()->insert($userinfo);

        return $User;

    }



    public function updateUser($params = null){

        $User = User::findOrFail($params["id"]);
        $User->email = Input::get('email') ;
        $User->username = Input::get('username', Input::get('email'));
        if(Input::has('password')){
            $User->password = Hash::make( Input::get('password') );
        }
        $User->save();

        $userinfo = array(
            "name"          => Input::get('name'),
            "last_name"     => Input::get('last_name'),
            "file_image"    => $params["_uploadFile"],
            "user_id"	    => $User->id,
            "region_id"		=> Input::get('region_id'),
            "site_id"		=> Input::get('site_id'),
            "username"      => Input::get('username', Input::get('email')),
        );

        $User->userinfo()->update($userinfo);

        return $User;

    }




    public function deleteUser($id, $return = null, $params = null){
        $params["messages"] = isset($params["messages"]) ? $params["messages"] : $this->_delete;

        $User = User::findOrFail($id);
        $User->delete();

        //$UserInfo = UserInfo::where('user_id', '=', $User->id);
        //$UserInfo->delete();

        $params["messages"] =  "{$params["messages"]} - No. empleado {$User ->employed_number}";
        if($return === "json"){
            return Response::json( array("success"     => true,
                "errors"       => $params["messages"],
                "messages"     => $params["messages"]));
        }

        return $params["messages"];


    }




}
