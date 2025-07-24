<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;

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
