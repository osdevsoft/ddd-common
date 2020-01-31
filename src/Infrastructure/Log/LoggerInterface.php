<?php

namespace Osds\Api\Infrastructure\Log;

interface LoggerInterface
{
    public function info($message);

    public function error($message);
}
