<?php
namespace Boot\RabbitMQ\Encryption;

/**
 * Class RijnDael256Encryption
 * @package Boot\RabbitMQ\Encryption
 */
class RijnDael256Encryption implements EncryptionInterface
{
    /** @var string  */
    private $secretKey;

    /**
     * @param string $secretKey
     */
    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Encrypt a given string.
     *
     * @param $string
     * @return string
     */
    public function encrypt($string)
    {
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->secretKey, $string, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    /**
     * Decrypt the encrypted string.
     *
     * @param $encrypted
     * @return string
     */
    public function decrypt($encrypted)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->secretKey, base64_decode($encrypted), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

}