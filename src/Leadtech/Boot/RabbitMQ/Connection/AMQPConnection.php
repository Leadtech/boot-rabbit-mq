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
     * Disable auto connecting in the constructor. This is too much responsibility for the constructor.
     * When the connection is created within a service container and something goes wrong there is nothing we can do catch the exception
     * unless the whole application is wrapped within a try - catch.
     *
     * @return bool
     */
    public function connectOnConstruct()
    {
        return false;
    }
}
