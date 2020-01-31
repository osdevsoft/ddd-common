<?php

namespace Osds\Api\Infrastructure\Log;

class PSRLogger implements LoggerInterface
{

    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function info($message)
    {
        $this->logger->info($message);
    }

    public function error($message)
    {
        $this->logger->error($message);
    }
}
