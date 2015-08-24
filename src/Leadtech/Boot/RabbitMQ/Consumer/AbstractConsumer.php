<?php
namespace Boot\RabbitMQ\Consumer;

use Boot\RabbitMQ\Consumer\Event\ConsumerErrorEvent;
use Boot\RabbitMQ\Consumer\Event\ConsumerSuccessEvent;
use Boot\RabbitMQ\Consumer\Event\ReceiveEvent;
use Boot\RabbitMQ\RabbitMQ;
use Boot\RabbitMQ\Template\QueueTemplate;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractConsumer
 * @package Boot\Leadtech\Consumer
 */
abstract class AbstractConsumer implements ConsumerInterface
{
    /** @var  string */
    protected $consumerName;

    /** @var  QueueTemplate */
    protected $queueTemplate;

    /** @var bool  */
    protected $noLocal = false;

    /** @var bool  */
    protected $noWaiting = false;

    /** @var LoggerInterface  */
    private $logger = null;

    /**
     * @param QueueTemplate $queueTemplate
     * @param string $consumerName
     */
    public function __construct(QueueTemplate $queueTemplate, $consumerName = '')
    {
        $this->consumerName = $consumerName;
        $this->queueTemplate = $queueTemplate;
    }

    /**
     * Implementation of a message handler.  Return true if the process is successful. This will trigger the ack signal if needed.
     * If the process has failed either return false or throw an exception.
     *
     *
     * @param AMQPMessage $message
     * @return bool
     */
    abstract public function handle(AMQPMessage $message);

    /**
     * @param AMQPMessage $message
     */
    public function __invoke(AMQPMessage $message)
    {
        // Unserialize content
        $message->body = $this->queueTemplate->getSerializer()->unserialize($message->body);

        // Intercept incoming message and dispatch receive event.
        $this->queueTemplate->dispatchEvent(RabbitMQ::ON_RECEIVE, new ReceiveEvent($this, $message));

        try {

            // Delegate message to handle method.
            // If the handle method returns false or throws an exception than
            $result = $this->handle($message);

        } catch (\Exception $e) {
            // Failed with errors
            $result = false;
        }

        // Handle result
        $result ? $this->success($message) : $this->failure($message);
    }

    /**
     * @return bool
     */
    public function connect()
    {
        // Connect to server
        $connection = $this->queueTemplate->getConnection();
        if (!$connection->isConnected()) {
            $connection->reconnect();
        }

        // Declare queue
        $this->queueTemplate->declareQueue();
        $this->queueTemplate->declareQualityOfService();

        // Always return true for the time being. May be improved later and returning true will ensure we won't have to
        // change the implementation later on.
        return true;
    }

    /**
     * @return void
     */
    public function listen()
    {
        // Declare template
        $queueTemplate = $this->queueTemplate;

        // Create or reuse existing channel
        $channel = $this->channel();

        /**
         * indicate interest in consuming messages from a particular queue. When they do
         * so, we say that they register a consumer or, simply put, subscribe to a queue.
         * Each consumer (subscription) has an identifier called a consumer tag
         */
        $channel->basic_consume(
            $queueTemplate->getQueueName(),                   #queue
            $this->consumerName,                              #consumer tag - Identifier for the consumer, valid within the current channel. just string
            $this->noLocal,                                   #no local - TRUE: the server will not send messages to the connection that published them
            !$queueTemplate->doAckManually(),                 #no ack, false - ack turned on, true - off.  send a proper acknowledgment from the worker, once we're done with a task
            $this->queueTemplate->isExclusive(),              #exclusive - queues may only be accessed by the current connection
            $this->noWaiting,                                 #no wait - TRUE: the server will not respond to the method. The client should not wait for a reply method
            $this                                             #callback
        );
    }

    /**
     * Create or reuse AMQP channel.
     *
     * @return AMQPChannel
     */
    public function channel()
    {
        // Delegate call to queue template. Method added for the sake of simplicity.
        // We don't want to get the queue template from the consumer. This would create an extra dependency that we do not need.
        return $this->queueTemplate->channel();
    }

    /**
     * @param AMQPMessage $message
     */
    protected function success(AMQPMessage $message)
    {
        // Check if the current strategy requires manual acknowledgement
        if ($this->queueTemplate->doAckManually()) {

            // Send ack
            $this->ack($message);
        }

        // Dispatch success event
        $this->queueTemplate->dispatchEvent(RabbitMQ::ON_CONSUMER_SUCCESS, new ConsumerSuccessEvent($this, $message));
    }

    /**
     * @param AMQPMessage $message
     * @param bool        $multiple
     */
    protected function failure(AMQPMessage $message, $multiple = false)
    {
        // Check if the current strategy requires manual acknowledgement
        if ($this->queueTemplate->doAckManually()) {

            // Reject message
            $this->nack($message);
        }

        // Dispatch error event
        $this->queueTemplate->dispatchEvent(RabbitMQ::ON_CONSUMER_ERROR, new ConsumerErrorEvent($this, $message));
    }

    /**
     * If a consumer dies without sending an acknowledgement the AMQP broker
     * will redeliver it to another consumer or, if none are available at the
     * time, the broker will wait until at least one consumer is registered
     * for the same queue before attempting redelivery
     *
     * @param AMQPMessage $message
     * @param bool        $multiple
     */
    protected function ack(AMQPMessage $message, $multiple = false)
    {
        /** @var AMQPChannel  $channel */
        $channel =  $message->delivery_info['channel'];
        $deliveryId = $message->delivery_info['delivery_tag'];
        $channel->basic_ack($deliveryId, $multiple);
    }

    /**
     * Rejects one or several received messages
     *
     * @param AMQPMessage $message
     * @param bool|false $multiple
     */
    protected function nack(AMQPMessage $message, $multiple = false)
    {
        /** @var AMQPChannel  $channel */
        $channel =  $message->delivery_info['channel'];
        $deliveryId = $message->delivery_info['delivery_tag'];
        $channel->basic_nack($deliveryId, $multiple, true);
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getConsumerName()
    {
        return $this->consumerName;
    }

    /**
     * @codeCoverageIgnore
     * @return QueueTemplate
     */
    public function getQueueTemplate()
    {
        return $this->queueTemplate;
    }

    /**
     * @codeCoverageIgnore
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if(!$this->logger instanceof LoggerInterface) {
            $this->logger = new Logger(__CLASS__);
            $this->logger->pushHandler(new NullHandler);
        }

        return $this->logger;
    }
    /**
     * @codeCoverageIgnore
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @codeCoverageIgnore
     * @return boolean
     */
    public function isNoLocal()
    {
        return $this->noLocal;
    }

    /**
     * @codeCoverageIgnore
     * @param boolean $noLocal
     */
    public function setNoLocal($noLocal)
    {
        $this->noLocal = $noLocal;
    }

    /**
     * @codeCoverageIgnore
     * @return boolean
     */
    public function isNoWaiting()
    {
        return $this->noWaiting;
    }

    /**
     * @codeCoverageIgnore
     * @param boolean $noWaiting
     */
    public function setNoWaiting($noWaiting)
    {
        $this->noWaiting = $noWaiting;
    }

    /**
     * @return void
     */
    public function wait()
    {
        $this->queueTemplate->channel()->wait();
    }

    /**
     * @return bool
     */
    public function isBusy()
    {
        return count($this->queueTemplate->channel()->callbacks) > 0;
    }
}
