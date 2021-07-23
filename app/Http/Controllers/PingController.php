<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PingController extends Controller
{
    /**
     * Ping check
     *
     * @return Response
     */
    public function ping(): Response
    {
        return response('pong', Response::HTTP_OK);
    }
}
