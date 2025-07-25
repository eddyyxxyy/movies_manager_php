<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ViewInterface;
use App\Contracts\SessionInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Contracts\CsrfTokenInterface;
use App\Services\UserService;

class AuthController
{
    /**
     * @param ViewInterface $view The view renderer service.
     * @param SessionInterface $session The session management service.
     * @param CsrfTokenInterface $csrfToken The CSRF token service.
     * @param UserService $userService The user business logic service.
     */
    public function __construct(
        private ViewInterface $view,
        private SessionInterface $session,
        private CsrfTokenInterface $csrfToken,
        private UserService $userService
    ) {
    }

    /**
     * Displays the user login form.
     * Generates a CSRF token for the form.
     *
     * @return Response The HTTP response containing the login form.
     */
    public function showLoginForm(): Response
    {
        $csrfToken = $this->csrfToken->generate();
        return Response::html($this->view->render('auth/login', [
            'appName' => 'Movies Manager',
            'csrf_token' => $csrfToken
        ]));
    }

    /**
     * Handles the user login form submission.
     * Authenticates the user and sets session data on success.
     *
     * @param Request $request The HTTP request containing login credentials.
     * @return Response The HTTP response (redirect or re-display form with errors).
     */
    public function login(Request $request): Response
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $errors = [];

        $csrfToken = $request->input('_csrf_token');
        if (!$this->csrfToken->validate($csrfToken ?? '')) {
            $errors['csrf'] = 'Invalid or expired CSRF token. Please try again.';
        }

        if (empty($errors)) {
            $user = $this->userService->authenticateUser($email, $password);

            if ($user) {
                $this->session->set('user_id', $user->id);
                $this->session->set('user_name', $user->fullName);
                return Response::redirect('/');
            }

            $errors['credentials'] = 'Invalid credentials.';
        }

        $csrfToken = $this->csrfToken->generate();
        return Response::html($this->view->render('auth/login', [
            'errors' => $errors,
            'appName' => 'Movies Manager',
            'csrf_token' => $csrfToken,
            'old_email' => $email
        ]));
    }

    /**
     * Logs the user out by destroying the session.
     *
     * @return Response A redirect to the login page.
     */
    public function logout(): Response
    {
        $this->session->destroy();
        return Response::redirect('/login');
    }
}