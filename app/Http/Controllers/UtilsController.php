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



use App\User;
use App\Models\Code;
use App\Models\Mathces;

class UtilsController extends Controller
{


    public function getImportCodes()
    {



        if (($handle = fopen(public_path("assets/codes-1.csv"), "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {



               $insert =  new Code();
                $insert->code = $data[0];
                $insert->save();

               
            }

        }

    }



    public function getImportMathces(){
        if (($handle = fopen(public_path("assets/calendario de partidos.csv"), "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if($data[0] == "LOCAL" or empty($data[0]) ){ continue; }


                $array = explode("/", $data[2]);
                $date = "{$array[2]}-{$array[1]}-{$array[0]} 00:00:00";
                $Mathces = new Mathces();
                $Mathces->local_id = $data[0];
                $Mathces->visit_id = $data[1];
                $Mathces->date_mathce = $date ;
                $Mathces->season_id = $data[3];
                $Mathces->save();

               echo $Mathces->local_id ."<br />";


            }

        }
    }
    
}