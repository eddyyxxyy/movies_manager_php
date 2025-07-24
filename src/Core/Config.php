<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /**
     * Base directory of the project.
     */
    public const BASE_DIR = __DIR__ . '/../../';

    /**
     * App's source code directory.
     */
    public const APP_DIR = __DIR__ . '/../';

    /**
     * Directory to store cache files.
     */
    public const CACHE_DIR = self::BASE_DIR . 'cache/';

    /**
     * Directory to find view files.
     */
    public const VIEWS_DIR = self::BASE_DIR . 'src/View/';

    /**
     * Toggles debug mode. Should be false in production.
     */
    public const APP_DEBUG = true;
}
