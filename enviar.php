<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;

$descricao = trim($_POST['descricao'] ?? '');

$simularErro = isset($_POST['simular_erro']) ? 1 : 0;

if ($descricao === '') {
    die('Descrição obrigatória.');
}


// ==========================================
// 1. SALVA NO MYSQL
// ==========================================

$stmt = $mysqli->prepare("
    INSERT INTO tarefas (
        descricao,
        status,
        tentativas,
        simular_erro
    )
    VALUES (
        ?,
        'Pendente',
        0,
        ?
    )
");

$stmt->bind_param(
    'si',
    $descricao,
    $simularErro
);

$stmt->execute();

$tarefaId = $mysqli->insert_id;


// ==========================================
// 2. CONECTA AO RABBITMQ
// ==========================================

$connection = conectarRabbitMQ();

$channel = $connection->channel();


// ==========================================
// 3. GARANTE QUE TODAS AS FILAS EXISTEM
// ==========================================

configurarFilas($channel);


// ==========================================
// 4. MONTA A MENSAGEM
// ==========================================

$dados = [
    'tarefa_id'      => $tarefaId,
    'descricao'      => $descricao,
    'simular_erro'   => (bool) $simularErro,
    'criado_em'      => date('Y-m-d H:i:s')
];

$msg = new AMQPMessage(
    json_encode($dados),
    [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    ]
);


// ==========================================
// 5. ENVIA PARA O RABBITMQ
// ==========================================

$channel->basic_publish(
    $msg,
    '',
    'lab.tarefas'
);


// ==========================================
// 6. FECHA CONEXAO
// ==========================================

$channel->close();

$connection->close();


// ==========================================
// 7. VOLTA PARA A TELA
// ==========================================

header('Location: index.php');

exit;