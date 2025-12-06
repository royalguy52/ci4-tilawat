<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class Auth extends ResourceController
{
    protected $format = 'json';

    // POST /api/auth/register
    public function register()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'its_id'   => 'required|min_length[5]|max_length[30]|is_unique[users.its_id]',
            'name'     => 'required|min_length[2]|max_length[150]',
            'password' => 'required|min_length[6]|max_length[100]',
            'phone'    => 'permit_empty|max_length[30]',
            'email'    => 'permit_empty|valid_email|max_length[150]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $itsId    = trim($data['its_id']);
        $name     = trim($data['name']);
        $password = $data['password'];
        $phone    = $data['phone'] ?? null;
        $email    = $data['email'] ?? null;

        $userModel = new UserModel();

        // TODO: in future – call ITS API here to fetch extra details.
        // For now we trust provided ITS + name.

        $token = $this->generateToken();

        $userId = $userModel->insert([
            'its_id'        => $itsId,
            'name'          => $name,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone'         => $phone,
            'email'         => $email,
            'status'        => 'active',
            'api_token'     => $token,
        ]);

        if (! $userId) {
            return $this->failServerError('Could not create user.');
        }

        // attach "student" role
        $studentRoleId = $this->getRoleIdByName('student');
        if ($studentRoleId !== null) {
            $db = Database::connect();
            $db->table('user_roles')->ignore(true)->insert([
                'user_id' => $userId,
                'role_id' => $studentRoleId,
            ]);
        }

        $user = $userModel->find($userId);
        unset($user['password_hash']);

        return $this->respondCreated([
            'message' => 'Registration successful.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // POST /api/auth/login
    public function login()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $itsId    = trim($data['its_id'] ?? '');
        $password = $data['password'] ?? '';

        if ($itsId === '' || $password === '') {
            return $this->failValidationErrors([
                'its_id'   => 'ITS ID is required.',
                'password' => 'Password is required.',
            ]);
        }

        $userModel = new UserModel();
        $user = $userModel->where('its_id', $itsId)
                          ->where('status', 'active')
                          ->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            // generic message to avoid leaking which part is wrong
            return $this->failUnauthorized('Invalid ITS ID or password.');
        }

        // generate a new token on each login
        $token = $this->generateToken();
        $userModel->update($user['id'], ['api_token' => $token]);

        unset($user['password_hash']);
        $user['api_token'] = $token;

        return $this->respond([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // GET /api/auth/me
    public function me()
    {
        $user = $this->getUserFromToken();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        unset($user['password_hash']);

        return $this->respond([
            'user' => $user,
        ]);
    }

    // ------------ Helpers -------------

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32)); // 64-char token
    }

    protected function getRoleIdByName(string $name): ?int
    {
        $db = Database::connect();
        $row = $db->table('roles')->where('name', $name)->get()->getRow();
        return $row ? (int) $row->id : null;
    }

    protected function getUserFromToken(): ?array
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        $token = null;
        if (stripos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
        }

        // Fallback: token in query string ?token=...
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
}
