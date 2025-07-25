<?php

declare(strict_types=1);

namespace App\Services;

use App\DAO\UserDAO;
use App\Model\User;
use RuntimeException;

/**
 * Service for business logic related to users.
 * Interacts with UserDAO to perform data operations, but contains no direct PDO calls.
 */
class UserService
{
    /**
     * @param UserDAO $userDAO The User Data Access Object.
     */
    public function __construct(private UserDAO $userDAO)
    {
    }

    /**
     * Creates a new user with a hashed password.
     *
     * @param string $fullName The user's full name.
     * @param string $email The user's email.
     * @param string $plainPassword Plain text password.
     * @return User The created User object with its assigned UUID.
     * @throws RuntimeException If the email already exists or password hashing fails.
     */
    public function createUser(string $fullName, string $email, string $plainPassword): User
    {
        if ($this->userDAO->findByEmail($email)) {
            throw new RuntimeException("Email already registered.");
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            throw new RuntimeException("Failed to hash password.");
        }

        $user = new User($fullName, $email, $hashedPassword);
        $this->userDAO->save($user);
        return $user;
    }

    /**
     * Authenticates a user.
     *
     * @param string $email
     * @param string $plainPassword Plain text password.
     * @return User|null The User object if credentials are valid, otherwise null.
     */
    public function authenticateUser(string $email, string $plainPassword): ?User
    {
        $user = $this->userDAO->findByEmail($email);
        if (!$user) {
            return null; // User not found
        }

        if (!password_verify($plainPassword, $user->passwordHash)) {
            return null; // Incorrect password
        }

        return $user;
    }

    /**
     * Gets a user by ID.
     * @param string $id The user's UUID.
     * @return User|null
     */
    public function getUserById(string $id): ?User
    {
        return $this->userDAO->findById($id);
    }

    /**
     * Gets a user by email.
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userDAO->findByEmail($email);
    }
}