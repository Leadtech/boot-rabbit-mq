<?php
namespace Boot\RabbitMQ\Producer\Exception;

use Boot\RabbitMQ\Producer\ProducerInterface;

class PublishMessageException extends \RuntimeException
{
    /** @var  ProducerInterface */
    protected $producer;

    /** @var array */
    protected $data;

    /**
     * @param \Exception $previous
     */
    public function __construct(ProducerInterface $producer, array $data, \Exception $previous = null)
    {
        $this->producer = $producer;
        $this->data = $data;

        parent::__construct(
            'The producer is unable to publish the given message.',
            0,
            $previous
        );
    }

    /**
     * @return ProducerInterface
     */
    public function getProducer()
    {
        return $this->producer;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
