<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * World Flora Online was not asked, because it is already known to be down and
 * nothing cached could answer instead.
 *
 * This is the breaker doing its job, not a new fault, so it is deliberately
 * distinct from the exception a real failed request throws: the request that
 * actually failed is worth reporting, and the hundred that follow it in the
 * same cooldown are worth only an error response.
 */
class WfoUnavailableException extends RuntimeException {}
