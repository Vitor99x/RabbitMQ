<?php

    require_once __DIR__ . '/config.php';

    $sql_tarefas = "
        SELECT
            id,
            descricao,
            status,
            tentativas,
            erro,
            criado_em
        FROM tarefas
        ORDER BY id DESC;
    ";

    $query_tarefas = mysqli_query(
        $mysqli,
        $sql_tarefas
    );

    while (
        $resultado_tarefas =
        mysqli_fetch_array($query_tarefas)
    ){

        $id = trim(
            $resultado_tarefas['id']
        );

        $descricao = trim(
            $resultado_tarefas['descricao']
        );

        $status = trim(
            $resultado_tarefas['status']
        );

        $tentativas = trim(
            $resultado_tarefas['tentativas']
        );

        $erro = trim(
            $resultado_tarefas['erro'] ?? ''
        );

        $criado_em = trim(
            $resultado_tarefas['criado_em']
        );


        // ==========================================
        // CLASSE DO STATUS
        // ==========================================

        $classe_status = '';

        switch ($status) {

            case 'Pendente':

                $classe_status =
                    'status-pendente';

                break;


            case 'Processando':

                $classe_status =
                    'status-processando';

                break;


            case 'Aguardando retry':

                $classe_status =
                    'status-retry';

                break;


            case 'Finalizado':

                $classe_status =
                    'status-finalizado';

                break;


            case 'Erro':

                $classe_status =
                    'status-erro';

                break;

        }

?>

    <tr>

        <td>
            <?php echo $id ?>
        </td>

        <td>
            <?php echo htmlspecialchars($descricao) ?>
        </td>

        <td class="status <?php echo $classe_status ?>">

            <?php echo htmlspecialchars($status) ?>

        </td>

        <td class="tentativas">

            <?php echo $tentativas ?>

        </td>

        <td class="erro">

            <?php echo htmlspecialchars($erro) ?>

        </td>

        <td>

            <?php echo $criado_em ?>

        </td>

    </tr>

<?php

    };

?>