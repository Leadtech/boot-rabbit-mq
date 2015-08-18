<?php
namespace Boot\RabbitMQ\Producer;

interface ProducerInterface
{
    /**
     * @param array $data
     * @return bool
     */
    public function publish(array $data);
}