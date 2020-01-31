<?php

namespace Osds\DDDCommon\Infrastructure\Messaging;

interface MessagingInterface
{
    public function connect($server, $port, $user, $password);

    public function publish($queue, $message);
}
