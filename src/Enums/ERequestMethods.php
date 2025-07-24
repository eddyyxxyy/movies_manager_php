<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enumeration of supported HTTP request methods.
 */
enum ERequestMethods: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';
}
