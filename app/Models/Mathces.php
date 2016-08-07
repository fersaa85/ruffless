<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Mathces
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Mathces",
 *   required={"name"}
 * )
 *
 */
class Mathces extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'matches';

    //...

    public function teamlocal(){
        return $this->belongsTo('App\Models\Team', 'local_id' );
    }

    public function teamvisit(){
        return $this->belongsTo('App\Models\Team', 'visit_id' );
    }
    
}