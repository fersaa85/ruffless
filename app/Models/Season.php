<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Season
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Season",
 *   required={"name"}
 * )
 *
 */
class Season extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'season';

    //...



}