<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\JsonStorageService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures
{
    private UserPasswordHasherInterface $hasher;
    private JsonStorageService $storage;

    public function __construct(UserPasswordHasherInterface $hasher, JsonStorageService $storage)
    {
        $this->hasher = $hasher;
        $this->storage = $storage;
    }

    public function load(): void
    {
        $users = $this->storage->read('users.json');
        if (!empty($users)) {
            return;
        }

        $admin = new User(1, 'admin', 'Admin', 'admin@admin.com', '');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));

        $this->storage->write('users.json', [[
            'id' => $admin->getId(),
            'username' => $admin->getUsername(),
            'firstname' => $admin->getFirstname(),
            'email' => $admin->getEmail(),
            'password' => $admin->getPassword(),
        ]]);
    }
}