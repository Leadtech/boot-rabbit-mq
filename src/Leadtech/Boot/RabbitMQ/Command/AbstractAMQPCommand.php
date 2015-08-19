<?php
namespace Boot\RabbitMQ\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractAMQPCommand
 * @package Search\QueueConsumer
 */
abstract class AbstractAMQPCommand extends Command
{
    /**
     * @return void
     */
    abstract public function connect();
}
