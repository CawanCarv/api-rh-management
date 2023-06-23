<?php
require_once("../index.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $number_of_rows = $conn->query("SELECT count(*) FROM departments")->fetchColumn();

    if($number_of_rows == 0){
        $data = ["message" => "No registered departments."];
        send_response($data, 404);
        exit();
    }

    if (isset($_GET['id'])) {        
        $stmt = $conn->prepare("SELECT id, name FROM departments WHERE id = :id");

        $id = $_GET['id'];

        $stmt->bindParam(':id', $id);

        $stmt->execute();

        $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!empty($department)) {
            send_response($department, 200);
            exit();
        }
    
        $data = [
            "message" => "Department not found."
        ];
        send_response($data, 404);
        exit();
    }

    $departments = $conn->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

    send_response($departments, 200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if(!isset($data['name']) || strlen($data['name']) > 30){
        send_response(["message" => "Department is not valid."], 400);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (:name)");
    $stmt->bindParam(':name', $data['name']);
    $stmt->execute();

    $department = [
        "id" => $conn->lastInsertId(),
        "department" => $data['name']
    ];

    send_response($department, 200);
    exit();
    
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if(!isset($data['id']) || !isset($data['name']) ||
    !is_numeric($data['id']) || strlen($data['name']) > 30){
        send_response(["message" => "Department is not valid."], 400);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM departments WHERE id = :id");

    $id = $data['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($department)) {
        send_response(["message" => "Department not found."], 400);
        exit();
    }

    $stmt = $conn->prepare("UPDATE departments SET name = :name WHERE id = :id");
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();

    send_response(["message" => "Department updated."], 200);
    exit();

}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if(!isset($_GET['id'])){
        send_response(["message" => "Select an department to remove."], 400);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM departments WHERE id = :id");

    $id = $_GET['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $removedRows = $stmt->rowCount();

    if($removedRows == 0){
        send_response(["message" => "Department not found."], 400);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM employees WHERE department_id = :id");

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($employees as $row) {
        $stmt = $conn->prepare("DELETE FROM payments WHERE employee_id = :id");

        $id = $row['id'];

        $stmt->bindParam(':id', $id);

        $stmt->execute();
    }

    $stmt = $conn->prepare("DELETE FROM employees WHERE department_id = :id");

    $id = $_GET['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    send_response(["message" => "Department deleted."], 200);
    exit();
}

send_response(["message" => "Method not Allowed"], 405);
exit();