<?php
namespace Boot\RabbitMQ\Serializer;

class JsonSerializer implements SerializerInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function serialize(array $data)
    {
        return json_encode($data);
    }

    /**
     * @param $data
     * @return array
     */
    public function unserialize($data)
    {
        return json_decode($data, true);
    }

}