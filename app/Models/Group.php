<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * Class Group
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Group",
 *   required={"name"}
 * )
 *
 */
class Group extends Model
{
    use SoftDeletes;
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'groups';

    //...

    public function groupbyusers(){
        return $this->hasMany('App\Models\GroupByUsers');
    }
}