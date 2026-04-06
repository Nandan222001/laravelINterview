<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }

    public function getVerifiedUsers(): mixed
    {
        return $this->repository->findVerifiedUsers();
    }

    public function register(array $data): User
    {
        return $this->repository->createWithHash($data);
    }

    public function updateProfile(int|string $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        return $this->repository->update($id, $data);
    }
}
