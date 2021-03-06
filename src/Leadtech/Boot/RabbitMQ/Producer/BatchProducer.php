<?php
namespace Boot\RabbitMQ\Producer;

use Boot\RabbitMQ\Producer\Exception\PublishMessageException;

/**
 * Class BatchProducer
 * @package Boot\RabbitMQ\Producer
 */
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
            $this->getChannel()->batch_basic_publish(
                $this->queueTemplate->createMessage($data),
                $this->queueTemplate->getExchangeName(),
                $this->queueTemplate->getQueueName(),
                false,
                false,
                null
            );

        } catch (\Exception $e) {

            // Handle exception logging
            $this->handleException($e);

            throw new PublishMessageException($this, $data, $e);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function commit()
    {
        $this->getChannel()->publish_batch();
    }
}
