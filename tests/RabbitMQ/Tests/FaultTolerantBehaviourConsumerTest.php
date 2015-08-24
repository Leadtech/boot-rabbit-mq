<?php
namespace Boot\RabbitMQ\Tests;

use Boot\RabbitMQ\Consumer\AbstractConsumer;
use Boot\RabbitMQ\RabbitMQ;
use Boot\RabbitMQ\Strategy\FaultTolerantBehaviour;
use Boot\RabbitMQ\Template\QueueTemplate;
use Boot\RabbitMQ\Tests\Assets\CleanFailConsumer;
use Boot\RabbitMQ\Tests\Assets\Consumer;
use Boot\RabbitMQ\Tests\Assets\TestableConsumerCommand;
use Boot\RabbitMQ\Tests\Assets\FailWithExceptionConsumer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class BasicBehaviourConsumerTest extends AbstractRabbitMQTest
{
    /** @var  QueueTemplate */
    protected $queueTemplate;

    /** @var  AbstractConsumer */
    protected $consumer;

    /**
     * Setup the test
     */
    public function setUp()
    {
        $this->queueTemplate = new QueueTemplate(
            $this->createConnection(),
            new FaultTolerantBehaviour,
            $this->createEventDispatcher()
        );

        $this->queueTemplate->setQueueName('some_test_queue');

        $this->consumer = $this->getMockForAbstractClass('Boot\RabbitMQ\Consumer\AbstractConsumer', [$this->queueTemplate], 'MockConsumer');
    }

    /**
     * @test
     */
    public function shouldExecuteConsumeCommand()
    {
        $application = new Application('prod');

        // Add command
        $command = new TestableConsumerCommand(
            'fake:consumer',
            $this->getMockForAbstractClass('Boot\RabbitMQ\Consumer\AbstractConsumer', [$this->queueTemplate], 'MockConsumer'),
            $this->createLogger()
        );
        $application->add($command);

        // Create command tester
        $commandTester = new CommandTester($command);

        // Execute consumer
        $commandTester->execute(['command' => $command->getName()]);

        // Should have declared the queue once
        $this->assertEquals(1, $this->basicQueueDeclareInvocations->getInvocationCount());

        $args = $this->basicQueueDeclareInvocations->getInvocations()[0]->parameters;

        // Make sure that the queue name is correct
        $this->assertEquals('some_test_queue', $args[0]);

        // Make sure that the queue is durable
        $this->assertTrue($args[2], 'The queue is not durable!');

        // Make sure that the queue is not automatically deleted
        $this->assertFalse($args[4], 'The queue is automatically deleted!');

        // Should have declared the quality of service once
        $this->assertEquals(1, $this->basicQosInvocations->getInvocationCount());

        // Should have declared the quality of service once
        $this->assertEquals(1, $this->basicConsumeInvocations->getInvocationCount());

        // The wait method should have been invoked once
        $this->assertEquals(1, $this->waitInvocations->getInvocationCount());

        // Should have status code 0
        $this->assertEquals(0, $commandTester->getStatusCode());

    }

    /**
     * @test
     */
    public function messageShouldBePersistent()
    {
        $message = $this->queueTemplate->createMessage(['msg' => 'blaat']);
        $this->assertEquals(RabbitMQ::DELIVERY_MODE_PERSISTENT, $message->get('delivery_mode'));
    }

    /**
     * @test
     */
    public function shouldSentAckSignal()
    {
        $consumer = new Consumer($this->queueTemplate);
        $message = $this->queueTemplate->createMessage(['msg' => 'blaat']);
        $message->delivery_info = ['channel' => $this->queueTemplate->channel(), 'delivery_tag' => 123];
        call_user_func($consumer, $message);

        // Assert that the ack signal was sent
        $this->assertEquals(1, $this->basicAckInvocations->getInvocationCount());
    }

    /**
     * @test
     */
    public function shouldSentNackSignal()
    {
        // Create message
        $message = $this->queueTemplate->createMessage(['msg' => 'blaat']);
        $message->delivery_info = ['channel' => $this->queueTemplate->channel(), 'delivery_tag' => 123];

        // Create a failing consumer instance. This instance will return false. A nack signal should be sent.
        $consumer = new CleanFailConsumer($this->queueTemplate);

        // Execute consumer
        call_user_func($consumer, $message);

        // Assert that the ack signal was sent
        $this->assertEquals(1, $this->basicNackInvocations->getInvocationCount());

        // Create a failing consumer instance. This instance will return false. A nack signal should be sent.
        $consumer = new FailWithExceptionConsumer($this->queueTemplate);

        // Execute consumer
        try {
            call_user_func($consumer, $message);
        } catch(\Exception $e) {
            // Make sure no exception is thrown here.
        }

        // Assert that the ack signal was sent
        $this->assertEquals(1, $this->basicNackInvocations->getInvocationCount());
    }

}