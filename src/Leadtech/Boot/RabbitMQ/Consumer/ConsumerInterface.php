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
     * @return bool
     */
    public function connect();

    /**
     * Wait for incoming messages
     *
     * @return void
     */
    public function wait();

    /**
     * @return bool
     */
    public function isBusy();

    /**
     * Get queue template
     *
     * @return QueueTemplate
     */
    public function getQueueTemplate();
}
