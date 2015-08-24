<?php
namespace Boot\RabbitMQ\Consumer\Event;

use Boot\RabbitMQ\Consumer\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AbstractConsumerEvent
 * @package Boot\RabbitMQ\Consumer\Event
 */
abstract class AbstractConsumerEvent extends Event
{
    /** @var ConsumerInterface  */
    protected $consumer;

    /** @var  AMQPMessage */
    protected $message;

    /**
     * @param ConsumerInterface $consumer
     * @param AMQPMessage       $message
     */
    public function __construct(ConsumerInterface $consumer, AMQPMessage $message)
    {
        $this->consumer = $consumer;
        $this->message = $message;
    }

    /**
     * @codeCoverageIgnore
     * @return ConsumerInterface
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @codeCoverageIgnore
     * @return AMQPMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

}
