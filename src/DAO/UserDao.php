<?php

declare(strict_types=1);

namespace App\DAO;

use App\Model\User;
use PDO;
use RuntimeException;

/**
 * Data Access Object for Users.
 * Responsible for direct database interactions for the User model.
 */
class UserDAO
{
    /**
     * @param PDO $pdo The PDO database connection instance.
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Saves a new user to the database.
     * For PostgreSQL, uses RETURNING id to get the generated UUID.
     *
     * @param User $user The User object to be saved.
     * @return string The UUID of the inserted user.
     * @throws RuntimeException If the save operation fails or new ID cannot be retrieved.
     */
    public function save(User $user): string
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, avatar, created_at, updated_at)
             VALUES (:full_name, :email, :password_hash, :avatar, :created_at, :updated_at)
             RETURNING id"
        );
        $success = $stmt->execute([
            'full_name' => $user->fullName,
            'email' => $user->email,
            'password_hash' => $user->passwordHash,
            'avatar' => $user->avatar,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
        ]);

        if (!$success) {
            throw new RuntimeException("Failed to save user.");
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result || !isset($result['id'])) {
            throw new RuntimeException("Failed to retrieve new user ID after insertion.");
        }

        $user->id = $result['id'];
        return $user->id;
    }

    /**
     * Finds a user by ID.
     *
     * @param string $id The user's UUID.
     * @return User|null
     */
    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        return $this->mapDataToUser($data);
    }

    /**
     * Finds a user by email.
     *
     * @param string $email The user's email.
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }
        return $this->mapDataToUser($data);
    }

    /**
     * Maps database row data to a User object.
     *
     * @param array $data The associative array of data from a database row.
     * @return User
     */
    private function mapDataToUser(array $data): User
    {
        $user = new User($data['full_name'], $data['email'], $data['password_hash']);
        $user->id = $data['id'];
        $user->avatar = $data['avatar'];
        $user->createdAt = $data['created_at'];
        $user->updatedAt = $data['updated_at'];
        return $user;
    }

    // TODO: Add methods for update, delete, all, etc. as needed by the services
}