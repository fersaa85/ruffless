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

class FootballPoolsController extends Controller
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
        
        dd("getIndex");
    }


    public function getCodigos(){
        $code = Request::input('code');
       $_vaidateCodes =  $this->secure->_validateCodes( $code, "back");
        if( $_vaidateCodes !== true){
            return $_vaidateCodes;
        }

        $this->db->insertBurnedCode($code);
        
        return Redirect::to('reto/participa');
        
    }
    
    public function getParticipa(){

        $getMathces =  $this->core->getMathcesByUser();
        return View::make('footballpools.footballpools', compact('getMathces'));

    }



    public function postParticipa(){


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
        $configEmail["to"]  = Auth::user()->email;
        $configEmail["blade"] = 'emails.footballpools';
        $this->core->sendEmailSMTP($configEmail,  $array );




          return  Redirect::to('reto/gracias');


    }


    public function getGracias(){

        $getFacebookShare = $this->core->getFacebookShare(Request::url());
        $getTwitterShare = $this->core-> getTwitterShare(Request::url(), "Reto Ruffles NFL");
        return View::make('footballpools.thankyou', compact('getFacebookShare',
                                                            'getTwitterShare'));
    }

    public function postShare(){

       $getCurrentSeason = $this->core->getCurrentSeason() ; 
       $this->db->insertShare(Request::input('share'), $getCurrentSeason->id );

        return Response::json(['done', true],200);
    }


    public function getRanking(){

       $getRanking =  $this->core->getRanking();
        return View::make('footballpools.ranking');
    }

}