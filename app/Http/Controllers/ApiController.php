<?php
namespace App\Http\Controllers;
/**
 * Class ApiController
 *
 * @package App\Http\Controllers
 *
 * @SWG\Swagger(
 *     basePath="/ruffless/public/",
 *     host="develop.com",
 *     schemes={"http"},
 *     @SWG\Info(
 *         version="1.0",
 *         title="API",
 *         @SWG\Contact(name="fersaavedra85@hotmail.com", url="https://abostudio.mx"),
 *     ),
 *     @SWG\Definition(
 *         definition="Error",
 *         required={"code", "message"},
 *         @SWG\Property(
 *             property="code",
 *             type="integer",
 *             format="int32"
 *         ),
 *         @SWG\Property(
 *             property="message",
 *             type="string"
 *         )
 *     )
 * )
 */
class ApiController extends Controller
{
}