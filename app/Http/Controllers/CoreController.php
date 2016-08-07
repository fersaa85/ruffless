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


class CoreController extends Controller
{
    public $imgWidth = 250;
    public $imgHeight = 200;


    public function __construct(){

        $this->_getViewsShare();

    }




    /****************
     * LOGUIN
     ****************/


    /* loginAuth
     *
     * realiza el proceso de login, para user o admin o webservices
     * retorna un json o http,
     *
     *
     * @method
     * public
     *
     * @params
     * $return => string [ json/null ]
     * $params => array [ remember (string) (reordar login)
     *                    messages (string) (texto de respuesta del login)
     *                    admin (string) (usuarios de tipo administrador)
     *                    redirect => (string) url a redirecionar, el usurio logueado
     *                  ]
     *
     * @return
     * json/http
     *
     * @author
     * ferssaavedra85@hotmail.com
     *
     */
    public function loginAuth($return = null,  $params = null){
        $params["remember"] = isset($params["remember"])? $params["remember"] : false;
        $params["messages"] = isset($params["messages"])? $params["messages"] : "Usuario/contraseña incorrectos" ;


        if( is_numeric(Input::get('employed_number')) ){
            $login["employed_number"] = Input::get('employed_number');
        }else{
            $login["username"] = Input::get('employed_number');
        }
        $login["password"] = Input::get('password');
        /*
        $login = array('employed_number'   => Input::get('employed_number'),
                       'password' => Input::get('password'));
        */

        if(isset($params["admin"])){
            $login["admin"] = true;
        }

        try {

            if (  Auth::attempt($login, $params["remember"]) ) {

                if($return === "json"){
                    return Response::json(array("success"=>true,
                        "token"=>Auth::user()->remember_token ));
                }
                else if($return === "http"){
                    return Redirect::to("{$params["redirect"]}");
                }
                else{
                    return Redirect::to("{$params["redirect"]}");
                }

            }
        } catch( \Exception $e ) {
            $exception = $e->getMessage();
        }



        $params["messages"] = isset($exception)?  $exception :$params["messages"] ;
        if($return === "json"){
            return Response::json( array("success"     => false,
                "errors"       => $params["messages"],
                "messages"     => $params["messages"]));
        }else{
            return Redirect::back()
                ->withErrors($params["messages"])
                ->withInput();
        }



    }


    /* logoutAuth
     *
     * realiza el proceso de logut, para user, admin o webservices
     * retorna un json o http,
     *
     *
     * @method
     * public
     *
     * @params
     * $return => string [ json/null ]
     * $params => array [ redirect (url a redirigir del login)
     *                    message (mensage de sesion filaizada) ]
     *
     * @return
     * json/http
     *
     * @author
     * ferssaavedra85@hotmail.com
     *
     */
    public function logoutAuth($return = "redirect", $params = null ){
        $params["messages"] = isset($params["messages"])?  $params["messages"] : "Sesion finalizada" ;
        $params["redirect"] = isset($params["redirect"])? $params["redirect"] : "/";
        $params["success"] = isset( $params["success"] )?  $params["success"]: false;

        if ( Auth::check() ) {
            Auth::logout();
            $params["success"] = true;
        }else{
            $params["messages"] = "No hay una sesion activa";
        }


        if ($return === "json") {
            return Response::json(array("success"  =>  $params["success"],
                "errors"   => $params["messages"],
                "messages" => $params["messages"]));
        } else {
            Session::flash("messages", $params["messages"]);
            return Redirect::to("{$params["redirect"]}");
        }


        dd( "No hay una sesion activa");
    }




    /* loginAuthCheck
     *
     * valida si el usuario se encentra autenticado
     *
     * @method
     * public
     *
     * @params
     * $return => string [ json/null ]
     * $params => array [ redirect (url a redirigir del login)
     *                    messages (mensage de Usuario no está autenticado) ]
     *
     *
     * @return
     * null/json
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     *
     */
    public function loginAuthCheck($return = null, $params = null){
        $params["messages"] = isset($params["messages"])? $params["messages"] : "El usuario no está autenticado";
        $params["redirect"] = isset($params["redirect"])? $params["redirect"] : "/";

        if ( !Auth::check() ) {

            if($return  == "json"){

                return Response::json(array("success"=>false,
                    "messages"=> $params["messages"]  ));
            }
            else if($return  == "http"){
                Session::flash("messages", $params["messages"]);
                return Redirect::to("{$params["redirect"]}");
            }
            else {

                Session::flash("messages", $params["messages"]);
                return Redirect::to("{$params["redirect"]}");

            }

        }

        return null;

    }



    public function loginAuthToken($return = "default", $params = null){
        $params["messages"] = isset($params["messages"])? $params["messages"] : "El token no es valido";
        $params["redirect"] = isset($params["redirect"])? $params["redirect"] : "/";



        $token = trim( Input::get("token") );

        $User = User::where('remember_token', '=', $token)
            ->first();


        if( $User === null or empty($token) ) {

            if ($return == "json") {

                return Response::json(array("success" => false,
                    "messages" => $params["messages"]));
            } else if ($return == "http") {
                Session::flash("messages", $params["messages"]);
                return Redirect::to("{$params["redirect"]}");
            } else {

                Session::flash("messages", $params["messages"]);
                return Redirect::to("{$params["redirect"]}");

            }
        }

        return  $User;

    }


    /* is_json
     *
     * determina cuando el objeto ha respondido un objeto json.
     *
     *
     * @method
     * public
     *
     * @params
     * $object	=> object [ obligatorio, objeto debueto, puede ser un jeson o un objeto de query ]
     *
     *
     * @return
     * bool
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */

    function is_json($object) {

        if( !isset($object->id) ) {

            //var_dump($object);
            return isset($object->getData()->success) ? true : false;
        }
        return false;
    }

    /* recoverPassword
     *
     * Recuperar el password del usuraio
     * se evalua si el usurio es un uasename/email
     * se puede enviar un password especifico que sera asignado
     * de lo contaro se asigara un password por default, desde la base
     *
     * @method
     * public
     *
     * @params
     * return => string [ json/null ]
     * params => array [ messages => (string) mensage de confirmacion
     *                   redirect => (string) redireccion http
     *                   email =>  (boolean) envio de email, confirmado el nuevo password
     *                  ]
     *
     * @return
     * json/http
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     */
    public function recoverPassword($return = null, $params = null){
        $params["messages"] = isset($params["messages"])? $params["messages"] : "El username/email no es valido";
        $params["email"] = isset( $params["email"] )?  $params["email"] : true;
        $params["redirect"] = isset($params["redirect"])? $params["redirect"] : "/";
        $params["success"] = isset($params["success"])? $params["success"] : false;

        $username =  Input::get('username', null);
        $password = Input::get('password', null);

        //\DB::enableQueryLog();
        if($this->usernameIsEmail($username)){
            $user = User::where("email", "=", $username)->first();
        }else{
            $user = User::where("employed_number", "=", $username)->first();
        }

        //dd( last( DB::getQueryLog()) );
        //dd($user);


        if( $user !== null){
            $password = !empty($password)?  $password : $user->employed_number ;
            $user->password =  Hash::make( $password );
            $user->save();

            $params["messages"] = "Su password ha sido restablecido";
            $params["success"] = true;


            /******
             * send email
             ******/
            if( $params["email"] ) {

                $configEmail["subject"] = "BBS recuperar contraseña";
                $configEmail["title"] = "BBS recuperar contraseña";
                $configEmail["from"]  = "developer@kreativeco.com";
                $configEmail["to"]  = $user->email;
                $configEmail["blade"] = 'email.recoverPassword';

                $messageEmail =  array( 'username'    => $user->username,
                    'password'    => $password,
                );



                $this->sendEmailSMTP($configEmail, $messageEmail);

                $params["messages"] = "Su password ha sido restablecido y enviando al email {$user->email}";

            }

        }


        if($return  == "json"){

            return Response::json(array("success"=>$params["success"],
                "messages"=> $params["messages"],
                "password" => $password ));

        } else {

            Session::flash("messages", $params["messages"]);
            return Redirect::to("{$params["redirect"]}");

        }

    }





    /**************
     ** SESSIONS -> VIEWS
     **************/

    /* _getViewsShare
     *
     * crea y maneja las seciones compartidas en las vistas
     * se maneja por arrays, para manipular multiples variables
     *
     * @method
     * public
     * __construct
     *
     *
     * @params
     * null
     *
     *
     * @return
     * void
     * session
     *
     * @nota
     * viewshare => array [ welcome => string,
                            welcome => string, ]
     *
     *
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function _getViewsShare(){


        $viewshare = $this->_getWelcomen() ;
        $viewshare = array_merge($viewshare, array('site_domine' => url()) );


        View::share('viewshare', $viewshare);

    }




    /* _getWelcomen
     *
     * regresa el saludo al usuario una vez que se
     * a logueado en el administardor
     *
     * @method
     * public
     *
     *
     * @params
     * null
     *
     *
     * @return
     * array
     *
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function _getWelcomen(){
        $return = null;

        if ( Auth::check() ) {
            $user = Auth::user();
            if($user !== null){
                $UserInfo = User::join('users_info', 'users_info.user_id', '=', 'users.id')
                    ->where('users_info.user_id', '=', $user->id)
                    ->first();
                $return = "{$UserInfo->name} {$UserInfo->last_name}";
            }
        }

        return array("welcome" => $return);
    }




    /***************
     * SELECT HTML
     ***************/

    /* _setEmptySelectHTML
     *
     * agrega un elemento vacio a un elemeto select  <select></select> ,
     * para validar que el campo, y que  lleve una
     * opcion valida elegida por el usuario
     *
     * @method
     * public
     *
     *
     * @params
     * $empty => array [ array en formato array( "" => --selecionar-- ) ]
     * $array => array [ array/lista de elemtos a concatenar ]
     *
     *
     * @return
     * array
     *
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function _setEmptySelectHTML($empty, $array){

        return  $empty +  $array;

    }






    /* _getSelectedSelectHTML
     *
     * indica en un elemeto select <select></select>
     * si un elemnto debe estar marcado por default selected
     * para indicar varias opciones las opciones se separan con comas
     *
     *
     * @method
     * public
     *
     *
     * @params
     * $array => array [ key  => (string) valor llave que se usa para marcar el array de busqueda)
     *                  value => (string) valores que se marcaran como selected, requiere separar con comas
     *                                    sin no hay valores indicar como null ]
     *
     * @parmas example
     * $array = array("region_id" => null, "color_id" => null);
     * $array = array("region_id" => "1,2")
     *
     *
     * @return
     * View::share
     * $selected[$key]
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function _getSelectedSelectHTML($array = null){


        foreach($array as $key => $value){
            if($value !== null){
                $array[$key] = explode(",", $value);
            }else{
                $array[$key] = array();
            }
        }

        View::share("selected",  $array);
    }







    /****************
     * EMAIL SMTP
     ****************/

    /* sendEmailSMTP
     *
     * Envia un email, utilizando una cuenta SMTP, configurada en .env
     * la funcion permite configurarse para realizar diferentes envios
     * de diferetes templates de blade, asi como el/los destinatarios
     * tambien permite personalizar la platilla que se manda llamar
     * con sus respectivos valores
     *
     * @method
     * public
     *
     * @params
     * $configEmail => array [ subject  => (string) subject del email ,
     *                         title    => (string) title del email,
     *                         from     => (string) remitente del mail,
     *                         to       => (array) destinatario/destinarios ,
     *                         blade    => (string) pantilla blade del mensage
     *                       ]
     * $messageEmail => array [ variables a pasar a la plantilla blade en formato array(key => value) ]
     *
     *
     * @config
     *
        $configEmail["subject"] = "Recuperar contraseña";
        $configEmail["title"] = "App Danone";
        $configEmail["from"]  = "no-replay@danone.com";
        $configEmail["to"]  = $user->email;
        $configEmail["blade"] = 'email.recoverPassword';

     *
     * @return
     * void
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function sendEmailSMTP($configEmail,  $messageEmail = null){
        //$configEmail = array('subject'=>null, "title"=>null,  "from"=>null, "to"=>null,  "blade"=>null);
        Mail::send($configEmail['blade'], $messageEmail, function($send) use($configEmail){
            $send->subject( $configEmail['subject'] );
            $send->from( $configEmail['from'] , $configEmail['title'] );
            $send->to( $configEmail['to'] );
            //$send->cc('fersaavedra85@hotmail.com');
            //$send->attach($pathToFile);
        });

        if( count(Mail::failures()) > 0 ) {
            dd( Mail:: failures() );
        }

    }





    /****************
     * UTILS PUBLIC
     ****************/

    /* usernameIsEmail
     *
     * determina siel nombre de usurio es un email, o un username
     * para que el usuario pueda reucperar su password
     * con ambos
     *
     * @method
     * public
     *
     * @params
     * username => string [ nombre de usurario/email ]
     *
     * @return
     * boolean
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function usernameIsEmail($username){

        if(strpos($username,"@") !== false){
            return true;
        }else{
            return false;
        }

    }




    /* addObjectToResultMYSQL
     *
     * inserta un nuevo elemento a un objeto creado
     * insertado llave/valor y retornadno un nuevo objeto
     * con los nuevos elementos
     *
     * @method
     * public
     *
     * @params
     * $object => object [objeto original ha ser modificado]
     * $key => string [ nombre de la llave del nuevo objeto]
     * $value => string/int/array/boolean/object [ valor del nuevo emento puede ser cualquier tipo de dato]
     *
     * @return
     * object
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *   $json->data[$key] = $this->core->addObjectToResultMYSQL( $json->data[$key], "get_reports", $report);
     */

    public function addObjectToResultMYSQL($object, $key, $value){

        $value = (count($value) > 0 )? $value : null;
        return (object) array_merge( (array)$object,  array("{$key}" => $value) );

    }





    /* _getListTable
     *
     *  esta funcion es la base para utilizar la funcion de list
     * y retornar diferentes resultados del listado en array
     * indicando la $key => $value, nos retorna un array o un json
     * deacuerdo al tipo de peticion
     * AJAX json
     * HTTP array/json
     *
     * @NOTA
     * esta funcion es la estructura base se debe personalizar
     * indicando la tabla/modelo respectivo en cada caso
     *
     * @method
     * public
     *
     * @params
     * $params => array [ id => (string) indicamaso un id para filtrar
     *                    where => (string) indicamos el camo en el cual filtramos
     *                    return => (string) permite forzar la respuesta en json
     *                    array => (boolean) el list te lo agrupa en un array
     *                  ]
     *
     *
     * @return
     * array/json
     *
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     */
    public function _getListTable($params = null){
        //$params = array("id" => 1, "where" => "", "return" => "json", "array" => true);
        $params["return"] = isset( $params["return"] )? $params["return"] : "default" ;


        if( isset($params["id"]) ){
            $lists = TABLE::where("{$params["where"]}", '=', $params["id"])->lists("name", "id");
        }else{
            $lists = TABLE::lists("name", "id");
        }


        if( isset($params["array"]) ){
            $lists = $lists->toArray();
        }


        if( isset($params["new_array"]) ){
            $array = array();
            foreach($lists as $key => $value){
                $array[] = array("id" => $key, "name"=>$value);
            }
            $lists  = $array;
        }


        if ( $params["return"] == "json") {
            return Response::json(array("success"=>true,
                "lists"=> $lists ));
        }

        return $lists;
    }




    
    /*****************
     * FILE IMAGE
     ****************/

    /* _getFileName
     *
     * genera un nombre de imagen unico bassado en una
     * marca de tiempo en formato date("YmdHis")
     * permite generar la image thumbnail asi como
     * asignar un sufijo al nombre
     *
     *
     * @method
     * public
     *
     * @params
     * $getFile => file [ archivo de imagen subido ]
     * $params => array [ thumbnail => boolean (insica si se crea un nombre de imagen con elsufijo 'thumb_')
     *                    suffixe => string (sufijo que opcional a agregar al nombre)
     *                    name => string (Nombre del archivo / default (date("YmdHis") )
     *                  ]
     *
     *
     * @return
     * string
     *
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     */
    public function _getFileName($getFile, $params = null){
        $params["thumbnail"] = isset($params["thumbnail"])? $params["thumbnail"] : null;
        $params["suffixe"] = isset($params["suffixe"])? $params["suffixe"] : null;
        $params["name"] = isset($params["name"])? $params["name"] : date("YmdHis");


        if(Input::hasFile("{$getFile}")) {
            $n      = $params["name"];
            $e      = Input::file("{$getFile}")->getClientOriginalExtension();

            $params["name"] = "{$n}.{$e}";

            if ($params["thumbnail"]) {
                $params["name"] = "{$n}_thumb.{$e}";
                if ($params["suffixe"]) {
                    list($n, $e) = explode(".", $params["name"]);
                    $params["name"] = "{$n}_{$params["suffixe"]}.{$e}";
                }
            }

            if ($params["suffixe"]) {
                $params["name"] = "{$n}_{$params["suffixe"]}.{$e}";
            }

        }

        return $params["name"];
    }


    /* _uploadFile
     *
     * permite subir un archivo al servidor, en una ruta selecionada
     * la fucnion permite subir cualquier tipo de archivo
     *
     *
     * @method
     * public
     *
     * @params
     * $default => string [nombre por default del archivo]
     * $getFile => file
     * $name => string [ nombre del archivo ]
     * $path => string [ path donde guardadaremos el arvhivo]
     *
     * @return
     * string
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function _uploadFile($default, $getFile = null, $name = null, $path = null){
        //$file->getClientOriginalName();
        //$file->getClientMimeType();
        //$file->getClientSize();
        //$file->getClientOriginalExtension();
        //$file->getMaxFilesize();
        //$file->getErrorMessage();


        if(Input::hasFile("{$getFile}")){


            $file 	   = Input::file("{$getFile}");
            $name = empty($name)? $file->getClientOriginalName() : "{$name}.{$file->getClientOriginalExtension()}";

            //indicamos que queremos guardar un nuevo archivo en el disco local
            Storage::disk('local')->put("{$path}{$name}",  File::get($file));
            if( $file->isValid() ){
                return $name;
            }


        }


        /*
        if( !empty(Input::get("{$getFile}")) ){
            $base64 = explode('base64,', Input::get("{$getFile}"));
            if( file_put_contents(getcwd()."/{$path}{$name}.png", base64_decode($base64[1])) ){
                return $name;
            }
        }
        */


        return $default;
    }

    public function _uploadFile2($default, $getFile = null, $name = null, $path = null){
        //$file->getClientOriginalName();
        //$file->getClientMimeType();
        //$file->getClientSize();
        //$file->getClientOriginalExtension();
        //$file->getMaxFilesize();
        //$file->getErrorMessage();


        if(Input::hasFile("{$getFile}")){


            $file 	   = Input::file("{$getFile}");
            $name = empty($name)? $file->getClientOriginalName() : "{$name}.{$file->getClientOriginalExtension()}";

            //indicamos que queremos guardar un nuevo archivo en el disco local
            Storage::disk('local')->put("{$path}{$name}",  File::get($file));
            if( $file->isValid() ){
                return $name;
            }


        }

        return Response::json(array("success"=>true,
            "inputs"=> $_POST,
            "file"=> $_FILES));


    }




    public function _getFileExtension($params = null){
        $params = isset($params["extension"])? $params["extension"] : "jpg";

        if(Input::hasFile("{$getFile}")){

            $file 	 = Input::file("{$getFile}");
            return  ".{$file->getClientOriginalExtension()}";
        }

        return ".{$extension}";
    }

    /*
    public function _getFileName($params = null){
        $params["name"] = isset($params["name"])? : date("YmdHis");
        $params["extension"] = isset($params["extension"])? $params["extension"] : $this->_getFileExtension();

        return $params["name"] . $params["extension"];

    }
    */

    /* _uploadFileResize
    *
    * Funcion que permite redimensionar y subir una nueva imagen
    * en base a una imagen original subida o en algun path
    *
    * @NOTA
    * PASO 1
    * http://stackoverflow.com/questions/23771117/requires-ext-fileinfo-how-do-i-add-that-into-my-composer-json-file
    *
    * PASO 2
    * https://styde.net/crear-un-thumbnail-con-laravel-5-y-bootstrap/
    *
    * @DOCUMENTACION
    * http://image.intervention.io/
    *
    *
    *
    */

    public function _uploadFileResize($default, $getFile = null, $name = null, $path = "", $size= null ){
        //use Image;
        // Image::make(Input::file("file_image"))->resize(50, 30)->save("resize.png");//path [ laravel/public ]
        if(Input::hasFile("{$getFile}")){

            $file 	 = Input::file("{$getFile}");
            $name = empty($name)? $file->getClientOriginalName() : "{$name}.{$file->getClientOriginalExtension()}";
            $size["width"] = isset($size["width"])? $size["width"] : $this->imgWidth;
            $size["height"] = isset($size["height"])? $size["height"] : $this->imgHeight;

            Image::make(Input::file("{$getFile}"))
                ->resize( $size["width"], $size["height"]  )
                ->save( "{$path}{$name}" );

            return $name;
        }


        return $default;
    }






    /* _deleteFile
     *
     * permite borrar un archivo del servidor en una ruta especifica
     *
     *
     * @method
     * public
     *
     * @params
     * $getFile = file
     * $name => string [nombre del archivo a borrar]
     * $path => string [path donde se encuentra el archivo]
     *
     * @return
     * void
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function _deleteFile($getFile = null, $name = null, $path = null){


        if (File::exists("{$path}{$name}")){

            File::delete("{$path}{$name}");

            return;
        }


        if(Input::hasFile("{$getFile}")){

            File::delete("{$path}{$name}");

            return;

        }



    }




    /* validateMaxSizeFile
     *
     * valida el tamaño maximo del archivo que se esta subiendo
     *
     * @method
     * public
     *
     * @params
     * $array => array [array de imagenes con el maximo peso en mb (file_image => 5)]
     * $edit => boolean [si el registro se esta editando]
     *
     *
     * @return
     * string/boolean
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     */

    public function validateMaxSizeFile($array, $edit = false){

        if( count($array) > 0 ){
            foreach($array as $key => $max){

                if(Input::hasFile("{$key}")){
                    $size = Input::file("{$key}")->getClientSize();
                    $max = ($max * 1024 * 1024);
                    if($size > $max){
                        return "La imagen no puede pesar mas de {$max} MB";
                    }
                }else{

                    if( $edit ){
                        continue;
                    }else{
                        return "La imagen no existe, favor de verficicar";
                    }
                }

            }

            return !true;
        }

        return "La imagen no existe, favor de verficicar";
        //return !false;

    }







    /* validateMimeTypeImage
     *
     * permite valdar el tip de extencion del archivo,
     * para que sea un formato de archivo valdo el que se esta subiendo
     *
     * @NOTA
     * esta funcion o esta completada y solo aplica para mime-type de imagen
     * se requiere comprender y utilizar las expesiones regulares al 100
     *
     * @method
     * public
     *
     * @params
     * $array => array [array con los archivos que se esta subiendo y stensiones permitidas (file_image => "gif|jpeg|pjpeg|png|x-png|jpg")]
     * $edit => boolean [indica si se esta realizando una edicion del archivo]
     *
     * @return
     * string/boolena
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     *
     */
    public function validateMimeTypeImage($array, $edit = false){


        if( count($array) > 0 ){
            foreach($array as $key => $value){

                if( Input::hasFile("{$key}") ){
                    $type = Input::file("{$key}")->getClientOriginalExtension();
                    //var_dump( preg_match("/(gif|jpeg|pjpeg|png|x-png|jpg)/i", "  png es el lenguaje x-png de secuencias de comandos web preferido.") ) ;
                    if( !preg_match('/(gif|jpeg|pjpeg|png|x-png|jpg)/i', $type, $matches) ) {
                        return "La imagen debe estar en formato jpg, gif o png";
                    }

                }else{

                    if( $edit ){
                        continue;
                    }else{
                        return "La imagen no existe, favor de verficicar";
                    }

                }

            }

            return !true;
        }

        return "La imagen no existe, favor de verficicar";


    }



    /*
     *
     *
     Este seria el ideal mayor precisición
    NUMERO EXTERIOR CALLE, COLONIA, MUNICIPIO/DELEGACION, ESTADO/PAIS


    Lo minimoque nos podrian pasar, si arroja un resultado aproximado
    CODIGO POSTAL, ESTADO/PAIS


    puedo hacer otras conbinaciones como pero ya no hay precisición, incluso puede arrojar otros resultados
    CALLE, ESTADO/PAIS
    COLONIA, ESTADO/PAIS
     */
    public function getLatLngByAddressGMaps($address){

        if(is_array($address)){


        }

        // format this string with the appropriate latitude longitude
        $address = urlencode($address);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address},+Mexico";

        // make the HTTP request
        $data = @file_get_contents($url);

        // parse the json response
        $jsondata = json_decode($data,true);


        // if we get a placemark array and the status was good, get the addres
        if(is_array($jsondata) and  $jsondata['status']=="OK"){

            return $jsondata["results"][0]["geometry"]["location"];

        }

        //dd( $url );

    }






    /*****************
     * EXPORT EXCEL FROM HTML
     ****************/

    /* getExcel
     *
     * exportat una tabla en formato excel,
     * obtenida desde lo que se imprime en pantalla del HTML
     *
     * La function tiene dos linea de procesamiento
     *
     * REQUEST AJAX
     * se recibe por AJAX el HTML y se guarda en una sesion
     *
     * REQUEST HTTP
     * se expota en excel lo guardado en la secion
     *
     * @files
     * get.html.export.js
     *
     *
     * @method
     * public
     *
     *
     * @params
     * $html => string [ html en un string limpiado desde javascript ]
     *
     *
     * @return
     * json
     * excel
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function getExcel(){


        if( Request::ajax() ){

            $html =  Input::get('html', null);

            if( !empty($html ) ){
                Session::flash("html", $html);
                return Response::json(array("success"=>true,
                    "callback"=> true ));
            }

            return Response::json(array("success"=>true,
                "callback"=> false ));

        }else{

            if( Session::has('html') ){

                $html = "<table border=\"1\">".
                    Session::get('html').
                    "<table>";

                //$html  = trim(preg_replace('/\s+/', ' ', utf8_$html));
                $html = $this->clearStrTojson($html);

                $file = "excel-".date("d-m-Y").".xls";
                header('Content-Encoding: utf-8');
                header("Content-Disposition: attachment; filename={$file}");
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Content-type: application/vnd.ms-excel;charset=utf-8");
                header("Content-Transfer-Encoding: binary ");
                header("Pragma: no-cache");
                header("Expires: 0");

                echo $html;

            }


        }


    }






    public function exportPdf(){


        if( Request::ajax() ){

            $html =  Input::get('html', null);

            if( !empty($html ) ){
                Session::flash("html", $html);
                return Response::json(array("success"=>true,
                    "callback"=> true ));
            }

            return Response::json(array("success"=>true,
                "callback"=> false ));

        }else{

            $reports = Report::orderBy('id','desc')->get();
            $pdf = PDF::loadView('dompdf.report', compact('reports'));
            return $pdf->download("pdf-".date("d-m-Y").".pdf");


        }


    }



    /* clearStrTojson
     *
     * limpia y codifica un string para exportar en excel
     *
     *
     * @method
     * public
     *
     *
     * @params
     * $string => string [ limpia y codifica un string para exportar en excel ]
     *
     *
     * @return
     * string
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */
    public function clearStrTojson($string = ""){

        return trim( preg_replace('/\s+/', ' ', utf8_decode($string)) );

    }



    /***************
     ** KCO APPS
     ***************/

    /* getAppPublished
     *
     * esta funcion evalua y controlael acceso a las apps de KCO
     * desde el servidor, se creea una vista donde se encuentra cargado
     * el estatus de la app ( true/flase )
     *
     * NOTA
     * las apps se identifican con  un token unico
     *
     * NOTA
     * si una app se encuetra desactivada, se debe cerrar la sesion
     * del usuario y regresarlo al login indicando que la app fue desactivada
     *
     * NOTA
     * la app debe cerrarse debe evaluar simpre el estatus al iniciar
     * desde cualquier ventana
     *
     * NOTA
     * el token debe proporcionarce manualmente a la app
     *
     * @method
     * public
     *
     * @params
     * $token => string [ token de la app ]
     *
     * @return
     * json
     *
     */
    public function getAppPublished(){

        $App = App::where('token', '=', Input::get('token'))
            ->first();

        return Response::json(array("success"=>true,
            "app"=> isset($App->published)? $App->published : false ));

    }


}