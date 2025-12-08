<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    protected $table            = 'courses';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'code',
        'name',
        'description',
        'gender_allowed',
        'min_age',
        'max_age',
        'language_code',
        'is_paid',
        'price',
        'duration_days',
        'is_incremental',
        'mentor_required',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;
}
