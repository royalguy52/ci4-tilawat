<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseEnrollmentModel extends Model
{
    protected $table            = 'course_enrollments';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'user_id',
        'course_id',
        'status',
        'is_paid',
        'payment_ref',
        'start_date',
        'end_date',
        'requested_extension_days',
        'extension_reason',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;
}
