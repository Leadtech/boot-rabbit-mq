<?php
namespace Boot\RabbitMQ\Template;

use Boot\RabbitMQ\Serializer\JsonSerializer;
use Boot\RabbitMQ\Serializer\SerializerInterface;
use Boot\RabbitMQ\Strategy\QueueStrategy;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection as AbstractAMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class QueueTemplate
 * @package Boot\RabbitMQ\Template
 */
class QueueTemplate
{
    /** @var  AbstractAMQPConnection */
    protected $connection;

    /** @var  QueueStrategy */
    protected $strategy;

    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /** @var AMQPChannel */
    protected $channel = null;

    /** @var  string */
    protected $queueName;

    /** @var  SerializerInterface */
    protected $serializer;

    /** @var string|null */
    protected $channelId = null;

    /** @var string  */
    protected $exchangeName = '';

    /** @var bool  */
    protected $passive = false;

    /** @var bool  */
    protected $exclusive = false;

    /**
     * @param AbstractAMQPConnection   $connection
     * @param QueueStrategy            $strategy
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(AbstractAMQPConnection $connection, QueueStrategy $strategy, $eventDispatcher = null)
    {
        $this->connection = $connection;
        $this->strategy = $strategy;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param $eventName
     * @param Event $event
     *
     * @return bool
     */
    public function dispatchEvent($eventName, Event $event)
    {
        // Dispatch event if the event dispatcher is set
        if ($this->eventDispatcher instanceof EventDispatcher) {
            $this->eventDispatcher->dispatch($eventName, $event);

            return true;
        }

        // Event not dispatched
        return false;
    }

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    public function createChannel()
    {
        if ($this->channel === null) {
            $this->channel = $this->getConnection()->channel(
                $this->getChannelId() ?: $this->getQueueName()
            );
        }

        return $this->channel;
    }

    /**
     * Calls are delegated to the strategy. The strategy is hidden for simplicity.
     *
     * @return void
     */
    public function declareQueue()
    {
        $this->strategy->declareQueue($this);
    }

    /**
     * @return void
     */
    public function declareQualityOfService()
    {
        // Calls are delegated to the strategy. The strategy is hidden for simplicity.
        $this->strategy->declareQualityOfService($this);
    }

    /**
     * @param array $data
     *
     * @return AMQPMessage
     */
    public function createMessage(array $data)
    {
        // Calls are delegated to the strategy. The strategy is hidden for simplicity.
        return $this->strategy->createMessage($this, $data);
    }

    /**
     * @return bool
     */
    public function doAckManually()
    {
        // Calls are delegated to the strategy. The strategy is hidden for simplicity.
        return $this->strategy->doAckManually();
    }


    /**
     * @return AbstractAMQPConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return QueueStrategy
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        if (!$this->serializer instanceof SerializerInterface) {
            $this->serializer = new JsonSerializer;
        }

        return $this->serializer;
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * @return null|string
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param null|string $channelId
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;
    }


    /**
     * @return string
     */
    public function getExchangeName()
    {
        return $this->exchangeName;
    }

    /**
     * @param string $exchangeName
     */
    public function setExchangeName($exchangeName)
    {
        $this->exchangeName = $exchangeName;
    }

    /**
     * @return boolean
     */
    public function isPassive()
    {
        return $this->passive;
    }

    /**
     * @param boolean $passive
     */
    public function setPassive($passive)
    {
        $this->passive = $passive;
    }

    /**
     * @return boolean
     */
    public function isExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param boolean $exclusive
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = $exclusive;
    }
}
