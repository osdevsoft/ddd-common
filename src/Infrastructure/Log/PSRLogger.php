<?php

namespace Osds\DDDCommon\Infrastructure\Log;

class PSRLogger implements LoggerInterface
{

    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function info($message, $context = [])
    {
        $this->logger->info($message, $context);
    }

    public function error($message, $context = [])
    {
        $this->logger->error($message, $context);
    }
}
