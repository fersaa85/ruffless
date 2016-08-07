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
use App\Models\Code;
use App\Models\FootballPools;
use App\Models\CodeByMathces;
use App\Models\Group;
use App\Models\GroupByUsers;
use App\Models\Point;

use  App\Http\Controllers\CoreController;

class SecureController extends Controller
{


    public function __construct() {

        // $this->_getViewsShare();

    }


    public  $spanish = array("employed_number"  => "numero de empleado",
                                "email"             => "email",
                                "name"              => "nombre",
                                "last_name"         => "apellido",
                                "username"          => "Nombre de usuario",
                                "password"          => "contraseña",
                                "re_password"       => "repertir contraseña",
                                "region_id"         => "region",
                                "site_id"         	=> "sitio",
                                "file_image"        => "imagen",
                                "in_site"			=> "en sitio",

                                "observed_behavior"     => "Conducta observada",
                                "worker_feedback"       => "feedback del trabajador",

                                "place"      		 => "lugar",


                                "position"				=> "puesto",
                                "n_more_1"				=> "N mas 1",
                                "rh_del_sitio"			=> "RH del sitio",

                                "title"     => "titulo",
                                "news"       => "noticia",
    );





  /* validateRequest
   *
   * Obtemenos todas las variables de forma global con Request::all()
   * y de efectua un el proceso de evaluacion de errors,
   * se incorporan los correctos mensajes de error, el json/http
   *
   *
   * @method
   * public
   *
   * @params
   * $return => string [ tipo de respuesta que regresa la funcion http/json ]
   * $rules => array [ array con las reglas que debe efectuar en esa validacion ]
   * $redirect  =>  string [ si se produce un errror debe redirigir a una url especifica, por default regresa a la misma url de la peticion ]
   *
   * @return
   * boolean/object
   *
   * @author
   * fersaavedra85@hotmail.com
   */
    public function validateRequest($return = "default" ,$rules, $redirect = null) {
        $request = Request::all();


        $validation = Validator::make($request, $rules);
        if ($validation->fails()) {
             $_getSpanishMessages =  $this->_getSpanishMessages($validation->errors(), $this->spanish);
             return $this->_getReturn($_getSpanishMessages, $return, $redirect);
        }

        return true;


    }


    /* _getSpanishMessages
     *
     * Convierte las variables de una llamada GET/POST a su
     * equivalente en español, para el manejo correcto de
     * los mensajes de error eviados
     *
     *
     * @method
     * public
     *
     * @params
     * $errors => array [ $validation->errors() ]
     * $spanish => array [ array con los valores en español siguiendo el formato $key => $value (name => nombre)]
     *
     * @return
     * array
     *
     * @author
     * fersaavedra85@hotmail.com
     */
    public function _getSpanishMessages($errors, $spanish){

        $return = (array)$errors->all();


        foreach($spanish as $key => $value){
            if ( $errors->has("{$key}") ) {

                $serach = str_replace("_"," ",$key);

                foreach($return as $key2 => $value2){

                    if(strstr($value2, $serach) !== false){
                        $return[$key2] = str_replace($serach, $value, $return[$key2]);
                        break;
                    }
                    continue;
                }

            }

        }

        return $return;

    }





    /* _getRetrurn
    *
    * Regresa el/los mensages de error en con su respectivo callback
    * deacuerdo a los parametros indicados
    *
    *
    * @method
    * private
    *
    * @params
    * $_getSpanishMessages => array
    * $return => string [ derault ( default/json/redirect/back ) - tipo de respuesta ]
	* $redirect => string [ default ( / ) -  url para redirigir al http]
    *
    * @return
    * json/http
    *
    * @author
    * fersaavedra85@hotmail.com
    *
    */
    public function _getReturn($_getSpanishMessages, $return = "default", $redirect = "/"){

        if( $return == "json" ){
            return Response::json([ "errors"=> $_getSpanishMessages ], 401 );
        }
        else if( $return == "redirect" ){

            return Redirect::to("{$redirect}")
                ->withErrors($_getSpanishMessages)
                ->withInput();

        }
        else if(  $return == "back" ) {

            return Redirect::back()
                ->withErrors($_getSpanishMessages)
                ->withInput();

        }
        else{

            return Redirect::back()
                ->withErrors($_getSpanishMessages)
                ->withInput();
        }

        dd($_getSpanishMessages);

    }




    /****************************************
     * UTILS
     ***************************************/


    /* _validateIsJSON
     *
     * prmite validar que una  variable cumpla
     * con un formato JSON valido
     *
     * @method
     * public
     *
     *
     * @params
     * $return 	=> string [ derault ( default/json/redirect/back ) - tipo de respuesta ]
     * $var 	=> string [ nombre de la variable con contenido JSON ]
     * $params 	=> array ( message => mensaje de respuesta )
     *
     *
     *
     *
     *
     *
     */
    public function _validateIsJSON($var ,$return, $params = null){
        $params["messages"] = isset($params["messages"])? $params["messages"] : "El formato JSON es invalido";

        $json =  json_decode( Input::get("{$var}") );
        if( $json === null){
            return $this->_getReturn(array($params["messages"]), $return );
        }


        return null;

    }




    /*****************
     * GET RULES
     ****************/
    public function _getRuleProfile($edit=null, $id=null){
        $unset =  array("password", 'name', 'last_name', 'phone');

        $rules = array(
                    "email"             => "required|email|unique:users,email",
                    "name"              => "required",
                    "last_name"         => "required",
                    "phone"             => "required",
                     );

        
        $password = Request::input('password', null);
        if( $password ){
            $rules['password'] = "required|min:8";
        }


        if($edit){
            if( isset($id)){
                $rules["email"] = "required|email|unique:users,email,".$id;
            }
            
            foreach($unset as $value) {
                if( Request::has("{$value}") ) { continue; }
                unset($rules["{$value}"]);
            }
        }
        
        return $rules;

    }


    public function _getRuleGroup($edit=null, $id=null){
        $unset = array();

        $rules = array(
            "name"              => "required",
        );

        if($edit){

            foreach($unset as $value) {
                if( Request::has("{$value}") ) { continue; }
                unset($rules["{$value}"]);
            }
        }

        return $rules;

    }

    /*******************
     * VALIDATES
     ********************/

    public function _validateCodes($code,  $return ){
        
        if( !empty($code) and ( strlen($code) == 10 ) ){

            $data =   Code::where('code', '=', $code)->whereNull('burned')->first();
            if(  $data !== null){
               return true;
            }
            return $this->_getReturn("El codigo ya ha sido utilizado, ingrese otro codigo ", $return);
        }

        return $this->_getReturn("El codigo no es valido ", $return);
        
    }

    public function validateFootballPoolsUnique($values, $return){

        list($mathce_id, $team_id) =  explode('-', $values);

        $data = FootballPools::where('user_id', '=', Auth::user()->id)->where('mathce_id', '=', $mathce_id)->first();
        if(  $data === null){
            return true;
        }

        return $this->_getReturn("Ya haz registrado, un resultado para este partido", $return);
        
    }
    
    
    
    public function validateTwoCodesSeason($season_id){
        

        $data = CodeByMathces::where('user_id', '=', Auth::user()->id)
                                ->where('mathce_id', '!=', 0)
                                ->where('season_id', '=', $season_id)
                                ->count();
        
        return ($data == 16)? true : false;
        
    }


    public function validateGroupUnique($name,  $return){

        $data =  Group::where('name', '=', $name)
                    ->where('user_id', '=', Auth::user()->id)
                    ->first();

        if(  $data === null){
            return true;
        }

        return $this->_getReturn("El nombre del grupo ya existe, ingrese uno diferente ", $return);
    }

    
    public function validateGroupByUsersUnique($group_id, $user_id, $return){
        $data = GroupByUsers::where('group_id', '=', $group_id)->where('user_id', '=', $user_id)->first();

        if(  $data === null){
            return true;
        }

        return $this->_getReturn("Ya haz intivitado a esta persona", $return);
    }
    
    
    public function validateGamePointsUnique( $type, $season, $id ){
        
        $data = Point::where('type', '=', $type)
                    ->where('season', '=', $season)
                    ->where('users_id', '=', $id)
                    ->first();

        if(  $data === null){
            return true;
        }
        
        return false;
        
    }
    
}
