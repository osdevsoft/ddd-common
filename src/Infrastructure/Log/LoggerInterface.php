<?php

namespace Osds\DDDCommon\Infrastructure\Log;

interface LoggerInterface
{
    public function info($message);

    public function error($message);
}
