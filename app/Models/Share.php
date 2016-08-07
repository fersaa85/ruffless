<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Share
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Share",
 *   required={"name"}
 * )
 *
 */
class Share extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'share';

    //...

    public $timestamps = false;


}