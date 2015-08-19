<?php
namespace Boot\RabbitMQ\Serializer;

interface SerializerInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function serialize(array $data);

    /**
     * @param $data
     * @return array
     */
    public function unserialize($data);
}
