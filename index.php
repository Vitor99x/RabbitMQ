<?php

    require_once 'config.php';

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>RabbitMQ Lab</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;
            padding: 30px;

            font-family: Arial, sans-serif;

            background: #f4f6f8;

        }

        .container {

            max-width: 1100px;

            margin: 0 auto;

        }

        .card {

            background: #ffffff;

            padding: 25px;

            border-radius: 10px;

            margin-bottom: 20px;

            box-shadow: 0 2px 8px rgba(0,0,0,.08);

        }

        h1,
        h2 {

            margin-top: 0;

        }

        .formulario {

            display: flex;

            gap: 15px;

            align-items: center;

            flex-wrap: wrap;

        }

        input[type="text"] {

            flex: 1;

            min-width: 300px;

            padding: 12px;

            border: 1px solid #ccc;

            border-radius: 5px;

        }

        button {

            padding: 12px 20px;

            border: 0;

            border-radius: 5px;

            cursor: pointer;

            font-weight: bold;

            background: #2f6fed;

            color: #ffffff;

        }

        button:hover {

            opacity: .9;

        }

        table {

            width: 100%;

            border-collapse: collapse;

        }

        th,
        td {

            padding: 12px;

            border-bottom: 1px solid #eee;

            text-align: left;

            vertical-align: middle;

        }

        th {

            background: #f8f8f8;

        }

        .status {

            font-weight: bold;

        }

        .status-pendente {

            color: #b58900;

        }

        .status-processando {

            color: #0066cc;

        }

        .status-retry {

            color: #7b4ce2;

        }

        .status-finalizado {

            color: green;

        }

        .status-erro {

            color: red;

        }

        .tentativas {

            font-weight: bold;

            text-align: center;

        }

        .erro {

            color: #b00020;

            max-width: 300px;

            word-break: break-word;

        }

        .atualizando {

            font-size: 12px;

            color: #777;

            margin-left: 10px;

            font-weight: normal;

        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <h1>RabbitMQ Lab</h1>

        <p>
            Envie tarefas para serem processadas pelo RabbitMQ.
        </p>

        <form action="enviar.php" method="POST" class="formulario">

            <input
                type="text"
                name="descricao"
                placeholder="Descrição da tarefa"
                required
            >

            <label>

                <input
                    type="checkbox"
                    name="simular_erro"
                    value="1"
                >

                Simular erro

            </label>

            <button type="submit">
                Enviar tarefa
            </button>

        </form>

    </div>


    <div class="card">

        <h2>
            Tarefas

            <span class="atualizando">
                atualização automática
            </span>
        </h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Descrição</th>

                    <th>Status</th>

                    <th>Tentativas</th>

                    <th>Erro</th>

                    <th>Criado</th>

                </tr>

            </thead>

            <tbody id="lista-tarefas">

                <?php

                    $sql_tarefas = "SELECT * FROM tarefas ORDER BY id DESC;";

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

            </tbody>

        </table>

    </div>

</div>


<script>

    function carregarTarefas() {

        fetch('listar_tarefas.php')

            .then(function(response) {

                return response.text();

            })

            .then(function(html) {

                document.getElementById(
                    'lista-tarefas'
                ).innerHTML = html;

            })

            .catch(function(error) {

                console.error(
                    'Erro ao carregar tarefas:',
                    error
                );

            });

    }


    // ==========================================
    // ATUALIZA A CADA 2 SEGUNDOS
    // ==========================================

    setInterval(
        carregarTarefas,
        2000
    );

</script>

</body>

</html>