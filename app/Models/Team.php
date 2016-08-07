<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Team
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Team",
 *   required={"name"}
 * )
 *
 */
class Team extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'teams';

    //...


}