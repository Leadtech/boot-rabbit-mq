<?php
namespace Boot\RabbitMQ;

final class RabbitMQ
{
    const DELIVERY_MODE_PERSISTENT = 2;

    const ON_RECEIVE_EVENT = 'consumer.message.receive.event';
    const ON_CONSUMER_SUCCESS = 'consumer.message.success.event';
    const ON_CONSUMER_ERROR = 'consumer.message.error.event';
}