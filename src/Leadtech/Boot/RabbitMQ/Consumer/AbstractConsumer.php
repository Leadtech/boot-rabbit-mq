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

    /** @var LoggerInterface  */
    private $logger = null;

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

        } catch(\Exception $e){
            // Failed with errors
            $result = false;
        }

        // Handle result
        $result ? $this->success($message) : $this->failure($message);
    }

    /**
     * @param QueueTemplate $queueTemplate
     * @param string        $consumerName
     *
     * @return static|self
     */
    public static function createConsumer(QueueTemplate $queueTemplate, $consumerName = '')
    {
        // Create or reuse existing channel
        $channel = $queueTemplate->getConnection()->channel(
            $queueTemplate->getChannelId() ?: $queueTemplate->getQueueName()
        );

        $consumer = new static();
        $consumer->consumerName = $consumerName;
        $consumer->queueTemplate = $queueTemplate;


        /**
         * indicate interest in consuming messages from a particular queue. When they do
         * so, we say that they register a consumer or, simply put, subscribe to a queue.
         * Each consumer (subscription) has an identifier called a consumer tag
         */
        $channel->basic_consume(
            $queueTemplate->getQueueName(),                   #queue
            $consumerName,                                    #consumer tag - Identifier for the consumer, valid within the current channel. just string
            false,                                            #no local - TRUE: the server will not send messages to the connection that published them
            !$queueTemplate->getStrategy()->doAckManually(),  #no ack, false - ack turned on, true - off.  send a proper acknowledgment from the worker, once we're done with a task
            false,                                            #exclusive - queues may only be accessed by the current connection
            false,                                            #no wait - TRUE: the server will not respond to the method. The client should not wait for a reply method
            $consumer                                         #callback
        );

        return $consumer;
    }

    /**
     * @param AMQPMessage $message
     */
    protected function success(AMQPMessage $message)
    {
        // Check if the current strategy requires manual acknowledgement
        if($this->queueTemplate->getStrategy()->doAckManually()) {

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
        if($this->queueTemplate->getStrategy()->doAckManually()) {

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
     * @todo documentation states that if a consumer failes (crashes I assume) that the message in this setup is rejected and redistributed.
     * @todo I want to force the channel to continue and requeue the item. This method should work, but I need to check if this works properly.
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

}