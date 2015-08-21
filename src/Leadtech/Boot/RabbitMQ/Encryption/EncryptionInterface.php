<?php
namespace Boot\RabbitMQ\Encryption;

interface EncryptionInterface
{
    /**
     * Encrypt a given string.
     *
     * @param $string
     * @return string
     */
    public function encrypt($string);

    /**
     * Decrypt the encrypted string.
     *
     * @param $encrypted
     * @return string
     */
    public function decrypt($encrypted);
}