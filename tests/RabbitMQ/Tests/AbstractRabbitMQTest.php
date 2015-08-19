<?php
namespace Boot\RabbitMQ\Tests;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractRabbitMQTest
 *
 * Abstract class for RabbitMQ producer/consumer tests
 */
abstract class AbstractRabbitMQTest extends PHPUnit_Framework_TestCase
{
    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $channelInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $basicNackInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $basicAckInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $basicConsumeInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $basicPublishInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $basicQosInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $basicQueueDeclareInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $publishBatchInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $batchServicePublishInvocations;

    /** @var  PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount */
    protected $waitInvocations;

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected function createEventDispatcher()
    {
        return new \Symfony\Component\EventDispatcher\EventDispatcher();
    }

    /**
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    protected function createConnection()
    {
        $mock = $this
            ->getMockBuilder('PhpAmqpLib\Connection\AMQPStreamConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->channelInvocations = $this->any())
            ->method('channel')
            ->willReturn($this->createChannel())
        ;

        return $mock;
    }

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel|PHPUnit_Framework_MockObject_MockObject
     */
    protected function createChannel()
    {
        $mock = $this
            ->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->basicNackInvocations = $this->any())
            ->method('basic_nack')
        ;

        $mock
            ->expects($this->basicAckInvocations = $this->any())
            ->method('basic_ack')
        ;

        $mock
            ->expects($this->basicConsumeInvocations = $this->any())
            ->method('basic_consume')
        ;

        $mock
            ->expects($this->basicPublishInvocations = $this->any())
            ->method('basic_publish')
        ;

        $mock
            ->expects($this->basicQosInvocations = $this->any())
            ->method('basic_qos')
        ;

        $mock
            ->expects($this->basicQueueDeclareInvocations = $this->any())
            ->method('queue_declare')
        ;

        $mock
            ->expects($this->publishBatchInvocations = $this->any())
            ->method('publish_batch')
        ;

        $mock
            ->expects($this->batchServicePublishInvocations = $this->any())
            ->method('batch_basic_publish')
        ;

        $mock
            ->expects($this->waitInvocations = $this->any())
            ->method('wait')
        ;

        return $mock;
    }

    /**
     * @return LoggerInterface
     */
    protected function createLogger()
    {
        $logger = new Logger(__CLASS__);
        $logger->pushHandler(new NullHandler);

        return $logger;
    }

}