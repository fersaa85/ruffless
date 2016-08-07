<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class UserInfo
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="UserInfo",
 *   required={"name"}
 * )
 *
 */
class UserInfo extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'users_info';

    //...


    public function team(){
        return $this->hasOne('App\Models\Team');
    }

    /**
     * Get the user that owns the userinfo.
     */
    public function user(){
        return $this->belongsTo('App\User');
    }
    
    
    
}