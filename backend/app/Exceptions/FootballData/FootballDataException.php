<?php

namespace App\Exceptions\FootballData;

use App\Exceptions\ApiException;

/** Cualquier problema al hablar con football-data.org. */
abstract class FootballDataException extends ApiException {}
