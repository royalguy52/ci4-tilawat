<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Health extends ResourceController
{
    public function index()
    {
        return $this->respond([
            'status'  => 'ok',
            'message' => 'Tilawat API is running (local)',
            'time'    => date('c'),
        ]);
    }
}
