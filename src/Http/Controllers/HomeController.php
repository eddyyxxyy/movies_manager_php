<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ViewInterface;
use App\Core\AppConfig;
use App\Core\Http\Response;

/**
 * Controller for handling home page requests.
 */
class HomeController
{
    /**
     * Show the application's home page.
     *
     * @param ViewInterface $view The view renderer service.
     * @param AppConfig $config The application configuration service.
     * @return Response The HTTP response.
     */
    public function index(ViewInterface $view, AppConfig $config): Response
    {
        $content = $view->render('home/index', [
            'appName' => $config->get('name')
        ]);

        return Response::html($content);
    }
}
