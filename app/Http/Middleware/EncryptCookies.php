<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // i18next writes the interface language from the browser, so this one
        // is never encrypted. Without the exemption Laravel fails to decrypt
        // it and drops it, and SetLocale never sees the visitor's choice.
        'i18next',
    ];
}
