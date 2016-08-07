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

class SecureController extends Controller
{

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






    public function validateProfile($return = "default", $edit = null, $id=null, $redirect = null) {

        $input = Input::all();


        $rules = array( //"employed_number"    => "required|numeric|unique:users,employed_number",
            //"email"             => "required|email|unique:users,email,NULL,id,deleted_at,NULL",
            "email"             => "required|email|unique:users,email",
            "name"              => "required",
            "last_name"         => "required",
            "username"          => "required|unique:users,username",
            "password"          => "required",
            "region_id"             => "required|numeric|exists:regions,id",
            "site_id"               => "required|numeric|exists:sites,id",
            "re_password"       => "required",
            "file_image"        => "");

        if( !empty(Input::get('employed_number')) ){
            $rules["employed_number"] = "required|numeric|unique:users,employed_number";
        }



        if($edit){

            if( isset($id)){
                $rules["email"] = "required|email|unique:users,email,".$id;
                $rules["username"] = "required|unique:users,username,".$id;
            }

            $array =  array("password",
                "re_password",
                "file_image",
                "employed_number",
                "username",
                "email",
            );
            foreach($array as $value) {

                if( Input::has("{$value}") ) {
                    continue;
                }
                unset($rules["{$value}"]);

            }

        }




        $validation = Validator::make($input, $rules);

        if ($validation->fails()) {
            

            $_getSpanishMessages =  $this->_getSpanishMessages($validation->errors(), $this->spanish);



            return $this->_getReturn($_getSpanishMessages, $return, $redirect);


        }

        return null;


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


    /* _getSpanishMessages
     *
     * Obtemenos todas las variables de forma global con Input::all()
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
            //die();

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

            return Response::json( array("success" => false,
                "errors"=> $_getSpanishMessages));

        }
        else if( $return == "redirect" ){

            return Redirect::to("{$redirect}")
                ->withErrors($_getSpanishMessages)
                ->withInput();
        }
        else if(  $return == "back" ){

            return Redirect::back()
                ->withErrors($_getSpanishMessages)
                ->withInput();

        }else if(  $return == "default" ){

            return Redirect::back()
                ->withErrors($_getSpanishMessages)
                ->withInput();
        }
        else{
            dd($_getSpanishMessages);
        }


    }


}
