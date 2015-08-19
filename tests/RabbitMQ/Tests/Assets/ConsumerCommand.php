<?php
namespace Boot\RabbitMQ\Tests\Assets;

use Boot\RabbitMQ\Command\AbstractConsumerCommand;
use PhpAmqpLib\Channel\AMQPChannel;

class ConsumerCommand extends AbstractConsumerCommand
{
    protected $invokeCountCanContinueMethod = 0;

    public function wait()
    {
        // Wait one time and than empty the callback collection. (or we would end up with an eternal loop)
        $this->consumer->getQueueTemplate()->createChannel()->callbacks[] = [];
    }

    /**
     * @param AMQPChannel $channel
     * @return bool
     */
    protected function canContinue(AMQPChannel $channel)
    {
        if(++$this->invokeCountCanContinueMethod > 1) {
            return false;
        }

        return true;
    }


}