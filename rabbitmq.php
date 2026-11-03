<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

function conectarRabbitMQ()
{
    return new AMQPStreamConnection('localhost',5672,'guest','guest');
}

function configurarFilas($channel)
{
    // ==========================================
    // FILA FINAL DE ERRO
    // ==========================================

    $channel->queue_declare('lab.tarefas.erro',false,true,false,false);


    // ==========================================
    // FILA DE RETRY
    // espera 5 segundos e devolve para principal
    // ==========================================

    $retryArgs = new AMQPTable([
        'x-message-ttl' => 5000,
        'x-dead-letter-exchange' => '',
        'x-dead-letter-routing-key' => 'lab.tarefas'
    ]);

    $channel->queue_declare(
        'lab.tarefas.retry',
        false,
        true,
        false,
        false,
        false,
        $retryArgs
    );


    // ==========================================
    // FILA PRINCIPAL
    // ==========================================

    $channel->queue_declare(
        'lab.tarefas',
        false,
        true,
        false,
        false
    );
}