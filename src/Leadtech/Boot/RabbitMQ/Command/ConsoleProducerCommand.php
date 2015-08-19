<?php
namespace Boot\RabbitMQ\Command;

use Boot\RabbitMQ\Consumer\ConsumerInterface;
use Boot\RabbitMQ\Producer\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractWorkerCommand
 * @package Search\QueueConsumer
 */
class ConsoleProducerCommand extends AbstractProducerCommand
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setDescription('Produce messages from console.')
            ->addArgument('message',       InputArgument::REQUIRED,     'The message')
            ->addOption('--repeat', '-r',  InputOption::VALUE_REQUIRED, 'How many times the message must be published. Useful for testing.')
            ->addOption('--base64', '-b',  InputOption::VALUE_REQUIRED, 'Set value to "1" if the message is base64 encoded.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function produce(InputInterface $input, OutputInterface $output)
    {
        // Get message
        if($message = $input->getArgument('message')) {

            // Repeat the message (useful for debugging)
            $repeat = (int) $input->getOption('repeat') ?: 1;
            for($i=0;$i<$repeat;$i++) {

                // Decode base64 encoded argument
                if($isBase64 = $input->getOption('base64')) {
                    if($decoded = base64_decode($message)) {
                        $message = $decoded;
                    }
                }

                // Publish message
                $this->producer->publish([
                    'message' => $message
                ]);
            }
        }
    }



}