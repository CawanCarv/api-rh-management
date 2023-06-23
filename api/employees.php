<?php
require_once("../index.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $number_of_rows = $conn->query("SELECT count(*) FROM employees")->fetchColumn();
    
    if($number_of_rows == 0){
        $data = ["message" => "No registered employees."];
        send_response($data, 404);
        exit();
    }

    if(isset($_GET['id'])){
        $stmt = $conn->prepare("SELECT id, name, department_id FROM employees WHERE id = :id");

        $id = $_GET['id'];

        $stmt->bindParam(':id', $id);

        $stmt->execute();

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!empty($employee)) {
            send_response($employee, 200);
            exit();
        }

        $data = [
            "message" => "Employee not found."
        ];
        send_response($data, 404);
        exit();
    }

    $employees = $conn->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);

    send_response($employees, 200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if(!isset($data['name']) || !isset($data['department_id']) ||
    strlen($data['name']) > 254 || !is_numeric($data['department_id'])){
        send_response(["message" => "Employee is not valid."], 400);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM departments WHERE id = :id");

    $id = $data['department_id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($department)) {
        send_response(["message" => "Department not found."], 400);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO employees (name, department_id) VALUES (:name, :department_id)");
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':department_id', $data['department_id']);
    $stmt->execute();

    $employee = [
        "id" => $conn->lastInsertId(),
        "employee" => $data['name']
    ];

    send_response($employee, 200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if(!isset($data['id']) || !isset($data['name']) || !isset($data['department_id']) ||
    !is_numeric($data['id']) || strlen($data['name']) > 254 || !is_numeric($data['department_id'])){
        send_response(["message" => "Employee is not valid."], 400);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, department_id FROM employees WHERE id = :id");

    $id = $data['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($employee)) {
        send_response(["message" => "Employee not found."], 400);
        exit();
    }

    if($employee["department_id"] != $data["department_id"]){
        $stmt = $conn->prepare("SELECT id FROM departments WHERE id = :id");

        $id = $data['department_id'];

        $stmt->bindParam(':id', $id);

        $stmt->execute();

        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($department)) {
            send_response(["message" => "Department not found."], 400);
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE employees SET name = :name, department_id = :department_id WHERE id = :id");
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':department_id', $data['department_id']);
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();

    send_response(["message" => "Employee updated."], 200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if(!isset($_GET['id'])){
        send_response(["message" => "Select an employee to remove."], 400);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM employees WHERE id = :id");

    $id = $_GET['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $removedRows = $stmt->rowCount();

    if($removedRows == 0){
        send_response(["message" => "Employee not found."], 400);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM payments WHERE employee_id = :id");

    $id = $_GET['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    send_response(["message" => "Employee deleted."], 200);
    exit();
}

send_response(["message" => "Method not Allowed"], 405);
exit();
