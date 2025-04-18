<?php

namespace App\Security;

use App\Entity\User;
use App\Service\JsonStorageService;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private JsonStorageService $storage;

    /**
     *
     * @param JsonStorageService $storage
     */
    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param string $identifier
     * @return UserInterface
     * @throws UserNotFoundException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
{
    $users = $this->storage->read('users.json');
    
    if (!is_array($users)) {
        throw new \RuntimeException('Invalid users data format');
    }

    foreach ($users as $user) {
        if (!isset($user['email'])) {
            continue;
        }
        
        if ($user['email'] === $identifier) {
            if (!isset($user['password'])) {
                throw new \RuntimeException('User missing password');
            }
            
            return new User(
                $user['id'] ?? 0,
                $user['username'] ?? '',
                $user['firstname'] ?? '',
                $user['email'],
                $user['password']
            );
        }
    }
    
    throw new UserNotFoundException(sprintf('User "%s" not found', $identifier));
}

    /**
     * @param string $identifier
     * @return UserInterface
     * @throws UserNotFoundException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /**
     * @param string $username
     * @return UserInterface
     * @throws UserNotFoundException
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        $users = $this->storage->read('users.json');
        $userData = array_filter($users, fn($u) => $u['username'] === $username);

        if (empty($userData)) {
            throw new UserNotFoundException();
        }

        $userData = reset($userData);
        return new User(
            $userData['id'],
            $userData['username'],
            $userData['firstname'],
            $userData['email'],
            $userData['password']
        );
    }
}