<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

enum AuthState: string
{
    case LoggedIn = 'loggedIn';
    case LoggedOut = 'loggedOut';
}
