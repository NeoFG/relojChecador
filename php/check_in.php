<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

date_default_timezone_set('America/Mexico_City'); // Ajusta según tu zona horaria

$servername = "sql102.infinityfree.com";
$username = "if0_37571050";
$password = "E4UhVXHquI6bkf";
$dbname = "if0_37571050_checador";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$data = json_decode(file_get_contents("php://input"));
$userId = $data->userId;
$date = date('Y-m-d');

// Verificar si el usuario ya ha registrado una entrada hoy
$sql = "SELECT * FROM registros WHERE user_id='$userId' AND DATE(hora_entrada) = '$date'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Ya has registrado tu entrada hoy.";
} else {
    // Registrar la entrada con la zona horaria correcta
    $hora_entrada = date('Y-m-d H:i:s');
    $sql = "INSERT INTO registros (user_id, hora_entrada) VALUES ('$userId', '$hora_entrada')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Entrada registrada exitosamente: $hora_entrada";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
