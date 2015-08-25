<?php
namespace Boot\RabbitMQ\Tests;

use Boot\RabbitMQ\Producer\BatchProducer;
use Boot\RabbitMQ\Producer\Producer;
use Boot\RabbitMQ\Producer\ProducerInterface;
use Boot\RabbitMQ\Strategy\BasicBehaviour;
use Boot\RabbitMQ\Template\QueueTemplate;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ProducerTest extends AbstractRabbitMQTest
{
    /** @var  QueueTemplate */
    protected $queueTemplate;

    /** @var  ProducerInterface */
    protected $producer;

    /**
     * Setup the test
     */
    public function setUp()
    {
        $this->queueTemplate = new QueueTemplate(
            'some_test_queue',
            $this->createConnection(),
            new BasicBehaviour,
            $this->createEventDispatcher()
        );
    }


    /**
     * @test
     */
    public function executeCommand()
    {
        $this->markTestIncomplete('Add functional test for console command. (todo)');
    }

    /**
     * @test
     */
    public function publishMessage()
    {
        $producer = new Producer($this->queueTemplate);
        $producer->connect();
        $producer->publish(['message' => 'foo']);

        // Should have declared the queue once
        $this->assertEquals(1, $this->basicQueueDeclareInvocations->getInvocationCount());

        // Should have declared the quality of service once
        $this->assertEquals(1, $this->basicPublishInvocations->getInvocationCount());

    }

    /**
     * @test
     */
    public function publishBatchMessagesAndSubmit()
    {
        $producer = new BatchProducer($this->queueTemplate);
        $producer->connect();
        $producer->publish(['message' => 'foo']);
        $producer->publish(['message' => 'foo']);
        $producer->publish(['message' => 'foo']);

        // Should have declared the queue once
        $this->assertEquals(3, $this->batchBasicPublishInvocations->getInvocationCount());

        // Commit batch
        $producer->commit();

        // Should have declared the quality of service once
        $this->assertEquals(1, $this->publishBatchInvocations->getInvocationCount());
    }

}