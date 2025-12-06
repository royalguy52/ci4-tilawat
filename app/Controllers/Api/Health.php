<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Health extends ResourceController
{
    public function index()
    {
        $env = env('CI_ENVIRONMENT', 'production');

        return $this->respond([
            'status'      => 'ok',
            'message'     => 'Updated Tilawat API is running',
            'environment' => $env,
            'time'        => date('c'),
        ]);
    }
}
