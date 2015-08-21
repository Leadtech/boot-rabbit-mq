<?php
namespace Boot\RabbitMQ\Serializer;

/**
 * Class SecureJsonSerializer
 * @package Boot\RabbitMQ\Serializer
 */
class SecureJsonSerializer extends AbstractSecureSerializer
{

    /**
     * @param array $data
     * @return string
     */
    public function serialize(array $data)
    {
        return $this->encrypt(json_encode($data));
    }

    /**
     * @param $data
     * @return array
     */
    public function unserialize($data)
    {
        return json_decode($this->decrypt($data), true);
    }

}