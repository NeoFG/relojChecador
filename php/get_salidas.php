<?php
$servername = "sql102.infinityfree.com";
$username = "if0_37571050";
$password = "E4UhVXHquI6bkf";
$dbname = "if0_37571050_checador";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener todas las salidas registradas
$sql = "SELECT user_id, hora_salida FROM registros WHERE hora_salida IS NOT NULL";
$result = $conn->query($sql);

$salidas = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $salidas[] = $row;
    }
}

echo json_encode($salidas);

$conn->close();
?>
