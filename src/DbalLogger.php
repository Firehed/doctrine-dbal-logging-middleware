<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

interface DbalLogger extends QueryLogger
{
    public function connect(): void;
    public function disconnect(): void;
}
