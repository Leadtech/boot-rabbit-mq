<?php
namespace Boot\RabbitMQ\Consumer;

use Boot\RabbitMQ\Template\QueueTemplate;
use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    /**
     * Handle incoming message
     *
     * @param AMQPMessage $message
     * @return mixed
     */
    public function handle(AMQPMessage $message);

    /**
     * Start listening to the message queue
     *
     * @return void
     */
    public function listen();

    /**
     * Get queue template
     *
     * @return QueueTemplate
     */
    public function getQueueTemplate();
}
