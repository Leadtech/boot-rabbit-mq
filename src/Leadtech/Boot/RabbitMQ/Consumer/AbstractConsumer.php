<?php
namespace Boot\RabbitMQ\Consumer;

use Boot\RabbitMQ\Consumer\Event\ConsumerErrorEvent;
use Boot\RabbitMQ\Consumer\Event\ConsumerSuccessEvent;
use Boot\RabbitMQ\Consumer\Event\ReceiveEvent;
use Boot\RabbitMQ\RabbitMQ;
use Boot\RabbitMQ\Template\QueueTemplate;
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
        $this->queueTemplate->dispatchEvent(RabbitMQ::ON_RECEIVE_EVENT, new ReceiveEvent($this, $message));

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
     * @return void
     */
    public function listen()
    {
        // Declare template
        $queueTemplate = $this->queueTemplate;

        // Create or reuse existing channel
        $channel = $queueTemplate->createChannel();

        /**
         * indicate interest in consuming messages from a particular queue. When they do
         * so, we say that they register a consumer or, simply put, subscribe to a queue.
         * Each consumer (subscription) has an identifier called a consumer tag
         */
        $channel->basic_consume(
            $queueTemplate->getQueueName(),                   #queue
            $this->consumerName,                              #consumer tag - Identifier for the consumer, valid within the current channel. just string
            $this->noLocal,                                   #no local - TRUE: the server will not send messages to the connection that published them
            !$queueTemplate->getStrategy()->doAckManually(),  #no ack, false - ack turned on, true - off.  send a proper acknowledgment from the worker, once we're done with a task
            $this->queueTemplate->isExclusive(),              #exclusive - queues may only be accessed by the current connection
            $this->noWaiting,                                 #no wait - TRUE: the server will not respond to the method. The client should not wait for a reply method
            $this                                             #callback
        );
    }


    /**
     * @param AMQPMessage $message
     */
    protected function success(AMQPMessage $message)
    {
        // Check if the current strategy requires manual acknowledgement
        if ($this->queueTemplate->getStrategy()->doAckManually()) {

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
        if ($this->queueTemplate->getStrategy()->doAckManually()) {

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
     * @return string
     */
    public function getConsumerName()
    {
        return $this->consumerName;
    }

    /**
     * @return QueueTemplate
     */
    public function getQueueTemplate()
    {
        return $this->queueTemplate;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return boolean
     */
    public function isNoLocal()
    {
        return $this->noLocal;
    }

    /**
     * @param boolean $noLocal
     */
    public function setNoLocal($noLocal)
    {
        $this->noLocal = $noLocal;
    }

    /**
     * @return boolean
     */
    public function isNoWaiting()
    {
        return $this->noWaiting;
    }

    /**
     * @param boolean $noWaiting
     */
    public function setNoWaiting($noWaiting)
    {
        $this->noWaiting = $noWaiting;
    }
}
