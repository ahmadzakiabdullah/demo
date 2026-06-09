<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;

class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
            $handler->pushProcessor(new PsrLogMessageProcessor());
            $handler->pushProcessor(new UidProcessor());
        }
    }
}
