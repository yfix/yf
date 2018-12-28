<?php

require __DIR__ . '/_rabbitmq.php';

echo 'Worker started listening a queue...' . PHP_EOL;

// Создание обменника
$ex = new AMQPExchange($ch);
$ex->setName($rpc_ex_name);
$ex->setType(AMQP_EX_TYPE_TOPIC);
$ex->setFlags(AMQP_DURABLE);
$ex->declare();

// Создание очереди для приёма запросов
$q = new AMQPQueue($ch);
$q->setName($rpc_q_name);
$q->setFlags(AMQP_DURABLE);
$q->declare();
$q->bind($ex->getName(), $rpc_topic_name);

// Метод реализующий обработку запроса
$callback = function ($input) {
    echo 'processing string ' . $input . PHP_EOL;
    return 'Processed ' . $input;
};

// Чтение запроса
$q->consume(function (AMQPEnvelope $env, AMQPQueue $q) use ($callback, $ex) {
    $resp_msg = $callback($env->getBody());
    $ex->publish($resp_msg, $env->getReplyTo(), AMQP_MANDATORY, [
        'correlation_id' => $env->getCorrelationId(),
    ]);
    $q->ack($env->getDeliveryTag());
});
