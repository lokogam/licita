<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

/**
 * Thrown when the container cannot build a requested type.
 */
final class ContainerException extends RuntimeException
{
}
