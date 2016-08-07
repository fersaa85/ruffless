<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class FootballPools
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="FootballPools",
 *   required={"name"}
 * )
 *
 */
class FootballPools extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'football_pools';

    //...


    public function team(){
        return $this->belongsTo('App\Models\Team');
    }
}