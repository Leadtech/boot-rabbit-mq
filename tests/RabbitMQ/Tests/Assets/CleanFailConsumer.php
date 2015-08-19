<?php
namespace Boot\RabbitMQ\Tests\Assets;

use Boot\RabbitMQ\Consumer\AbstractConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class CleanFailConsumer extends AbstractConsumer
{
    /**
     * Implementation of a message handler.  Return true if the process is successful. This will trigger the ack signal if needed.
     * If the process has failed either return false or throw an exception.
     *
     *
     * @param AMQPMessage $message
     * @return bool
     */
    public function handle(AMQPMessage $message)
    {
        return false; // failed
    }

}