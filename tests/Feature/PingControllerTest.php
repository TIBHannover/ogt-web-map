<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
use Tests\TestCase;

class PingControllerTest extends TestCase
{
    /**
     * Test ping request.
     *
     * @return void
     */
    public function testPing()
    {
        $this->get('/api/ping')
            ->assertStatus(Response::HTTP_OK)
            ->assertSee('pong');
    }
}
