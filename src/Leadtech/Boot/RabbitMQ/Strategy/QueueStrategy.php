<?php
namespace Boot\RabbitMQ\Strategy;

use Boot\RabbitMQ\Template\QueueTemplate;
use PhpAmqpLib\Message\AMQPMessage;

abstract class QueueStrategy
{
    /**
     * @param QueueTemplate $queueTemplate
     */
    abstract public function declareQueue(QueueTemplate $queueTemplate);

    /**
     * @param QueueTemplate $queueTemplate
     */
    abstract public function declareQualityOfService(QueueTemplate $queueTemplate);

    /**
     * @param QueueTemplate $queueTemplate
     * @param array $data
     *
     * @return AMQPMessage
     */
    abstract public function createMessage(QueueTemplate $queueTemplate, array $data);

    /**
     * @return bool
     */
    abstract public function doAckManually();
}