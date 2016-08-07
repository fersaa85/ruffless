<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * Class GroupByUsers
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="GroupByUsers",
 *   required={"name"}
 * )
 *
 */
class GroupByUsers extends Model
{
    use SoftDeletes;
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'groups_by_users';

    //...

    public function group(){
        return $this->belongsTo('App\Models\Group', 'group_id');
    }


    public function userinfo(){
        return $this->hasMany('App\Models\UserInfo', 'user_id', 'user_id');
    }
}