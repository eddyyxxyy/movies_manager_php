<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Represents a user in the system.
 * This is a data transfer object (DTO) or an entity, holding user data.
 */
class User
{
    public ?string $id = null; // UUID
    public string $fullName;
    public string $email;
    public string $passwordHash;
    public ?string $avatar = null;
    public string $createdAt;
    public string $updatedAt;

    /**
     * @param string $fullName The user's full name.
     * @param string $email The user's email address.
     * @param string $passwordHash The hashed password.
     */
    public function __construct(string $fullName, string $email, string $passwordHash)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $now = date('Y-m-d H:i:s');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    // You can add simple getters/setters or other data-related methods here.
    // Business logic (like hashing passwords) should reside in Services.
}