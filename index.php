<?php 
header('Content-Type: application/json');

$hostname = 'localhost';   
$database = 'pdwa5';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Erro na conexão com o banco de dados: ' . $e->getMessage();
}

function send_response($response, $status){
    http_response_code($status);
    if($status >= 400 && $status < 500){
        $response = array(
            "error" => array (
                "status" => $status,
                "data" => $response,
            )
        );
    }

    if($status >= 200 && $status < 300) {
        $response = array(
            "data" => $response,
        );
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

    return;
}