<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Point
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Point",
 *   required={"name"}
 * )
 *
 */
class Point extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'points';

    //...

    public function userinfo(){
        return $this->hasMany('App\Models\UserInfo', 'user_id', 'user_id');
    }

}