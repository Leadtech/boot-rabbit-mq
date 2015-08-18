<?php
namespace Boot\RabbitMQ\Consumer;

use Boot\RabbitMQ\Template\QueueTemplate;
use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    /**
     * @param AMQPMessage $message
     * @return mixed
     */
    public function handle(AMQPMessage $message);

    /**
     * @return void
     */
    public function listen();

    /**
     * @return QueueTemplate
     */
    public function getQueueTemplate();

}