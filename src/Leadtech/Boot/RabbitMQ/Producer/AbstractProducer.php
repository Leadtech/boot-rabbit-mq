<?php
namespace Boot\RabbitMQ\Producer;

use Boot\RabbitMQ\Template\QueueTemplate;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractProducer
 * @package Boot\RabbitMQ\Producer
 */
abstract class AbstractProducer implements ProducerInterface
{
    /** @var  QueueTemplate */
    protected $queueTemplate;

    /** @var  AMQPChannel */
    protected $channel;

    /** @var LoggerInterface  */
    private $logger = null;

    /**
     * @param QueueTemplate $queueTemplate
     *
     * @return static|self
     */
    public function __construct(QueueTemplate $queueTemplate)
    {
        $this->queueTemplate = $queueTemplate;
        $this->channel = $queueTemplate->createChannel();
    }

    /**
     * Create message
     *
     * @param array $data
     * @return AMQPMessage
     */
    protected function createMessage(array $data)
    {
        // Delegate to queue strategy
        return $this->queueTemplate->getStrategy()->createMessage($this->queueTemplate, $data);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if(!$this->logger instanceof LoggerInterface) {
            $logger = new Logger(__CLASS__);
            $logger->pushHandler(new NullHandler);
        }

        return $this->logger;
    }

    /**
     * @param \Exception $e
     */
    protected function handleException(\Exception $e)
    {
        // Log error
        $this->getLogger()->error(
            strtr('Error occurred in {file} on line {line}. Message: {message}', [
                '{file}' => $e->getFile(),
                '{line}' => $e->getLine(),
                '{message}' => $e->getMessage()
            ])
        );
    }
}