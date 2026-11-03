<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection(
    'localhost',
    5672,
    'guest',
    'guest'
);

$channel = $connection->channel();

$channel->queue_declare('lab.tarefas',false,true,false,false);

$dados = [
    'id' => 1,
    'descricao' => 'Minha primeira tarefa',
    'data' => date('Y-m-d H:i:s')
];

$msg = new AMQPMessage(json_encode($dados),['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

$channel->basic_publish($msg,'','lab.tarefas');

echo "Mensagem enviada!\n";

print_r($dados);

$channel->close();
$connection->close();