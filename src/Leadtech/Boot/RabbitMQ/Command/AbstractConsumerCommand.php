<?php
namespace Boot\RabbitMQ\Command;

use Boot\RabbitMQ\Consumer\ConsumerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractWorkerCommand
 * @package Search\QueueConsumer
 */
abstract class AbstractConsumerCommand extends AbstractAMQPCommand
{
    const SUCCESS_EXIT_CODE = 0;
    const FAILED_EXIT_CODE = 1;

    /** @var  ConsumerInterface   */
    protected $consumer;

    /** @var  int   Interval in seconds */
    protected $interval = 1;

    /** @var int  */
    protected $resultState = self::SUCCESS_EXIT_CODE;

    /**
     * @param string            $name            The command name e.g. some:command
     * @param ConsumerInterface $consumer        Instance of Consumer. The Consumer is configured to receive the incoming messages.
     * @param LoggerInterface   $logger          Instance of LoggerInterface
     * @param int               $interval        Interval in seconds. If a message is denied and there is no interval than the server and client may continuously send requests back and forth.
     */
    public function __construct($name, ConsumerInterface $consumer, LoggerInterface $logger = null, $interval = 0)
    {
        $this->consumer = $consumer;
        $this->interval = $interval;
        parent::__construct($name);
    }

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
        if($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE && !defined('AMQP_DEBUG')) {
            define('AMQP_DEBUG', true);
        }

        // Connect to server
        if($this->connect()) {

            // Create channel
            $queueTemplate = $this->consumer->getQueueTemplate();
            $channel = $queueTemplate->createChannel();

            // Prepare process
            $this->prepareProcess();

            // Iterate callbacks.
            while (count($channel->callbacks)) {

                // Execute pre process
                $this->preProcess();

                // Wait for message. Note that incoming messages are delegated to the injected consumer instance.
                // For more information see:
                //  - Boot\RabbitMQ\Consumer\AbstractConsumer::__invoke(AMQPMessage $message)
                //  - Boot\RabbitMQ\Consumer\ConsumerInterface::handle(AMQPMessage $message)
                $channel->wait();

                // Execute post process
                $this->postProcess();

            }

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
        $queueTemplate = $this->consumer->getQueueTemplate();

        // Connect to server
        $connection = $queueTemplate->getConnection();
        if (!$connection->isConnected()) {
            $connection->reconnect();
        }

        // Declare queue
        $queueTemplate->getStrategy()->declareQueue($queueTemplate);
        $queueTemplate->getStrategy()->declareQualityOfService($queueTemplate);

        return true;
    }

    /**
     * Prepare process.
     */
    protected function prepareProcess()
    {
        // Listen to incoming messages.
        $this->consumer->listen();
    }

    /**
     * Execute post process.
     */
    protected function postProcess()
    {
        // Sleep for a specified amount of seconds after all messages are processed.
        sleep($this->interval);
    }

    /**
     * Execute pre press.
     */
    protected function preProcess()
    {
        // By default nothing happens, this method is just here to extended the functionality if needed.
    }

}