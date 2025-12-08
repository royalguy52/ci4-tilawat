<?php

namespace App\Controllers\Api;

use App\Models\CourseModel;
use App\Models\CourseEnrollmentModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class Course extends ResourceController
{
    protected $format = 'json';

    // GET /api/courses
    public function index()
    {
        $user = $this->getUserFromToken();
        $courseModel = new CourseModel();

        $builder = $courseModel->where('status', 'active');

        // Optional filters based on user profile
        if ($user) {
            // gender filter
            if (!empty($user['gender'])) {
                $builder->groupStart()
                        ->where('gender_allowed', 'B')
                        ->orWhere('gender_allowed', $user['gender'])
                        ->groupEnd();
            }

            // age filter if dob present
            if (!empty($user['dob']) && $user['dob'] !== '0000-00-00') {
                $age = $this->calculateAge($user['dob']);
                if ($age !== null) {
                    $builder->groupStart()
                            ->groupStart()
                                ->where('min_age IS NULL', null, false)
                                ->orWhere('min_age <=', $age)
                            ->groupEnd()
                            ->groupStart()
                                ->where('max_age IS NULL', null, false)
                                ->orWhere('max_age >=', $age)
                            ->groupEnd()
                            ->groupEnd();
                }
            }
        }

        $courses = $builder->orderBy('id', 'ASC')->findAll();

        return $this->respond([
            'courses' => $courses,
        ]);
    }

    // POST /api/courses/{id}/enroll
    public function enroll($courseId = null)
    {
        $user = $this->getUserFromToken();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        if (empty($courseId) || ! ctype_digit((string) $courseId)) {
            return $this->failValidationErrors(['course_id' => 'Invalid course id.']);
        }

        $courseModel = new CourseModel();
        $course = $courseModel->find((int) $courseId);

        if (! $course || $course['status'] !== 'active') {
            return $this->failNotFound('Course not found or not active.');
        }

        $enrollModel = new CourseEnrollmentModel();

        // already enrolled?
        $existing = $enrollModel
            ->where('user_id', $user['id'])
            ->where('course_id', $course['id'])
            ->first();

        if ($existing && ! in_array($existing['status'], ['CANCELLED', 'EXPIRED'], true)) {
            return $this->respond([
                'message'   => 'Already enrolled in this course.',
                'enrollment'=> $existing,
            ]);
        }

        // compute dates
        $today = date('Y-m-d');
        $end   = null;
        if (! empty($course['duration_days']) && (int)$course['duration_days'] > 0) {
            $end = date('Y-m-d', strtotime("+{$course['duration_days']} days"));
        }

        $status = $course['is_paid'] ? 'PENDING' : 'ACTIVE';

        $data = [
            'user_id'   => $user['id'],
            'course_id' => $course['id'],
            'status'    => $status,
            'is_paid'   => (int)$course['is_paid'],
            'start_date'=> $status === 'ACTIVE' ? $today : null,
            'end_date'  => $status === 'ACTIVE' ? $end    : null,
        ];

        $db = Database::connect();
        $db->transStart();

        if ($existing) {
            $enrollId = $existing['id'];
            $enrollModel->update($enrollId, $data);
        } else {
            $enrollId = $enrollModel->insert($data);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failServerError('Could not enroll in course.');
        }

        $enrollment = $enrollModel->find($enrollId);

        return $this->respondCreated([
            'message'    => 'Enrollment saved.',
            'enrollment' => $enrollment,
        ]);
    }

    // --------- Helpers ---------

    protected function getUserFromToken(): ?array
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        $token = null;

        if (stripos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
        }
        if (! $token) {
            $token = $this->request->getGet('token');
        }
        if (! $token) {
            return null;
        }

        $userModel = new UserModel();
        $user = $userModel->where('api_token', $token)
                          ->where('status', 'active')
                          ->first();

        return $user ?: null;
    }

    protected function calculateAge(string $dob): ?int
    {
        try {
            $birth = new \DateTime($dob);
            $today = new \DateTime('today');
            return (int)$birth->diff($today)->y;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
