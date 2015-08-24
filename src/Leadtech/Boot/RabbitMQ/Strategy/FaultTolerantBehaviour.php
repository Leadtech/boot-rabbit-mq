<?php
namespace Boot\RabbitMQ\Strategy;

use Boot\RabbitMQ\RabbitMQ;
use Boot\RabbitMQ\Template\QueueTemplate;
use PhpAmqpLib\Message\AMQPMessage;

class FaultTolerantBehaviour extends QueueStrategy
{
    const ACKNOWLEDGE_MANUALLY = true;

    /**
     * @param QueueTemplate $queueTemplate
     */
    public function declareQueue(QueueTemplate $queueTemplate)
    {
        // Create or reuse existing channel
        $channel = $queueTemplate->channel();

        // Declare queue
        $channel->queue_declare(
            $queueTemplate->getQueueName(),  #queue - Queue names may be up to 255 bytes of UTF-8 characters
            $queueTemplate->isPassive(),     #passive - can use this to check whether an exchange exists without modifying the server state
            true,                            #durable, make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
            $queueTemplate->isExclusive(),   #exclusive - used by only one connection and the queue will be deleted when that connection closes
            false                            #auto delete - queue is deleted when last consumer unsubscribes
        );
    }

    /**
     * @param QueueTemplate $queueTemplate
     */
    public function declareQualityOfService(QueueTemplate $queueTemplate)
    {
        // Create or reuse existing channel. The channel ID should be set, use queue name if the channel has no id.
        // We don't want the channel to be recreated over and over.
        $channel = $queueTemplate->channel();

        /*
         * don't dispatch a new message to a worker until it has processed and
         * acknowledged the previous one. Instead, it will dispatch it to the
         * next worker that is not still busy.
         */
        $channel->basic_qos(
            null,   #prefetch size - prefetch window size in octets, null meaning "no specific limit"
            1,      #prefetch count - prefetch window in terms of whole messages
            true    #global - global=null to mean that the QoS settings should apply per-consumer, global=true to mean that the QoS settings should apply per-channel
        );
    }

    /**
     * @param QueueTemplate $queueTemplate
     * @param array $data
     *
     * @return AMQPMessage
     */
    public function createMessage(QueueTemplate $queueTemplate, array $data)
    {
        return new AMQPMessage(
            $queueTemplate->getSerializer()->serialize($data),
            [
                'delivery_mode' => RabbitMQ::DELIVERY_MODE_PERSISTENT  // make message persistent, so it is not lost if server crashes or quits, channel must be durable
            ]
        );
    }

    /**
     * @return bool
     */
    public function doAckManually()
    {
        return static::ACKNOWLEDGE_MANUALLY;
    }
}
