<?php
namespace Boot\RabbitMQ\Command;

use Boot\RabbitMQ\Consumer\ConsumerInterface;
use Boot\RabbitMQ\Producer\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractWorkerCommand
 * @package Search\QueueConsumer
 */
abstract class AbstractProducerCommand extends AbstractAMQPCommand
{
    const SUCCESS_EXIT_CODE = 0;
    const FAILED_EXIT_CODE = 1;

    /** @var  ProducerInterface   */
    protected $producer;

    /** @var int  */
    protected $resultState = self::SUCCESS_EXIT_CODE;

    /**
     * @param string            $name
     * @param ProducerInterface $producer
     * @param LoggerInterface   $logger
     */
    public function __construct($name, ProducerInterface $producer, LoggerInterface $logger = null)
    {
        $this->producer = $producer;
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    abstract protected function produce(InputInterface $input, OutputInterface $output);

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Show verbose info
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE && !defined('AMQP_DEBUG')) {
            define('AMQP_DEBUG', true);
        }

        // Connect to server
        if ($this->connect()) {

            // Create channel
            $queueTemplate = $this->producer->getQueueTemplate();
            $channel = $queueTemplate->createChannel();

            // Produce message(s)
            $this->produce($input, $output);

            // Close channel
            $channel->close();

            // Close connection
            $queueTemplate->getConnection()->close();
        }

        return $this->resultState;
    }


    /**
     * Connect to RabbitMQ
     */
    public function connect()
    {
        // Declare queue
        $queueTemplate = $this->producer->getQueueTemplate();

        // Connect to server
        $connection = $queueTemplate->getConnection();
        if (!$connection->isConnected()) {
            $connection->reconnect();
        }

        // Declare queue
        $queueTemplate->declareQueue();

        return true;
    }
}
