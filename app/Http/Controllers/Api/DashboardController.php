<?php
namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\ApiController;
/**
 * Class DashboardController
 *
 * @package App\Http\Controllers\api
 */
class DashboardController extends ApiController {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="api/dashboard",
     *     description="Returns dashboard overview.",
     *     operationId="api.dashboard.index",
     *     produces={"application/json"},
     *     tags={"dashboard"},
     *     @SWG\Response(
     *         response=200,
     *         description="Dashboard overview."
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized action.",
     *     )
     * )
     */
    public function getIndex()
    {
        return response()->json([
            'result'    => [
                'statistics' => [
                    'users' => [
                        'name'  => 'Name',
                        'email' => 'user@example.com'
                    ]
                ],
            ],
            'message'   => '',
            'type'      => 'success',
            'status'    => 0
        ]);
    }







    




}