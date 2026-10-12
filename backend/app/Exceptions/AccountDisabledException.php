<?php

namespace App\Exceptions;

class AccountDisabledException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Tu usuario está desactivado. Contacta a un administrador.', 403, 'account_disabled');
    }
}
