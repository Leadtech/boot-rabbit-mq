<?php
namespace Boot\RabbitMQ\Producer;

use Boot\RabbitMQ\Producer\Exception\PublishMessageException;

class BatchProducer extends AbstractProducer implements BatchProducerInterface
{
    /**
     * @param array $data
     * @return bool
     */
    public function publish(array $data)
    {
        try {

            // Batch publish. The message is not released until the publishSubmit method is executed!
            $this->channel->batch_basic_publish(
                $this->createMessage($data),
                $this->queueTemplate->getExchangeName(),
                $this->queueTemplate->getRoutingKey(),
                false,
                false,
                null
            );

        } catch(\Exception $e) {

            // Handle exception logging
            $this->handleException($e);

            throw new PublishMessageException($this, $data, $e);
        }
    }

    /**
     * @return bool
     */
    public function commit()
    {
        $this->channel->publish_batch();
    }

}