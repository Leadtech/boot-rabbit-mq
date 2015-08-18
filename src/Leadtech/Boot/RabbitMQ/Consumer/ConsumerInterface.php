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
     * @return QueueTemplate
     */
    public function getQueueTemplate();

    /**
     * @param QueueTemplate $queueTemplate
     *
     * @return self
     */
    public static function createConsumer(QueueTemplate $queueTemplate);
}