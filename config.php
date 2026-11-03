<?php

$mysqli = new mysqli('localhost','root','','rabbitmq_lab');

if ($mysqli->connect_error) {

    die('Erro ao conectar no banco: '. $mysqli->connect_error);

}

$mysqli->set_charset('utf8mb4');