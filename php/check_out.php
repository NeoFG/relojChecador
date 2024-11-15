<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

date_default_timezone_set('America/Mexico_City'); // Ajusta según tu zona horaria

$servername = "sql102.infinityfree.com"; // Cambia a tu servidor MySQL
$username = "if0_37571050"; // Cambia a tu nombre de usuario MySQL
$password = "E4UhVXHquI6bkf"; // Cambia a tu contraseña MySQL
$dbname = "if0_37571050_checador"; // Cambia a tu base de datos MySQL

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener los datos de la solicitud
$data = json_decode(file_get_contents("php://input"));
$userId = $data->userId;
$date = date('Y-m-d');

// Verificar si el usuario tiene una entrada registrada hoy
$sql = "SELECT * FROM registros WHERE user_id='$userId' AND DATE(hora_entrada) = '$date' AND hora_salida IS NULL";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Registrar la salida
    $hora_salida = date('Y-m-d H:i:s');
    $sql = "UPDATE registros SET hora_salida='$hora_salida' WHERE user_id='$userId' AND DATE(hora_entrada) = '$date'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Salida registrada exitosamente: $hora_salida";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "No tienes una entrada registrada hoy para registrar una salida.";
}

$conn->close();
?>
