<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = conectarRabbitMQ();

$channel = $connection->channel();

configurarFilas($channel);

echo "Consumer iniciado.\n";
echo "Aguardando mensagens da fila lab.tarefas...\n\n";


$callback = function (AMQPMessage $msg) use ($mysqli, $channel) {

    $dados = json_decode(
        $msg->getBody(),
        true
    );

    if (!$dados || !isset($dados['tarefa_id'])) {

        echo "Mensagem inválida.\n";

        $msg->nack(false, false);

        return;
    }


    $tarefaId = (int) $dados['tarefa_id'];

    $descricao = $dados['descricao'] ?? '';

    $simularErro = !empty($dados['simular_erro']);


    // ==========================================
    // PEGA RETRY
    // ==========================================

    $retryCount = 0;

    if ($msg->has('application_headers')) {

        $headers = $msg
            ->get('application_headers')
            ->getNativeData();

        $retryCount = $headers['x-retry-count'] ?? 0;

    }


    $tentativaAtual = $retryCount + 1;


    echo "========================================\n";
    echo "Tarefa recebida\n";
    echo "ID: {$tarefaId}\n";
    echo "Descrição: {$descricao}\n";
    echo "Tentativa: {$tentativaAtual}/3\n";


    // ==========================================
    // STATUS PROCESSANDO
    // ==========================================

    $stmt = $mysqli->prepare("
        UPDATE tarefas
        SET
            status = 'Processando',
            tentativas = ?,
            erro = NULL
        WHERE id = ?
    ");

    $stmt->bind_param(
        'ii',
        $tentativaAtual,
        $tarefaId
    );

    $stmt->execute();


    try {

        sleep(2);


        // ==========================================
        // SIMULA ERRO
        // ==========================================

        if ($simularErro) {

            throw new Exception(
                'Erro proposital durante o processamento.'
            );

        }


        // ==========================================
        // SUCESSO
        // ==========================================

        $stmt = $mysqli->prepare("
            UPDATE tarefas
            SET
                status = 'Finalizado',
                erro = NULL,
                processado_em = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            'i',
            $tarefaId
        );

        $stmt->execute();


        $msg->ack();


        echo "Status: Finalizado\n";
        echo "ACK enviado.\n";


    } catch (Exception $e) {

        $erro = $e->getMessage();

        echo "ERRO: {$erro}\n";


        // ==========================================
        // AINDA TEM TENTATIVA
        // ==========================================

        if ($tentativaAtual < 3) {


            $novaMensagem = new AMQPMessage(
                $msg->getBody(),
                [
                    'delivery_mode' =>
                        AMQPMessage::DELIVERY_MODE_PERSISTENT,

                    'application_headers' =>
                        new AMQPTable([
                            'x-retry-count' => $tentativaAtual
                        ])
                ]
            );


            // manda para fila de retry
            $channel->basic_publish(
                $novaMensagem,
                '',
                'lab.tarefas.retry'
            );


            // confirma mensagem atual
            $msg->ack();


            $stmt = $mysqli->prepare("
                UPDATE tarefas
                SET
                    status = 'Aguardando retry',
                    erro = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                'si',
                $erro,
                $tarefaId
            );

            $stmt->execute();


            echo "Mensagem enviada para retry.\n";
            echo "Aguardando 5 segundos para próxima tentativa.\n";


        } else {


            // ==========================================
            // ESGOTOU
            // ==========================================

            $mensagemErro = new AMQPMessage(
                $msg->getBody(),
                [
                    'delivery_mode' =>
                        AMQPMessage::DELIVERY_MODE_PERSISTENT,

                    'application_headers' =>
                        new AMQPTable([
                            'x-retry-count' => $tentativaAtual,
                            'x-error-message' => $erro
                        ])
                ]
            );


            $channel->basic_publish(
                $mensagemErro,
                '',
                'lab.tarefas.erro'
            );


            $msg->ack();


            $stmt = $mysqli->prepare("
                UPDATE tarefas
                SET
                    status = 'Erro',
                    erro = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                'si',
                $erro,
                $tarefaId
            );

            $stmt->execute();


            echo "Limite de tentativas atingido.\n";
            echo "Mensagem enviada para lab.tarefas.erro.\n";

        }

    }


    echo "========================================\n\n";

};


$channel->basic_qos(
    null,
    1,
    null
);


$channel->basic_consume(
    'lab.tarefas',
    '',
    false,
    false,
    false,
    false,
    $callback
);


while ($channel->is_consuming()) {

    $channel->wait();

}