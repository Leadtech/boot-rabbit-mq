<?php
namespace Boot\RabbitMQ\Producer;

/**
 * Interface BatchProducerInterface
 * @package Boot\RabbitMQ\Producer
 */
interface BatchProducerInterface extends ProducerInterface
{
    /**
     * @return bool
     */
    public function commit();
}
