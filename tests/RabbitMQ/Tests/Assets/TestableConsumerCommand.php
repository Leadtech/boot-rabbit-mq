<?php
namespace Boot\RabbitMQ\Tests\Assets;

use Boot\RabbitMQ\Command\ConsumerCommand;
use PhpAmqpLib\Channel\AMQPChannel;

class TestableConsumerCommand extends ConsumerCommand
{
    protected $invokeCountCanContinueMethod = 0;

    public function wait()
    {
        // Wait one time and than empty the callback collection. (or we would end up with an eternal loop)
        $this->consumer->getQueueTemplate()->channel()->callbacks[] = [];
    }

    /**
     * @return bool
     */
    protected function canContinue()
    {
        if(++$this->invokeCountCanContinueMethod > 1) {
            return false;
        }

        return true;
    }


}