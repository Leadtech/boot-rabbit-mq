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

            try {

                // Produce message(s)
                $this->produce($input, $output);

            } catch(\Exception $e) {

                // Handle exception
                $this->handleException($e, $output);

            }

            // Close connection
            $queueTemplate->close();
        }

        return $this->resultState;
    }


    /**
     * Connect to RabbitMQ
     *
     * @return bool
     */
    public function connect()
    {
        // Connect producer
        $this->producer->connect();

        return true;
    }

    /**
     * @param \Exception $e
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function handleException(\Exception $e, OutputInterface $output)
    {
        // Set result state
        $this->resultState = self::FAILED_EXIT_CODE;

        // Render error message
        $message = strtr('Error occurred: {message} on line {line} in file {file}.', [
            '{message}' => $e->getMessage(),
            '{line}'    => $e->getLine(),
            '{file}'    => $e->getFile()
        ]);

        // Output error
        $output->writeln($message);
    }
}
