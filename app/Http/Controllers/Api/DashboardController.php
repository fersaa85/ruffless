<?php
namespace App\Http\Controllers\Api;


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

use Facebook;

use App\Http\Controllers\ApiController;
use  App\Http\Controllers\CoreController;
use  App\Http\Controllers\SecureController;
use  App\Http\Controllers\DBController;
/**
 * Class DashboardController
 *
 * @package App\Http\Controllers\api
 */
class DashboardController extends ApiController {

    public $core;
    public $secure;
    public $db;
    public function __construct(){
        $this->core = new CoreController();
        $this->secure = new SecureController();
        $this->db = new DBController();

        $this->core->loginAuthCheck();
    }

    


    /**
     * Loguin app
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Post(
     *     path="api/dashboard/login",
     *     description="Loguin app.",
     *     operationId="api.dashboard.login",
     *     tags={"login"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *     @SWG\Parameter(
     *         name="token",
     *         description="FB token access",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function postLogin(){

        $token = Request::input('token');
        $response =  Facebook::get('/me',$token );
        // Convert the response to a `Facebook/GraphNodes/GraphUser` collection
        $facebook_user = $response->getGraphUser();


        // Create the user if it does not exist or update the existing entry.
        // This will only work if you've added the SyncableGraphNodeTrait to your User model.
        $user = App\User::createOrUpdateGraphNode($facebook_user);


        // Log the user into Laravel
        Auth::login($user);

        $user = $this->core->fbUserAccessToken($token);

        return Response::json([$user],200);

    }




    /**
     * getProfile
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/profile/{id}",
     *     description="Recupera el perfil del usuario.",
     *     operationId="api.dashboard.getProfile",
     *     tags={"profile"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="path",
     *         required=true,
     *         type="integer",
     *         format="int64"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function getProfile($id){

        $teams =  $this->core->_getList(['table'=>'team']);
        return Response::json(['teams'=>$teams,  'users'=>$this->core->getUser($id)],200);

    }

    

    /**
     * postProfile
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     * @SWG\Post(
     *     path="api/dashboard/profile",
     *     description="Actuliza la informacion del perfil del usuario.",
     *     operationId="api.dashboard.postProfile",
     *     tags={"profile"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         description="email del usuario",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         description="nombre del usuario",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="last_name",
     *         description="Apellido del usuario",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="phone",
     *         description="Telefono del usuario",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         name="phone2",
     *         description="Telefono secundario",
     *         in="formData",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="email2",
     *         description="Email secundario",
     *         in="formData",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         description="Password",
     *         in="formData",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="team_id",
     *         description="Equipo favorito",
     *         in="formData",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function postProfile(){
       $id = Request::input('id');

        $validateRequest = $this->secure->validateRequest("json" ,$this->secure->_getRuleProfile(true, $id));
        if( $validateRequest !== true ){ return  $validateRequest; }


        $params['id'] =  $id ;
        $this->db->insertOrUpdateUser($params);

        return Response::json([$this->core->getUser($id)],200);

    }



    /**
     * getJoinFacebook
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/join-facebook/{id}",
     *     description="Busca si el usuario se ha suscrito a la fanpages y le da 100 puntos.",
     *     operationId="api.dashboard.getJoinFacebook",
     *     tags={"profile"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Response(
     *         response=200,
     *         description="success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *  )
     */
    public function getJoinFacebook($id){
        $user =  $this->core->getUser($id);
        Auth::login($user);
        $this->core->fbGetLikeFanPages( $user->access_token );
        return Response::json(['done'=>true],200);
    }





    /**
     * getJoinTwitter
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/join-twitter/{id}",
     *     description="Busca si el usuario se es seguidor en twitter y le da 100 puntos.",
     *     operationId="api.dashboard.getJoinTwitter",
     *     tags={"profile"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Response(
     *         response=200,
     *         description="success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *  )
     */
    public function getJoinTwitter($id){

        $user =  $this->core->getUser($id);
        Auth::login($user);
        if( empty($user->userinfo->twitter) ){
            return Response::json(['errors'=>"Por favor actuliza tu cuenta de twitter, unete y ganas putnos."],400);
        }

        $this->core->twGetFirendsIDs($user->userinfo->twitter);
        return Response::json(['done'=>true],200);

    }





    /**
     * postCodes
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Post(
     *     path="api/dashboard/codes",
     *     description="Ingresa los codigos de participacion, y los quema(desactva), para que solo se utilicen una vez",
     *     operationId="api.dashboard.postCodes",
     *     tags={"reto"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         description="Codigo de accesos, comparados contra la base",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function postCodes(){


        $code = Request::input('code');
        $id = Request::input('id');


        $_vaidateCodes =  $this->secure->_validateCodes( $code, "json");
        if( $_vaidateCodes !== true){
            return $_vaidateCodes;
        }

        $user =  $this->core->getUser($id);
        Auth::login($user);
        $this->db->insertBurnedCode($code);

        return Response::json([$this->core->getMathcesByUser()],200);

    }





    /**
     * postParticipates
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Post(
     *     path="api/dashboard/participates",
     *     description="Ingresa los equipos y pronosticos del reto por temporada",
     *     operationId="api.dashboard.postParticipates",
     *     tags={"reto"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     produces={"application/xml", "application/json"},
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="tie[]",
     *         in="query",
     *         description="Empates selcecionados enviar en formato (array) en formato (mathce_id-tie)",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="string"),
     *         collectionFormat="multi"
     *     ),
     *       @SWG\Parameter(
     *         name="local[]",
     *         in="query",
     *         description="Empates selcecionados enviar en formato (array) en formato (mathce_id-local_id)",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="string"),
     *         collectionFormat="multi"
     *     ),
     *
     *      @SWG\Parameter(
     *         name="visit[]",
     *         in="query",
     *         description="Empates selcecionados enviar en formato (array) en formato (mathce_id-visit_id)",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="string"),
     *         collectionFormat="multi"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function postParticipates(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);


        $request = Request::all();


        foreach($request as $key => $var ){

            if(is_array($var)) {

                foreach ($var as  $value) {

                    $validateFootballPoolsUnique = $this->secure->validateFootballPoolsUnique($value, 'json');


                    /*
                     * evitamos que ingresen dos resultados diferentes para un mismo partido
                     */
                    if ($validateFootballPoolsUnique !== true) {
                        continue;
                    }

                    if($key == "tie"){
                        $this->db->insertFootballPools('tie', $value);
                    }else{
                        $this->db->insertFootballPools('winner', $value);
                    }

                }

            }

        }


        /*
        if ($validateFootballPoolsUnique !== true) {
            return $validateFootballPoolsUnique;
        }
        */


        /*
         * SEND MAIL
         */
        $getCodeMathcesFootballPoolsByUser = $this->core->getCodeMathcesFootballPoolsByUser();
        $html = "";
        foreach ($getCodeMathcesFootballPoolsByUser as $value){
            $winner = ($value->footballpools->result_match == "winner")? $value->footballpools->team->name : "EMPATE";
            $html .= "<tr>
                        <td>{$value->code->code}</td>
                        <td>{$value->mathce->teamlocal->name} VS {$value->mathce->teamvisit->name}</td>
                        <td>{$winner}</td>
                    </tr>";
        }


        $array = ['date' => date("H:i:s, d-m-Y"),
            'user'=>$this->core->getNameByUser(),
            'html'=>$html];



        $configEmail["subject"] = "Reto Ruffles NFL";
        $configEmail["title"] = "Reto Ruffles NFL";
        $configEmail["from"]  = "no-replay@ruffles.com";
        $configEmail["to"]  = $user->email;
        $configEmail["blade"] = 'emails.footballpools';
        $this->core->sendEmailSMTP($configEmail,  $array );


        unset( $array['html']);
        return Response::json($array,200);
    }



     /**
      * postShare
      *
      * @return \Illuminate\Http\JsonResponse
      *
      *
      *
      *
      * @SWG\Post(
      *     path="api/dashboard/share",
      *     description="Ingresa los equipos y pronosticos del reto por temporada",
      *     operationId="api.dashboard.postShare",
      *     tags={"reto"},
      *     consumes={
      *         "application/xml",
      *         "application/json",
      *         "application/x-www-form-urlencoded"
      *     },
      *     @SWG\Parameter(
      *         name="id",
      *         description="ID del usuario",
      *         in="formData",
      *         required=true,
      *         type="integer"
      *     ),
      *     @SWG\Parameter(
      *         name="share",
      *         in="query",
      *         description="Tipo de red en donde se va a compartir, por compartir un ememnto te da puntos por elemento",
      *         required=true,
      *         type="string",
      *         enum={"facebook", "twitter"}
      *     ),
      *     @SWG\Response(
      *         response=200,
      *         description="Login success."
      *     ),
      *     @SWG\Response(
      *         response=401,
      *         description="Unauthorized action.",
      *     ),
      *
      *
      * )
      */
    public function postShare(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        $getCurrentSeason = $this->core->getCurrentSeason() ;
        $this->db->insertShare(Request::input('share'), $getCurrentSeason->id );

        return Response::json(['done', true],200);

    }



    /**
     * getRanking
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/ranking/{$id}",
     *     description="Regresa el ranquin, general individual y por quincena.",
     *     operationId="api.dashboard.getRanking",
     *     tags={"reto"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function getRanking(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        return Response::json([$this->core->getRanking()],200);
    }










    /**
     * getGroup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/group",
     *     description="Regresa los grupos a los que le usuario pertenece , asi como los amigos confirmados .",
     *     operationId="api.dashboard.getGroup",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function getGroup(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);


        return Response::json([$this->core->getGroups()],200);

    }










    /**
     * postGroup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Post(
     *     path="api/dashboard/group",
     *     description="Regresa el ranquin, general individual y por quincena.",
     *     operationId="api.dashboard.postGroup",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function postGroup(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);


        $validateRequest = $this->secure->validateRequest("json" ,$this->secure->_getRuleGroup());
        if( $validateRequest !== true ){ return  $validateRequest; }

        $validateGroupUnique  = $this->secure->validateGroupUnique(Request::input('name'),  "json");
        if( $validateGroupUnique !== true ){ return  $validateGroupUnique; }

        return Response::json([$this->db-> insertOrUpdateGroup()],200);

    }



    /**
     * deleteGroup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Delete(
     *     path="api/dashboard/group",
     *     description="Regresa el ranquin, general individual y por quincena.",
     *     operationId="api.dashboard.deleteGroup",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *     @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="group_id",
     *         description="group_id del grupo que se desea eliminar",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function deleteGroup(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        return Response::json([$this->db->deleteGroup(Request::input('group_id'))],200);
    }


    /**
     * postInvite
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Post(
     *     path="api/dashboard/invite",
     *     description="Regresa el ranquin, general individual y por quincena.",
     *     operationId="api.dashboard.postInvite",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         description="ID del usuario, amigo al que se desea inviatar al grupo",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="group_id",
     *         description="grupo al que se esta invitando al usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function postInvite(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        $group_id = Request::input('group_id');
        $user_id = Request::input('user_id');

        $validateGroupByUsersUnique = $this->secure->validateGroupByUsersUnique($group_id, $user_id, "json");
        if( $validateGroupByUsersUnique !== true ){ return  $validateGroupByUsersUnique; }

        $this->db->insertFriends($group_id, $user_id);
        
        return Response::json(['done'=>true ],200);

    }


    /**
     * getInvite
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/invite",
     *     description="los grupos a los que haz sido invitado.",
     *     operationId="api.dashboard.getInvite",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function getInvite(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        return Response::json([$this->core->getGroupsPending()],200);

    }

    /**
     * putInvite
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Put(
     *     path="api/dashboard/invite",
     *     description="Confirma si el usuario quiere pertenecer al grupo.",
     *     operationId="api.dashboard.putInvite",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="group_by_user_id",
     *         description="grupo al que se esta invitando al usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function putInvite(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        $this->db->updateFriends(Request::input('group_by_user_id'));

        return Response::json(['done'=>true ],200);

    }

    /**
     * deleteInvite
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Delete(
     *     path="api/dashboard/invite",
     *     description="Confirma si el usuario quiere pertenecer al grupo.",
     *     operationId="api.dashboard.deleteInvite",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="group_by_user_id",
     *         description="grupo al que se esta invitando al usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function deleteInvite(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);

        $this->db->deleteFriends(Request::input('group_by_user_id'));

        return Response::json(['done'=>true ],200);

    }



    /**
     * getRankingGroup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/ranking-group",
     *     description="Regresa el rankig del grupo.",
     *     operationId="api.dashboard.getRankingGroup",
     *     tags={"grupos"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *      @SWG\Parameter(
     *         name="id",
     *         description="ID del usuario",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="group_id",
     *         description="grupo_id, del cual se quiere conocer el ranking",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function getRankingGroup(){
        $user =  $this->core->getUser(Request::input('id'));
        Auth::login($user);
        
        return Response::json( $this->core->getRankingGroup(Request::input('group_id') ),200);
    }



    /**
     * getTimeLine
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *
     *
     *
     * @SWG\Get(
     *     path="api/dashboard/time-line",
     *     description="time line de facebook y twitter de ruffles.",
     *     operationId="api.dashboard.getTimeLine",
     *     tags={"timeline"},
     *     consumes={
     *         "application/xml",
     *         "application/json",
     *         "application/x-www-form-urlencoded"
     *     },
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Login success."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     ),
     *
     *
     * )
     */
    public function getTimeLine(){

        return Response::json( $this->core->getUserTimeline(),200);
    }


}