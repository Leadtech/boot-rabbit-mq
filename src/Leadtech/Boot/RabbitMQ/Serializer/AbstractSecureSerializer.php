<?php
namespace Boot\RabbitMQ\Serializer;

use Boot\RabbitMQ\Encryption\EncryptionInterface;

/**
 * Class AbstractSecureSerializer
 * @package Boot\RabbitMQ\Serializer
 */
abstract class AbstractSecureSerializer implements SerializerInterface
{
    /** @var  EncryptionInterface */
    protected $encryption = null;

    /**
     * @param EncryptionInterface $encryption
     */
    public function __construct(EncryptionInterface $encryption = null)
    {
        $this->encryption = $encryption;
    }

    /**
     * Serialize data array.
     *
     * @param array $data
     * @return string
     */
    abstract public function serialize(array $data);

    /**
     * Unserialize the serialized string.
     *
     * @param $serialized
     * @return array
     */
    abstract public function unserialize($serialized);

    /**
     * @param string $serialized
     * @return string
     */
    protected function encrypt($serialized)
    {
        if ($this->isEncryptionEnabled()) {
            return $this->encryption->encrypt($serialized);
        }

        return $serialized;
    }

    /**
     * @param string $encrypted
     * @return string
     */
    protected function decrypt($encrypted)
    {
        if ($this->isEncryptionEnabled()) {
            return $this->encryption->decrypt($encrypted);
        }

        return $encrypted;
    }

    /**
     * Check if encryption is enabled.
     *
     * @return bool
     */
    public function isEncryptionEnabled()
    {
        return $this->encryption instanceof EncryptionInterface;
    }

    /**
     * @return EncryptionInterface
     */
    public function getEncryption()
    {
        return $this->encryption;
    }

    /**
     * @param EncryptionInterface $encryption
     */
    public function setEncryption(EncryptionInterface $encryption)
    {
        $this->encryption = $encryption;
    }

}