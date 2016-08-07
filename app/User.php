<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SammyK\LaravelFacebookSdk\SyncableGraphNodeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * Class User
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="User",
 *   required={"name"}
 * )
 *
 */
class User extends Authenticatable
{
    use SoftDeletes;

    use SyncableGraphNodeTrait;
    protected static $graph_node_field_aliases = [
        'id' => 'facebook_user_id',
        //'graph_node_field_name' => 'database_column_name',
    ];
    //protected static $graph_node_fillable_fields = ['facebook_user_id', 'name', 'email'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];



    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'access_token', 'created_at', 'updated_at', 'deleted_at',
    ];



    /**
     * Get the users_info record associated with the user.
     */
    public function userinfo(){
        return $this->hasOne('App\Models\UserInfo');
    }

}
