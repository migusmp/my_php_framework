<?php

declare(strict_types=1);

use App\Security\Csrf;

function csrf_field(): void
{
    echo '<input type="hidden" name="_token" value="'
        . htmlspecialchars(Csrf::getToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}
