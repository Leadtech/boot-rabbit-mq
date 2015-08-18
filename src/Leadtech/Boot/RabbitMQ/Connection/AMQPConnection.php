<?php
namespace Boot\RabbitMQ\Connection;

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class AMQPConnection
 * @package Boot\RabbitMQ\Connection
 */
class AMQPConnection extends AMQPStreamConnection
{
    /**
     * Should the connection be attempted during construction?
     *
     * @return bool
     */
    public function connectOnConstruct()
    {
        return false;
    }
}
