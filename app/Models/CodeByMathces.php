<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class CodeByMathces
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="CodeByMathces",
 *   required={"name"}
 * )
 *
 */
class CodeByMathces extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'codes_by_mathces';

    //...

    public $timestamps = false;

    public function mathce(){
        return $this->belongsTo('App\Models\Mathces');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }


    public function code(){
        return $this->belongsTo('App\Models\Code');
    }

}