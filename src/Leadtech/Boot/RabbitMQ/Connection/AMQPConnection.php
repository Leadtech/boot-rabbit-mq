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
     * Prevent the library from auto connecting in the constructor.
     * When the connection is created within a service container and something goes wrong there is nothing we can do catch the exception
     * unless the whole application is wrapped within a try - catch.
     *
     * @codeCoverageIgnore  There is no need to test this method this only prevents the library from auto connecting in the constructor.
     *
     * @return bool
     */
    public function connectOnConstruct()
    {
        return false;
    }
}
