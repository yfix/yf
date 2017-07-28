<?php

require __DIR__.'/_rabbitmq.php';

# Создание обменника
$ex = new AMQPExchange($ch);
$ex->setName($rpc_ex_name);
$ex->setType(AMQP_EX_TYPE_TOPIC);
$ex->setFlags(AMQP_DURABLE);
$ex->declare();
 
while(true) {
	// Создание очереди для получения ответа
	$rq = new AMQPQueue($ch);
	$rq->setFlags(AMQP_IFUNUSED | AMQP_AUTODELETE | AMQP_EXCLUSIVE);
	$rq->declare();
	$rq->bind($ex->getName(), $rq->getName());
 
	$req_msg = 'test request message '.++$i;
 
	// Публикация запроса в обменник
	$ex->publish($req_msg, $rpc_topic_name, AMQP_MANDATORY, [
		'delivery_mode' => 2,
		'reply_to' => $rq->getName(),
		'correlation_id' => sha1($rq->getName()) 
	]);

	$resp_msg = null;
 
	// Чтение результата из обменника
	$rq->consume(function (AMQPEnvelope $env, AMQPQueue $q) use (&$resp_msg) {
		$resp_msg = $env->getBody();
		$q->ack($env->getDeliveryTag());
		return false;
	});

	echo $resp_msg. PHP_EOL;

	usleep(1000);
}
