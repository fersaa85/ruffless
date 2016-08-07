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
use App\Models\Group;
use App\Models\GroupByUsers;
use App\Models\Point;


use  App\Http\Controllers\SecureController;
use  App\Http\Controllers\DBController;




class CoreController extends Controller
{
    public $imgWidth = 250;
    public $imgHeight = 200;

    public $fbFanPage = '894252303983825'; //ID DE LA FAN PAGES A BUSCAR CON ME GUSTA
    public $twFriendID = '175491448'; //ID DE LA CUENTA DE TWITTER QUE DEBES SEGUIR


    public $secure;
    public $db;

    public $now;

    public function __construct(){
      
       // $this->_getViewsShare();
        $this->secure = new SecureController();
        $this->db = new DBController();


        $this->now  =  date("Y-m-d H:i:s");
    }

    public function getNameByUser(){

        return Auth::user()->userinfo->name ." ". Auth::user()->userinfo->last_name;
    }

    public function getUser($id){

        $user = User::findOrFail($id);
        $user->offsetSet('userinfo', $user->userinfo);
        return $user;
    }

    
    
    public function getInvite(){

        GroupByUsers::where('user_id', '=', Auth::user()->id)->where()->get();
        
    }
    
    
    public function getFootballPoolsResults($result_matche, $team_id){
       
       return FootballPools::where('result_matche', '=', $result_matche)->where('team_id', '=', $team_id)->get();
        
    }


    public function getCodeMathcesFootballPoolsByUser(){

       $getMathces =  $this->getMathces();
        $merged = array();
        foreach( $getMathces as $value){

            $CodeByMathces = CodeByMathces::where('user_id', '=', Auth::user()->id)
                ->where('mathce_id', '!=', 0)
                ->orWhere('mathce_id', '=', $value->loca_id)
                ->orWhere('mathce_id', '=', $value->visit_id)
                ->first();

            $FootballPools =  FootballPools::where('user_id', '=', Auth::user()->id)->where('mathce_id', '=', $CodeByMathces->mathce_id)->first();
            $CodeByMathces->offsetSet('footballpools', $FootballPools );

            $merged[] = $CodeByMathces;
            //$merged = empty($merged)? $CodeByMathces :  $merged->merge($CodeByMathces);
        }


      return $merged;

    }


    public function getRanking(){
        $ranking = array();

        DB::statement(DB::raw('set @rownum=0'));
        $ranking['ranking'] = UserInfo::select([ DB::raw('@rownum := @rownum + 1 AS rownum'),
                                    'users_info.*',
                                   ])
                                    ->orderBy('points', 'DESC')
                                    ->get();


        DB::statement(DB::raw('set @rownum=0'));
        $ranking['ranking_last15days'] = UserInfo::select([ DB::raw('@rownum := @rownum + 1 AS rownum'),
                                                                'users_info.*',
                                                            ])
                                                    ->where(DB::raw("DATE_SUB('{$this->now}', INTERVAL 15 DAY)"), '<', 'users_info.updated_at')
                                                    ->orderBy('points', 'DESC')
                                                    ->get();



       $ranking['ranking_me'] = $ranking['ranking']->only(['user_id', Auth::user()->id]);



      return  $ranking;



    }
    
    public function getRankingGroup($group_id){
        $ranking = array();
        $array = $this->getGroupByUsers($group_id);

        DB::statement(DB::raw('set @rownum=0'));
        $filelds = ['users_info.*', DB::raw('SUM(.points.points) as total'), DB::raw('@rownum := @rownum + 1 AS rownum') ];
        $ranking['ranking'] = Point::join('users_info', 'users_info.user_id', '=', 'points.user_id')
                                    ->whereIn('points.user_id', $array)
                                    ->orderBy('users_info.points', 'DESC')
                                    ->groupBY('points.user_id')
                                    ->select($filelds)
                                    ->get();






        DB::statement(DB::raw('set @rownum=0'));
        $filelds = ['users_info.*', DB::raw('SUM(.points.points) as total'), DB::raw('@rownum := @rownum + 1 AS rownum') ];
        $ranking['ranking_last7days'] = Point::join('users_info', 'users_info.user_id', '=', 'points.user_id')
                                            ->whereIn('points.user_id', $array)
                                            ->where(DB::raw("DATE_SUB('{$this->now}', INTERVAL 7 DAY)"), '<', 'points.updated_at')
                                            ->orderBy('total', 'DESC')
                                            ->groupBY('points.user_id')
                                            ->select($filelds)
                                            ->get();


        foreach ($ranking['ranking_last7days'] as $key => $value) {
            $i = $key;
            $ranking['ranking_last7days'][$key]->rownum = ++$i;
        }

        return $ranking;
    }


     public function getCurrentSeason(){
         return Season::where('season', '>=', $this->now )->orderBy('id', 'DESC')->first();
     }

    public function getMathcesByUser(){

        $array = FootballPools::where('user_id', '=', Auth::user()->id)->get(['mathce_id'])->toArray();
        $Season = $this->getCurrentSeason();

        $count = CodeByMathces::where('user_id', '=',  Auth::user()->id)->where('season_id', '=', $Season->id)->count();
        $take = ($count>=2)? 16 : 8;

        return Mathces::where('season_id', '=', $Season->id)
                        ->where('date_mathce', '>=', $this->now )
                        ->whereNotIn('id',$array)
                        ->take($take)
                        ->get();
        
    }

    public function getMathces($params = null){
       $params['date'] = isset($params['date'])? $params['date'] : false;

       $Season =  $this->getCurrentSeason();
       $Mathces = Mathces::where('season_id', '=', $Season->id);
       if( $params['date'] ) {
           $Mathces->where('date_mathce', '>=', $this->now);
       }
       $Mathces = $Mathces ->get();

        return $Mathces ;
    }



    public function getGroups(){

       $GroupByUsers = GroupByUsers::where('user_id', '=', Auth::user()->id)->where('approved', '=', 1)->get();
        foreach( $GroupByUsers as $key => $value){
            $GroupByUsers[$key]->offsetSet('group', $value->group);

            $array = $this->getGroupByUsers();
            $GroupByUsers[$key]->offsetSet('userinfo',  UserInfo::whereIn('user_id',  $array )->get());
        }

        return $GroupByUsers;
    }
    
    public function getGroupsPending(){

        return GroupByUsers::where('user_id', '=', Auth::user()->id)->where('approved', '=', 0)->get();
    }

    public function getGroupByUsers($group_id){
        return  GroupByUsers::where('group_id', '=', $group_id)->where('approved', '=', 1)->get(['user_id'])->toArray();
    }

    
    
    
    public function getTimeLine(){
        $array = array();

        $get =  $this->getTwTimeline();
       //$get2= $this->getFbTimeLine("Ruflesevida", Auth::user()->access_token);
        return   $get;
    }
    

    /******************************
     * SHARE SOCIAL NETWORK and METATAGS OPEN GRAND
     ******************************/
    /* getFacebookShare
	 *
	 * esta funcion permite construye el link para compartir una url en faccebook
	 *
	 * params
	 * @$url
	 *
	 * html
	 * <a href="javascript: void(0);" onclick="window.open('{__NEWS_SHARE_FACEBOOK__}','popupShare', 'toolbar=0, status=0, width=650, height=450');">
			<img src="images/icon-share-facebook.png" height="18" />
	  </a>
	*/
    public function getFacebookShare($url = null){
        return  "http://www.facebook.com/sharer.php?u={$url}";
    }


    /* getTwitterShare
	 *
	 * genera el link para compartir en twitter
	 *
	 * params
	 * @$twitter
	 * @url
	 * @text
	 *
	 * html
	 * <a href="javascript: void(0);" onclick="window.open('{__NEWS_SHARE_TWITTER__}','popupShare', 'toolbar=0, status=0, width=450, height=250');">
			<img src="images/icon-share-twitter.png" height="18" />
	   </a>
	*/
    public function getTwitterShare($url = null, $text = null){
        $twitter = "Ruffles";
        $text = trim($text);

        $share  = "https://twitter.com/share?";
        $share .= "url={$url}&";
        $share .= "via={$twitter}&";
        $share .= "text={$text}&";
        //$share .= "related=twitterapi twitter&";
        //$share .= "hashtags=ejemplo ejemplo2&";


        return $share;


    }


    /* getGooglePlusShare
	 *
	 * esta funcion permite construye el link para compartir en google+/google plus
	 *
	 * params
	 * @$url
	 *
	 * html
	 * <a href="javascript: void(0);" onclick="window.open('{__NEWS_SHARE_GOOGLE__}','popupShare', 'toolbar=0, status=0, width=650, height=250');">
		<img src="images/icon-share-google.png" height="18" />
	   </a>
	*/
    public function getGooglePlusShare($url = null){

         return  "https://plus.google.com/share?url={$url}";

    }







    /**************
     * API TWITTER
     **************/
    
    public function twGetFirendsIDs($username){

        $get = Twitter::get('friends/ids.json', ['screen_name'=>$username]);
        if( empty(Auth::user()->userinfo->points_twitter)  ) {
            if (in_array($this->twFriendID, $get->ids)) {
                $this->db->twGetFirendsIDs(100);

                $getCurrentSeason = $this->getCurrentSeason();
                $this->db->insertPoints(100, 'followyou',  $getCurrentSeason->id );
            }
        }
        
    }
    
    
    public function getTwTimeline(){

//https://api.twitter.com/1.1/followers/list.json?cursor=-1&screen_name=twitterdev&skip_status=true&include_user_entities=false
//cursor=-1&screen_name=twitterdev&skip_status=true&include_user_entities=false
       /* try
        {
            $response = Twitter::get('friends/ids.json', ['screen_name'=>'ruffles']);
        }
        catch (Exception $e)
        {
            echo "LOGS <br /><br />";
            dd(Twitter::logs());
        }

        dd($response);
            */


        return  Twitter::getUserTimeline(['screen_name' => 'ruffles', 'count' => 100, 'format' => 'object']);

    }
    
    
    

    
    
    
    /**********************
     * GET FAN PAGES
     ********************/
    
    public function fbGetLikeFanPages($token, $return=null){
        $get =  Facebook::get("http://graph.facebook.com/v2.7/me/likes/{$this->fbFanPage}", $token);
        $get = $get->getGraphObject();

        if( empty(Auth::user()->userinfo->points_facebook)  ) {
            if ( !empty($get->count() )) {
                $this->db->fbGetLikeFanPages(100);

                $getCurrentSeason = $this->getCurrentSeason();
                $this->db->insertPoints(100, 'fanpages',  $getCurrentSeason->id );
            }
        }

    }

    public function getFbTimeLine(/*(int)or(string)*/$page_id=null, $token=null){
        //$fb = App::make('SammyK\LaravelFacebookSdk\LaravelFacebookSdk');  //$user_id = (int)$user_id ;
        //$get = $fb->get("http://graph.facebook.com/v2.6/{$user_id}/picture", $token);

        $get = Facebook::get("https://graph.facebook.com/v2.7/Ruflesevida/feed", Auth::user()->access_token);

        dd($get->getGraphObject());
    }



    /****************
     * FACEBOOK LOGUIN WITH laravel-facebook-sdk
     ****************/
    
    public function fbLoginUrl($permissions = array(), $callback=null){
        $permissions = empty($permissions)? ['email'] : $permissions;  // Optional permissions
        $fb = App::make('SammyK\LaravelFacebookSdk\LaravelFacebookSdk'); //use App;
        return $fb->getLoginUrl($permissions, $callback);
    }



    
    public function fbCallback(){
        $fb = App::make('SammyK\LaravelFacebookSdk\LaravelFacebookSdk'); //use App;
        // Obtain an access token.
        try {
            $token = $fb->getAccessTokenFromRedirect();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        // Access token will be null if the user denied the request
        // or if someone just hit this URL outside of the OAuth flow.
        if (! $token) {
            // Get the redirect helper
            $helper = $fb->getRedirectLoginHelper();

            if (! $helper->getError()) {
                abort(403, 'Unauthorized action.');
            }

            // User denied the request
            dd(
                $helper->getError(),
                $helper->getErrorCode(),
                $helper->getErrorReason(),
                $helper->getErrorDescription()
            );
        }

        if (! $token->isLongLived()) {
            // OAuth 2.0 client handler
            $oauth_client = $fb->getOAuth2Client();

            // Extend the access token.
            try {
                $token = $oauth_client->getLongLivedAccessToken($token);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                dd($e->getMessage());
            }
        }

        $fb->setDefaultAccessToken($token);

        // Save for later
        Session::put('fb_user_access_token', (string) $token);

        // Get basic info on the user from Facebook.
        try {
            $response = $fb->get('/me');///me?fields=id,name,email
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        // Convert the response to a `Facebook/GraphNodes/GraphUser` collection
        $facebook_user = $response->getGraphUser();


        dd( $facebook_user);
        // Create the user if it does not exist or update the existing entry.
        // This will only work if you've added the SyncableGraphNodeTrait to your User model.
        $user = App\User::createOrUpdateGraphNode($facebook_user);

        // Log the user into Laravel
        Auth::login($user);

        return redirect('/')->with('message', 'Successfully logged in with Facebook');
        
    }
    
    public function fbUserAccessToken($token=null) {
        $fb = App::make('SammyK\LaravelFacebookSdk\LaravelFacebookSdk'); //use App;
        $token = empty($token)? Session::get('fb_user_access_token') : $token;


        $fields = '?fields=id,name,email,timezone,middle_name,location,locale,link,last_name,gender,first_name,birthday,age_range';
        $get = $fb->get("/me{$fields}", $token );
        $get = $get->getGraphUser();

       return $this->insertOrUpdtateAccessToken($get, $token);

    }
    
    public function insertOrUpdtateAccessToken($get, $token){
        $User = User::where('facebook_user_id', '=', $get->getId())->first();

        $array = ['name'=>$get->getFirstName(),
                'last_name'=>$get->getLastName(),
                'user_id'=>$User->id,
                'photo'=>"http://graph.facebook.com/v2.6/{$get->getId()}/picture",
                 ];


        if ( empty($User->access_token) or
            ($User->access_token != $token) ){
                $User->access_token = $token;
                $User->save();
                $User->userinfo()->insert($array);
        }
        $User->userinfo()->update($array);

        $User->offsetSet('userinfo', $User->userinfo);
        return $User;
    }


    public function getfbUserPicture(/*(int)*/$user_id, $token){
        //$fb = App::make('SammyK\LaravelFacebookSdk\LaravelFacebookSdk');  //$user_id = (int)$user_id ;
        //$get = $fb->get("http://graph.facebook.com/v2.6/{$user_id}/picture", $token);


       $get =  Facebook::get("https://graph.facebook.com/v2.7/{$user_id}/picture", $token);
        dd($get->getGraphObject());
    }


    public function getfbUserFriends(){

        //$get = Facebook::get("/me", Auth::user()->access_token);


        $get =  Facebook::get("https://graph.facebook.com/v2.7/me/friends", Auth::user()->access_token);
        //dd($get->getGraphObject());
        dd($get->getGraphUser());
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
     ** SESSIONS -> VIEWS SHARE
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
     * LIST TABLE / SELECT HTML
     *
     *
     ***************/
    /* _getListTable
     *
     *  esta funcion pertimte agrrupar todas las TABLE::list()
     *  de las tablas en un una funcion y mandarlas llamar
     *  en base a sus parametros, tabaja en conjunto con otras
     *  funciones,
     *
     * @NOTA
     * esta funcion requiere de asignar los valores MANUALMENTE
     * para reocuparlas  en el sistema
     *
     * @method
     * prirvate
     *
     * @params
     * $params => array [ table => (string -> obligatorio) alias con el que se guarda la tabla
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

     private function _getListTable($table=null){
         $lists = array();
         $lists['team'] = Team::where('id','<>', '');
         if( isset($table) ){ return $lists["{$table}"]; }
         return null;
     }


    /* _getList
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
     * $params => array [ table => (string -> obligatorio) alias con el que se guarda la tabla
     *                    id => (string) indicamaso un id para filtrar
     *                    where => (string) indicamos el camo en el cual filtramos
     *                    return => (string) permite forzar la respuesta en json
     *                    array => (boolean) el list te lo agrupa en un array
     *                    empty => (array) indica el array de inico del lists
     *                    selected => (string) indica indice con propiedad 'selected', para multiples 'selected' separar con comas
     *                    new_array => (boolean) generar un nuevo array dividiendo los evelmentos en $key => $value
     *                    order => (array) orden los valores del lists array('name'=>'ASC/DESC' )
     *                   ]
     *
     *
     * @return
     * object
     *
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     *
     */
    public function _getList($params = null){
        //$params = array('table'=>'TABLE',"id" => 1, "where" => "", "return" => "json", "array" => true, "empty"=>array(""=>--selecionar--), 'selected'=>'TABLE_id'=>'1');
        $params["return"] = isset( $params["return"] )? $params["return"] : "default" ;
        $params["table"] = isset($params["table"])? $params["table"] : null;
        $params["order"] = isset($params['order'])? $params["order"] : ['name'=>'ASC'];
        $params["selected"] = isset($params["selected"])? ["{$params["table"]}_id"=>$params["selected"]] : ["{$params["table"]}_id"=>null];


        $lists = $this->_getListTable($params["table"]);
        if( empty($lists) ){ dd("NO SE HA SELECONADO UNA TABLA y/o NO ES VALIDA") ;}
        if( isset($params["id"]) ){ $lists->where("{$params["where"]}", '=', $params["id"]); }
        if( isset($params["order"]) ){ $order = $this->_getFieldOrderBy($params["order"]); $lists->orderBy("{$order}", '=', $params["order"]["{$order}"]); }
        $lists = $lists->lists("name", "id");

        $this->_getSelectedSelectHTML($params["selected"]);
        if( isset($params["array"]) ){ $lists = $lists->toArray();  }
        if( isset($params["empty"]) ){ $this->_setEmptySelectHTML($params["empty"], $this->getIsArray($lists)); }
        if( isset($params["new_array"]) ){ $this->_getNewArrayKeyValue($lists); }
          

        if ( $params["return"] == "json") { return Response::json(["lists"=> $lists, 200 ]); }
        return $lists;
    }


    /* _getFieldOrderBy
   *
   * regresa el campo por el cual se ordena el lists
   * este puede ser cualquier field de la tabla
   *
   *
   * @method
   * private
   *
   *
   * @params
   * $order => array [ (array) orden los valores del lists array('name'=>'ASC/DESC' ) ]

   *
   *
   * @return
   * string
   *
   *
   * @author
   * fersaavedra85@hotmail.com
   *
   */
    private function _getFieldOrderBy($order){
        $order = array_keys($order);
        return $order[0];
    }

    /* _getNewArrayKeyValue -> &
    *
    * genera un nuevo array con los elementos
    * dividiendo en $key => $value y $key => $value ,
    * por defecto se asignas las claves array('id', 'name')
    *
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
    private function &_getNewArrayKeyValue(&$lists){
        $array = array();
        foreach($lists as $key => $value){
            $array[] = array("id" => $key, "name"=>$value);
        }
        $lists  = $array;
        return $lists;
        
    }
    

    /* _setEmptySelectHTML -> &
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
    public function &_setEmptySelectHTML($empty, &$array){
       $array = $empty + $array;
       return $array;
    }






    /* _getSelectedSelectHTML -> share
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



    /* is_json
     *
     * determina cuando el objeto ha respondido un objeto json.
     *
     *
     * @method
     * public
     *
     * @params
     * $object	=> object [ obligatorio, objeto debueto, puede ser un json o un objeto de query ]
     *
     *
     * @return
     * bool
     *
     * @author
     * fersaavedra85@hotmail.com
     *
     */

    private  function is_json($object) {

        if( !isset($object->id) ) {
            return isset($object->getData()->success) ? true : false;
        }
        return false;
    }


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










    /****************
     * FUCNCIONES , VARIABES, RETURNS
     *
     * POR REFERENCIA
     *
     ****************/

   /* getIsArray -> &
   *
   *  POR REFERECNIA
   *  Evalua si la variable es un array si no lo convierte en uno
   *
   *
   * @method
   * private
   *
   * @params
   * $array	=> object [ obligatorio, objeto debueto, puede ser un json o un objeto de query ]
   *
   *
   * @return -> &
   * array
   *
   * @author
   * fersaavedra85@hotmail.com
   *
   */
   private function &getIsArray(&$array){
        if(!is_array($array)){
            $array = $array->toArray();
            return $array;
        }
        return $array;
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