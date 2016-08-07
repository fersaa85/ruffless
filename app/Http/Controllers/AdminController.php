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
use App\Models\Mathces;

use  App\Http\Controllers\CoreController;
use  App\Http\Controllers\SecureController;
use  App\Http\Controllers\DBController;

class AdminController extends Controller
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

    }

    public function getResults(){


        $getMathces =  $this->core->getMathces();
        return View::make('admin.add', compact('getMathces'));
    }


    public function postResults(){

        $value = Request::input('value');
        list($i, $team_id)  = explode("-", $value);

        if ( strpos("tie", Request::input('name') ) !== false) {
           $data = $this->db->updateMathces('tie', $value);
        }else{
           $data = $this->db->updateMathces('winner', $value);
        }


        $getFootballPoolsResults =  $this->core->getFootballPoolsResults($data->result_mathce, $data->team_id);

        foreach ($getFootballPoolsResults as $value){
           $validateGamePointsUnique = $this->secure->validateGamePointsUnique( "game-{$data->id}", $data->season_id, $value->user_id );
           if($validateGamePointsUnique !== true){ continue; }

            $this->db->insertPoints($data->points, "game-{$data->id}", $data->season_id, $value->user_id);
            $this->db->updatePoints($data->points, $value->user_id);
        }

        dd("resultados guardados");
        return Redirect::to('admin');
    }

}