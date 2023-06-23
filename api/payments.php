<?php
require_once("../index.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $number_of_rows = $conn->query("SELECT count(*) FROM payments")->fetchColumn();

    if($number_of_rows == 0){
        $data = ["message" => "No registered payments."];
        send_response($data, 404);
        exit();
    }
    
    if (isset($_GET['id'])) {      
        $stmt = $conn->prepare("SELECT id, employee_id, amount FROM payments WHERE id = :id");

        $id = $_GET['id'];

        $stmt->bindParam(':id', $id);

        $stmt->execute();

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($payment)) {
            send_response($payment, 200);
            exit();
        }

        $data = [
            "message" => "Payment not found."
        ];
        send_response($data, 404);
        exit();
    }

    $payments = $conn->query("SELECT * FROM payments")->fetchAll(PDO::FETCH_ASSOC);

    send_response($payments, 200);
    exit();
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if(!isset($data['amount']) || !isset($data['employee_id']) || 
    !is_numeric($data['amount']) || !is_numeric($data['employee_id'])){
        send_response(["message" => "Payment is not valid."], 400);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM employees WHERE id = :id");

    $id = $data['employee_id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($employee)) {
        send_response(["message" => "Employee not found."], 400);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO payments (amount, employee_id) VALUES (:amount, :employee_id)");
    $stmt->bindParam(':amount', $data['amount']);
    $stmt->bindParam(':employee_id', $data['employee_id']);
    $stmt->execute();

    $payment = [
        "id" => $conn->lastInsertId(),
        "employee_id" => $data['employee_id'],
        "payment" => $data['amount']
    ];

    send_response($payment, 200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if(!isset($data['id']) || !isset($data['amount']) || !isset($data['employee_id']) ||
    !is_numeric($data['id']) || !is_numeric($data['amount']) || !is_numeric($data['employee_id'])){
        send_response(["message" => "Payment is not valid."], 400);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, employee_id FROM payments WHERE id = :id");

    $id = $data['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($payment)) {
        send_response(["message" => "Payment not found."], 400);
        exit();
    }

    if($payment["employee_id"] != $data["employee_id"]){
        $stmt = $conn->prepare("SELECT id FROM employees WHERE id = :id");

        $id = $data['employee_id'];

        $stmt->bindParam(':id', $id);

        $stmt->execute();

        $employees = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($employees)) {
            send_response(["message" => "Employee not found."], 400);
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE payments SET amount = :amount, employee_id = :employee_id WHERE id = :id");
    $stmt->bindParam(':amount', $data['amount']);
    $stmt->bindParam(':employee_id', $data['employee_id']);
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();

    send_response(["message" => "Payment updated."], 200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if(!isset($_GET['id'])){
        send_response(["message" => "Select an payment to remove."], 400);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM payments WHERE id = :id");

    $id = $_GET['id'];

    $stmt->bindParam(':id', $id);

    $stmt->execute();

    $removedRows = $stmt->rowCount();

    if($removedRows == 0){
        send_response(["message" => "Payment not found."], 400);
        exit();
    }

    send_response(["message" => "Payment deleted."], 200);
    exit();

}

send_response(["message" => "Method not Allowed"], 405);
exit();
