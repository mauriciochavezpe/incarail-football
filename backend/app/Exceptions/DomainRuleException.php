<?php

namespace App\Exceptions;

/** Regla de negocio violada (p. ej. desactivarse a sí mismo). */
class DomainRuleException extends ApiException
{
    public function __construct(string $message, string $code = 'domain_rule_violation')
    {
        parent::__construct($message, 409, $code);
    }
}
