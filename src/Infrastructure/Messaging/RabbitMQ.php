<?php

namespace Osds\Api\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ implements MessagingInterface
{

    private $connection;
    private $channel;


    public function __construct(
        Array $configuration
    ) {
        $this->connect(
            $configuration['server'],
            $configuration['port'],
            $configuration['user'],
            $configuration['password']
        );
    }


    public function connect($server, $port, $user, $password)
    {
//        $this->connection = new AMQPStreamConnection($server, $port, $user, $password);
//        $this->channel = $this->connection->channel();
    }

    public function publish($queue, $message)
    {
//        $this->channel->exchange_declare($exchange, 'fanout', false, false, false);
        $this->channel->queue_declare($queue, false, true, false, false);

        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, '', $queue);

//        $this->channel->close();
//        $this->connection->close();
    }
}
