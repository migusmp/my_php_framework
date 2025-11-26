<?php

declare(strict_types=1);

use App\Http\RedirectResponse;
use App\Security\Csrf;

function csrf_field(): void
{
    echo '<input type="hidden" name="_token" value="'
        . htmlspecialchars(Csrf::getToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function redirect(string $url, int $status = 302): RedirectResponse
{
    return new RedirectResponse($url, $status);
}
