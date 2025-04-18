<?php
namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private JsonStorageService $storage;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        JsonStorageService $storage,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->storage = $storage;
        $this->passwordHasher = $passwordHasher;
    }

    public function authenticate(string $email, string $password): ?User
    {
        $users = $this->storage->read('users.json');
        $userData = array_filter($users, fn($u) => $u['email'] === $email);

        if (empty($userData)) {
            return null;
        }

        $userData = reset($userData);
        $user = new User(
            $userData['id'],
            $userData['username'],
            $userData['firstname'],
            $userData['email'],
            $userData['password']
        );

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }
}