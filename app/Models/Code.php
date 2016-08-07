<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Code
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Code",
 *   required={"name"}
 * )
 *
 */
class Code extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'codes';

    //...


    public function codebymathces()    {
        return $this->hasMany('CodeByMathces',  'code_id', 'id');
    }
}