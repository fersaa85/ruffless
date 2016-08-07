<?php
namespace App\Models;

/**
 * Class Machine
 *
 * @package App
 *
 * @SWG\Definition(
 *   definition="Machine",
 *   required={"name"}
 * )
 *
 */
class Machine extends Model
{
    /**
     * @SWG\Property(format="string")
     * @var string
     */
    protected $table = 'machines';

    //...
}