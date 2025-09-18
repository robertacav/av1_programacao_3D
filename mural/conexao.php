<?php

// Configurações do banco
$host    = "localhost";   // normalmente não precisa alterar
$usuario = "root";        // substituir se seu usuário não for root
$senha   = "";            // substituir se você tiver senha no MySQL
$banco   = "mural";       // substituir pelo nome do seu banco criado no phpMyAdmin

// Conexão MySQLi
$conexao = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conexao) {
    die("Erro ao conectar: " . mysqli_connect_error());
}

// SENSITIVE CASE suportar acentos e Ç
mysqli_set_charset($conexao, "utf8");
$cloud_name = "drpzca5w3";  // exemplo: "meucloud123"
$api_key    = "537184776887243";     // exemplo: "123456789012345"
$api_secret = "E-WWjDSE1g_vA6HAFC49ZU7cm54";  // exemplo: "abcdeFGHijkLMNopqrstu"

?>