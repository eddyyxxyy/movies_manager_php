<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Controller responsible for homepage and greetings.
 */
class HomeController
{
    /**
     * Homepage action.
     *
     * @param Request $request HTTP request object
     * @return Response HTML response with welcome message
     */
    public function index(Request $request): Response
    {
        $html = View::render(
            'home',
            [
                'message' => 'Welcome to Movies Manager!',
            ],
            'base-layout',
        );

        return Response::html($html);
    }
}
