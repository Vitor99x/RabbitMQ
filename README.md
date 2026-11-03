# RabbitMQ Lab

Projeto de estudo desenvolvido para compreender, na prática, o funcionamento do RabbitMQ integrado com PHP e MySQL.

A aplicação simula um sistema de processamento assíncrono de tarefas, permitindo acompanhar desde o envio de uma mensagem até seu processamento, novas tentativas em caso de falha e envio para uma fila de erro após o limite de tentativas.

## Objetivo

O objetivo deste projeto é estudar conceitos de mensageria utilizando um cenário simples e visual.

A aplicação permite criar tarefas através de uma interface web. Essas tarefas são registradas no MySQL e publicadas no RabbitMQ para processamento por um Consumer executado separadamente.

O fluxo básico é:

```text
Aplicação Web
     |
     v
   MySQL
     |
     v
 Producer PHP
     |
     v
 RabbitMQ
     |
     v
 Consumer PHP
     |
     +----------------------+
     |                      |
   Sucesso                 Erro
     |                      |
     v                      v
    ACK                Retry Queue
                            |
                           TTL
                            |
                            v
                      Fila principal
                            |
                      até 3 tentativas
                            |
                            v
                           DLQ
```

## Funcionalidades

- Cadastro de tarefas através de uma interface web
- Publicação de mensagens no RabbitMQ
- Processamento assíncrono utilizando Consumer PHP
- Controle manual de ACK
- Simulação de erros
- Controle de quantidade de tentativas
- Retry automático
- Retry Queue com TTL
- Dead Letter Queue
- Persistência do status das tarefas no MySQL
- Atualização automática da tela com AJAX
- Visualização de tarefas pendentes, processando, aguardando retry, finalizadas ou com erro

## Tecnologias utilizadas

- PHP 8
- MySQL / MariaDB
- RabbitMQ
- AMQP
- php-amqplib
- Composer
- JavaScript
- AJAX
- HTML
- CSS

## Arquitetura

O projeto utiliza três filas principais.

### `lab.tarefas`

Fila responsável pelo processamento normal das tarefas.

```text
Aplicação
   |
   v
lab.tarefas
   |
   v
Consumer
```

### `lab.tarefas.retry`

Fila temporária utilizada quando ocorre uma falha no processamento.

A mensagem permanece nessa fila durante um período definido por TTL.

Após o TTL, o RabbitMQ encaminha automaticamente a mensagem novamente para a fila principal.

```text
lab.tarefas
     |
    erro
     |
     v
lab.tarefas.retry
     |
   TTL 5s
     |
     v
lab.tarefas
```

### `lab.tarefas.erro`

Fila utilizada quando a tarefa ultrapassa o limite de tentativas.

```text
Tentativa 1
    |
   erro
    |
Tentativa 2
    |
   erro
    |
Tentativa 3
    |
   erro
    |
    v
lab.tarefas.erro
```

Essa fila funciona como uma Dead Letter Queue para permitir posterior análise da mensagem que não pôde ser processada.

## Fluxo de status

Uma tarefa pode passar pelos seguintes estados:

```text
Pendente
   |
   v
Processando
   |
   +----------------------+
   |                      |
 sucesso                 erro
   |                      |
   v                      v
Finalizado        Aguardando retry
                          |
                          v
                    Processando
                          |
                        ...
                          |
                          v
                        Erro
```

## Estrutura do projeto

```text
rabbitmq-lab/
|
|-- index.php
|-- enviar.php
|-- consumer.php
|-- producer.php
|-- listar_tarefas.php
|-- rabbitmq.php
|-- config.php
|-- composer.json
|-- composer.lock
|-- .gitignore
|
`-- vendor/
```

### `index.php`

Interface principal da aplicação.

Permite cadastrar novas tarefas e acompanhar os status em tempo real.

### `enviar.php`

Responsável por:

1. registrar a tarefa no banco;
2. criar a mensagem;
3. publicar a mensagem na fila `lab.tarefas`.

### `consumer.php`

Worker responsável pelo processamento das mensagens.

Ele permanece conectado ao RabbitMQ aguardando novas tarefas.

### `rabbitmq.php`

Centraliza a configuração da conexão com RabbitMQ e declaração das filas.

### `listar_tarefas.php`

Utilizado pelo AJAX da interface para atualizar a tabela automaticamente.

### `config.php`

Responsável pela conexão com o banco de dados.

## Banco de dados

Crie o banco:

```sql
CREATE DATABASE rabbitmq_lab
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Depois:

```sql
USE rabbitmq_lab;
```

Crie a tabela:

```sql
CREATE TABLE tarefas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    descricao VARCHAR(255) NOT NULL,

    status VARCHAR(30) NOT NULL DEFAULT 'Pendente',

    tentativas INT NOT NULL DEFAULT 0,

    simular_erro TINYINT(1) NOT NULL DEFAULT 0,

    erro TEXT NULL,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    processado_em DATETIME NULL

);
```

## Instalação

Clone o repositório:

```bash
git clone https://github.com/Vitor99x/RabbitMQ.git
```

Entre na pasta:

```bash
cd RabbitMQ
```

Instale as dependências:

```bash
composer install
```

Configure o MySQL em:

```text
config.php
```

Exemplo:

```php
$mysqli = new mysqli(
    'localhost',
    'root',
    '',
    'rabbitmq_lab'
);
```

## RabbitMQ

O projeto utiliza por padrão:

```text
Host: localhost
Porta AMQP: 5672
Usuário: guest
Senha: guest
```

O painel administrativo pode ser acessado em:

```text
http://localhost:15672
```

Credenciais padrão:

```text
Usuário: guest
Senha: guest
```

## Executando o projeto

Com Apache, MySQL e RabbitMQ em execução, abra:

```text
http://localhost/RabbitMQ/
```

Depois execute o Consumer em outro terminal:

```bash
php consumer.php
```

O terminal ficará aguardando novas mensagens:

```text
Consumer iniciado.
Aguardando mensagens da fila lab.tarefas...
```

## Teste de sucesso

Crie uma tarefa sem marcar a opção de simular erro.

O fluxo esperado será:

```text
Pendente
   |
   v
Processando
   |
   v
Finalizado
```

O Consumer enviará um ACK ao RabbitMQ depois que o processamento for concluído.

## Teste de erro

Crie uma tarefa marcando:

```text
Simular erro
```

O fluxo será:

```text
Tentativa 1
    |
   erro
    |
    v
Retry Queue
    |
   5s
    |
    v
Tentativa 2
    |
   erro
    |
    v
Retry Queue
    |
   5s
    |
    v
Tentativa 3
    |
   erro
    |
    v
Dead Letter Queue
```

A tarefa será marcada como:

```text
Erro
```

e a mensagem ficará disponível na fila:

```text
lab.tarefas.erro
```

## RabbitMQ Management

Durante os testes é possível acompanhar as filas em:

```text
Queues and Streams
```

As principais filas são:

```text
lab.tarefas
lab.tarefas.retry
lab.tarefas.erro
```

Durante um retry, a mensagem ficará temporariamente em:

```text
lab.tarefas.retry
```

Após atingir o limite de tentativas:

```text
lab.tarefas.erro
```

## Conceitos estudados

Este projeto foi criado para praticar os principais conceitos de RabbitMQ:

### Producer

Aplicação responsável por publicar mensagens.

### Consumer

Processo responsável por consumir e processar mensagens.

### Queue

Estrutura utilizada pelo RabbitMQ para armazenar mensagens até que possam ser processadas.

### ACK

Confirma ao RabbitMQ que a mensagem foi processada com sucesso.

### NACK

Informa que houve falha durante o processamento.

### Retry

Nova tentativa de processamento após uma falha.

### TTL

Tempo que uma mensagem pode permanecer em uma fila.

Neste projeto, a Retry Queue utiliza TTL antes de devolver a mensagem à fila principal.

### Dead Letter Queue

Fila utilizada para armazenar mensagens que não puderam ser processadas após todas as tentativas.

## Exemplo de mensagem

Uma mensagem publicada pelo sistema possui estrutura semelhante a:

```json
{
    "tarefa_id": 14,
    "descricao": "Processar documento",
    "simular_erro": true,
    "criado_em": "2026-11-03 16:30:00"
}
```

Durante os retries também é utilizado o header:

```text
x-retry-count
```

para controlar a quantidade de tentativas realizadas.

## Próximas melhorias

Algumas evoluções possíveis para o projeto:

- criar botão para reenviar mensagens da DLQ;
- exibir detalhes da mensagem de erro na interface;
- adicionar histórico de tentativas;
- utilizar Publisher Confirms;
- adicionar logs estruturados;
- criar múltiplos Consumers;
- testar processamento paralelo;
- implementar Exchanges;
- estudar Direct, Fanout e Topic Exchanges;
- utilizar variáveis de ambiente para configurações;
- executar RabbitMQ através de Docker;
- adicionar autenticação na aplicação.

## Autor

**Vitor Hugo**

GitHub: `Vitor99x`

Projeto desenvolvido para estudo de RabbitMQ, mensageria e processamento assíncrono com PHP.
