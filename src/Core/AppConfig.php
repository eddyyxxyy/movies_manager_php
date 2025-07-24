<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application-wide configuration accessor.
 * Encapsulates all environment-based and static config options.
 */
class AppConfig
{
    /**
     * Create a new AppConfig instance.
     *
     * @param array<string, mixed> $config The configuration array.
     */
    public function __construct(private array $config)
    {
    }

    /**
     * Retrieve the full configuration array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Retrieve a specific config value using dot notation.
     *
     * @param string $key The key to retrieve (e.g., 'database.host').
     * @param mixed|null $default The default value to return if the key is not found.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool) ($this->get('debug') ?? false);
    }
}