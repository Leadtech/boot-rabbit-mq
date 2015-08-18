<?php
namespace Boot\RabbitMQ\Producer;

use Boot\RabbitMQ\Producer\Exception\PublishMessageException;

/**
 * Class Producer
 * @package Boot\RabbitMQ\Producer
 */
class Producer extends AbstractProducer
{
    /**
     * @param array $data
     * @return bool
     */
    public function publish(array $data)
    {
        try {

            // Do publish message
            $this->doPublish($data);

        } catch(\Exception $e) {

            // Handle exception logging
            $this->handleException($e);

            throw new PublishMessageException($this, $data, $e);
        }

    }

    /**
     * @param array $data
     */
    protected function doPublish(array $data)
    {
        $this->channel->basic_publish(
            $this->createMessage($data),
            $this->queueTemplate->getExchangeName(),
            $this->queueTemplate->getRoutingKey(),
            false,
            false,
            null
        );
    }

}