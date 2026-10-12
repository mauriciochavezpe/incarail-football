<?php

namespace App\Exceptions;

class ImportNotAllowedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Esta liga todavía no fue importada y tu rol no tiene permiso para importarla.',
            403,
            'import_not_allowed',
        );
    }
}
