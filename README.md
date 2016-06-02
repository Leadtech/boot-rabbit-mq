# Quickstart

This library provides an easy to way to get up and running with RabbitMQ very quickly.
To implement rabbitMQ you'll first need to create an instance of the QueueTemplate class. This class represents a single line of communication between consumer(s) and producer(s).
Both the producer and the consumer classes will use the same template.
Once the template is available you'll have to subclass the AbstractConsumer class and implement a handle method.
Incoming messages are delegated to this method. The handle method returns either true or false indicating the success status.

To implement a producer simply instantiate or subclass `Boot\RabbitMQ\Producer\Producer` or `Boot\RabbitMQ\Producer\BatchProducer`
providing the queue template instance in the constructor. There is a command available that you can use to publish messages using the console.


### Full example

Full example of a fault tolerant queue. The messages are persisted and will survive a restart.
The client is configured to sent ACK/NACK signals manually.


#### Create worker.php

```
<?php
// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

use Boot\RabbitMQ\Strategy\FaultTolerantBehaviour;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Boot\RabbitMQ\Connection\AMQPConnection;
use Boot\RabbitMQ\Consumer\AbstractConsumer;
use Boot\RabbitMQ\RabbitMQ;
use Boot\RabbitMQ\Consumer\Event\ConsumerSuccessEvent;
use Boot\RabbitMQ\Consumer\Event\ReceiveEvent;

class ExampleConsumer extends AbstractConsumer
{
    /**
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     * @return bool
     */
    public function handle(\PhpAmqpLib\Message\AMQPMessage $message)
    {
        echo "Received message #{$message->body['sequence_number']}\n";

        // Return true for success, an ACK signal is sent to the server.
        // Alternatively an exception or returning false will result in a NACK signal instead.
        return true;
    }

}

// Create event dispatcher (optional)
$eventDispatcher = new EventDispatcher();

// Create queue template
$queueTemplate = new \Boot\RabbitMQ\Template\QueueTemplate(
    'some_queue_name',
    new AMQPConnection('localhost', 5672, 'guest', 'guest'),
    new FaultTolerantBehaviour,
    $eventDispatcher
);

$queueTemplate->setExclusive(false);

$eventDispatcher->addListener(RabbitMQ::ON_RECEIVE, function(ReceiveEvent $event){
    echo "Receiving a new message. Sequence number: {$event->getMessage()->body['sequence_number']}\n";
});


$eventDispatcher->addListener(RabbitMQ::ON_CONSUMER_SUCCESS, function(ConsumerSuccessEvent $event){
    echo "Successfully processed message. Sequence number: {$event->getMessage()->body['sequence_number']}\n\n";
});

$consumer = new ExampleConsumer($queueTemplate);
$consumer->connect();
$consumer->listen();

while($consumer->isBusy()) {
    $consumer->wait();
}
```

#### Create producer.php

```
<?php
// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';
use Boot\RabbitMQ\Strategy\FaultTolerantBehaviour;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Boot\RabbitMQ\Connection\AMQPConnection;
use Boot\RabbitMQ\Producer\Producer;

$eventDispatcher = new EventDispatcher();
$queueTemplate = new \Boot\RabbitMQ\Template\QueueTemplate(
    'some_queue_name',
    new AMQPConnection('localhost', 5672, 'guest', 'guest'),
    new FaultTolerantBehaviour
);

$queueTemplate->setExclusive(false);


$producer = new Producer($queueTemplate);
$producer->connect();

for($i=0;$i<=10;$i++) {
    $producer->publish([
        'sequence_number' => time() . '-' . $i
    ]);
}
```


### Implementing queue workers as command line using boot

Boot is a minimalistic framework build upon symfony components such as DependencyInjection, EventDispatcher and Console components and relies solely on composer for package management and auto-loading. Boot was created to provide a very basic yet elegant way to bootstrap an application.

Boot makes it very easy to create a console application to run a queue worker.

This is an example of how to bootstrap a console application: 

```
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Get environment
$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], 'dev');

// Build application
$rootDir = realpath(__DIR__ . '/..');
$app = (new \Boot\Builder($rootDir))
    ->appName('SomeConsumerApp')                                     # The name of the application
    ->caching('cache', false)                                        # Enable/disable caching of the service container
    ->environment($env)                                              # Set environment
    ->path('resources/config')                                       # Service configuration (order matters)
    ->path('src/Search/Resources/config')                            # Service configuration (order matters)
    ->parameter('project_dir', $rootDir)                             # Register parameters to the service container.
    ->beforeOptimization(new CommandCompilerPass)                    # Automatically register the commands to the console. Console commands must be tagged with a console_command tag.
    ->build()
;

/** @var ConsoleApplication $console */
$console = $app->get('console');
$console->getDefinition()->addOption(
    new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The environment name.', 'dev')
);

$console->run();
```



### Installation

After installing RabbitMQ you'll have to setup a project. If you decide to go with boot checkout the examples
folder. You will find a ready to use console application in there. If you want to start from scratch you will need to include
the following packages in your composer.json file. If you don't want to use boot and create your implementation of `symfony\console` than you
can use this package without installing boot as well.

*Installing Boot*
```
"require": {
    "leadtech/boot": "^1.0",
  }
```

*Installing Boot RabbitMQ*
```
"require": {
    "leadtech/boot-rabbit-mq": "^1.0"
  }
```

#### Requirements

System requirements
- PHP >= 5.4
- RabbitMQ Server

Required packages:
- videlalvaro/php-amqplib
- symfony/event-dispatcher
- symfony/console
- monolog/monolog


# QueueTemplate

### Responsibility
The responsibility of the QueueTemplate is to provide a single setup that is specific to a single line of communication between producers and consumers.
Both the consumer and the producer depend on the same setup provided by the queue template.

The QueueTemplate contains:
- The connection  (same configuration must be used for both the producer and the consumer).
- A message serializer.
- RabbitMQ specific options such as the queue name, exchange, passiveness, exclusive connections etcetera.
- A queue strategy.
- The event dispatcher.


#### What is a queue strategy?

RabbitMQ is a powerful queuing server and there are numerous ways to use it. This library provides two configurations out of the box providing either a fault tolerant solution
or a basic but faster setup.
Configurations may require a specific setup of both the consuming as the producing application(s).

A downside of many features is that it becomes easier to make mistakes along the way. Even more so when you have to deal with a number of
teams. By providing the single point of configuration the queue template prevents queuing logic from being scattered
across components and/or application(s).
The strategy should both simplify the implementation of RabbitMQ and improve the reliability of the implementation amongst teams and applications.


Creating a queue in memory which performs better but will not survive a crash:
```
$queueTemplate = new QueueTemplate('some_queue_name', $connection, new Boot\RabbitMQ\Strategy\BasicBehaviour);
```

Or to create a fault tolerant queue instead we would do:
```
$queueTemplate = new QueueTemplate('some_queue_name', $connection, new Boot\RabbitMQ\Strategy\FaultTolerantBehaviour);
```

#### Implementing a custom strategy

To implement a custom strategy simply create an object that extends the Boot\RabbitMQ\Strategy\QueueStrategy.

```
/**
 * Class SomeCustomBehaviour
 * @package Boot\RabbitMQ\Strategy
 */
class SomeCustomBehaviour extends QueueStrategy
{

   /**
     * @param QueueTemplate $queueTemplate
     * @param array $data
     *
     * @return AMQPMessage
     */
    public function createMessage(QueueTemplate $queueTemplate, array $data)
    {
        return new AMQPMessage(
            $queueTemplate->getSerializer()->serialize($data)
        );
    }

   /**
     * @param QueueTemplate $queueTemplate
     */
    public function declareQueue(QueueTemplate $queueTemplate)
    {
       // ...
    }

   /**
     * @param QueueTemplate $queueTemplate
     */
    public function declareQualityOfService(QueueTemplate $queueTemplate)
    {
        // ...
    }

   /**
     * Whether an (n)ack signal must be sent to the server. Depending on the setup this may or may not happen automatically.
     *
     * @return bool
     */
    public function doAckManually()
    {
        // ...
    }
}
```

*Do you have an awesome strategy that might be useful to others? Feel free to share ;-)*



# The consumer


### Responsibility

A consumer is responsible for listening and processing received messages from a particular queue.
Both the consumer and producer classes are tightly coupled to the QueueTemplate class.
The queue template provides a single configuration that applies to both consumers and producers.

### Implementing your consumer

To get started to started simply create a subclass of the Boot\RabbitMQ\Consumer\AbstractConsumer.

You must implement the following method:

```
/**
 * @param AMQPMessage $message
 * @return bool
 */
public function handle(AMQPMessage $message)
{
   print_r($message->body);

   // Return true on success, if the message could not be processed and you need the message to be enqueued again than return false.
   // Note that the client should be configured to sent ack/nack signals manually. (See FaultTolerantBehaviour strategy)

   return true;
}
```


### Instantiate consumer

Create the consumer object. The consumer has a dependency to the QueueTemplate class. The same template should be provided to a consumer.

```
$queueTemplate = $app->get('queueTemplate'); // Get fully configured queue template from whatever component one could use.
$consumer = new SomeMessageConsumer($queueTemplate, 'some_optional_consumer_name');
```

*Although this works it is better to use dependency injection to configure the components. If the consumer(s) are implemented as a standalone application I
recommend to checkout the PHPBoot repository which implements a lightweight implementation of the Symfony2 service container and console component.
I will add ready to use example of this library there as well. (by the time you read this it might already be there)*


### Handling incoming messages

Start listening to incoming messages.

```
if($consumer->connect()) {
    $consumer->listen();
}
```

The connect method will:
- Create a connection to the server. The consumer will get a configured connection object from queue template.
- Declare a queue as defined in the queue template and its strategy
- Declare the quality of service (qos) as defined in the queue template and its strategy

The listen method will:
- Create a channel and subscribe to a particular queue. Internally we provide a callback that will be executed for each received message.
  Because the AbstractConsumer implements the magic __invoke method we can consumer instances as a valid callback.
  When the __invoke method is called we will delegate the call to the handle method on the concrete consumer.
  The handle method must return true or false. This is especially crucial in setups where ack/nack signals are sent manually.



We are processing the messages just yet. To start accepting incoming messages we must execute:

```
// Start receiving
while ($consumer->isBusy()) {
    // Wait for messages. Any incoming message is delegated to the consumer object
    $consumer->wait();
}
```



### Listen to consumer events

The customer provides additional functionality to make it easy for other components to attach additional functionality.
The following events are implemented:
- ON_RECEIVE
- ON_CONSUMER_ERROR
- ON_CONSUMER_SUCCESS


**Tip: checkout the symfony2 documentation for full documentation about the EventDispatcher and events.
There are more ways to register listeners. (Event subscribers, using objects instead of functions etc.)**


#### Adding listeners

```
use Boot\RabbitMQ\RabbitMQ;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Boot\RabbitMQ\Consumer\Event\ReceiveEvent

$eventDispatcher = new EventDispatcher();

// ON_RECEIVE
$eventDispatcher->addListener(RabbitMQ::ON_RECEIVE, function(ReceiveEvent $event){
    var_dump($event->getMessage());
    var_dump($event->getConsumer());
});

// ON_CONSUMER_ERROR
$eventDispatcher->addListener(RabbitMQ::ON_CONSUMER_ERROR, function(ReceiveEvent $event){
    // ...
});

// ON_CONSUMER_SUCCESS
$eventDispatcher->addListener(RabbitMQ::ON_CONSUMER_SUCCESS, function(ReceiveEvent $event){
    // ...
});
```


#### Console command


This library provides an easy way to run consumers from the command line.

**Instantiate the ConsumerCommand**

```
   /** @var Symfony\Component\Console\Application $console */
   $console->add(
       new Boot\RabbitMQ\Command\ConsumerCommand(
           'consume:my-foo-consumer',                         # Provide a command name
           null,                                              # Provide a logger that implements LoggerInterface. (most mainstream php loggers do, see PSR guidelines)
           'Start consumer to process my foo messages.',      # A description for this command
           1                                                  # Optional sleep time after handling a message. Provide interval in seconds.
       );
   );
   $console->run();
   ```

Start consuming by executing:
`php /path/to/app/console consume:my-foo-consumer`


The provided solution works for any console application build upon or based on symfony2 components.
If you are not using symfony2 for this project I recommend to checkout the PHPBoot repository. Boot provides a very minimalistic micro framework
build solely on composer, the symfony2 service container with (optional) build in support for the console component.
Boot provides a powerful builder that you can use to setup your application.
Components are designed to be framework agnostic, flexible, and lightweight.


# Producer


### Responsibility

A producer is responsible for publishing messages to the queue. The producer uses the same queue template as the consumer.
This library provides both the producer class and another producer for batch operations.
There is no need to subclass the producer although you can if you need to.

### Example

Using a producer to publish a message.
```
use Boot\RabbitMQ\Producer\Producer
/** @var QueueTemplate $queueTemplate*/
$producer = new Producer($queueTemplate);
$producer->connect();
$producer->publish([
    'message'          => 'some example message',
    'some-other-field' => 'some other value',
    'published'        => time()
]);
```

Using a batch producer to publish multiple messages at once.
```
use Boot\RabbitMQ\Producer\BatchProducer
/** @var QueueTemplate $queueTemplate*/
$producer = new BatchProducer($queueTemplate);
$producer->connect();

// Publish message 1
$producer->publish([
    'message'          => 'some example message',
    'published'        => time()
]);
// Publish message 2
$producer->publish([
    'message'          => 'some example message',
    'published'        => time()
]);
// Commit
$producer->commit();
```


### Console

We've made it easy for you to be up and running in little time. For development/testing you can register instances of the ConsoleProducerCommand class.
This command allows you to publish messages simply by running a command. The command depends on the producer object and uses the producer and the queue template to
setup the connection. When you need to it is quite easy to develop your own command as well. Just extend the Boot\RabbitMQ\Command\AbstractProducerCommand class and it should almost work
out of the box. The command must implement a produce method. Check the source of Boot\RabbitMQ\Command\ConsoleProducerCommand to see how it works. (just a few lines of code, I promise ;-))

Example of how the command can be created (supposing you do not use dependency injection nor symfony2 etc.
```
/**
 * @var Symfony\Component\Console\Application $console
 * @var Boot\RabbitMQ\Producer\Producer       $producer
 */
$console->add(
    new Boot\RabbitMQ\Command\ConsoleProducerCommand(
        'produce:my-foo-producer',                         # Provide a command name
        $producer,                                         # Provide the producer
        null,                                              # Provide a logger that implements LoggerInterface. (most mainstream php loggers do, see PSR guidelines)
    );
);
$console->run();
```

To publish the same message 10 times execute:
`php /path/to/app/console produce:my-foo-producer --repeat=10  --base64=0  "This is my message"`



# Serializer

### Responsibility

The serializer is responsible of the serialization of messages that are processed by RabbitMQ.
Neither the consumer nor the producer should know how the serialization process works.
The producer and consumer will automatically use the same conversion logic by requesting the serializer from the same corresponding queue template.
If no serializer is defined than we will automatically use the included JsonSerializer class.


### Implementing your own serializer

To implement your own serializer simply create an object that implements the 'Boot\RabbitMQ\Serializer\SerializerInterface'.
Let's say we want to use encryption to secure super secret objects.
We can implement this functionality simply by creating a serializer such as the one in the example below.
Simply inject an instance of this serializer into the queue template and you're good to go.

For example:

```

use Boot\RabbitMQ\Serializer\SerializerInterface;

class EncryptedJsonSerializer implements SerializerInterface
{
   /** @var string */
   private $secretKey;

   /**
     * @param string $secretKey
     */
    public function serialize($secretKey)
    {
        $this->secretKey = $secretKey;
    }


   /**
     * @param array $data
     * @return string
     */
    public function serialize(array $data)
    {
        return $this->encrypt(serialize($data));
    }

   /**
     * @param $data
     * @return array
     */
    public function unserialize($data)
    {
        return unserialize($this->decrpyt($data), true);
    }

   /**
     * @param string $string
     * @return string
     */
    protected function encrypt($string)
    {
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->secretKey, $string, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

   /**
     * @param string $string
     * @return string
     */
    protected function decrypt($string)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->secretKey, base64_decode($string), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }


}
```

*While writing this example I realized that this would be a useful addition. So I added a serializer that uses encryption as well. :-)*





