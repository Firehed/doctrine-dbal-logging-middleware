<?php

declare(strict_types=1);

namespace Your\Library;

/**
 * This file exists to:
 * a) Demonstrate the default namespace setup
 * b) Provide something for composer-require-checker to find when CI runs
 * against the actual template repository.
 *
 * You won't want to keep it!
 */
class Example
{
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
