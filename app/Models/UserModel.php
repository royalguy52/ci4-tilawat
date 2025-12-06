<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'its_id',
        'password_hash',
        'name',
        'gender',
        'dob',
        'language_pref',
        'phone',
        'email',
        'api_token',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false; // we use DB defaults
}
