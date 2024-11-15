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

// Obtener todas las entradas que aún no tienen salida
$sql = "SELECT user_id, hora_entrada FROM registros WHERE hora_salida IS NULL";
$result = $conn->query($sql);

$entradas = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $entradas[] = $row;
    }
}

echo json_encode($entradas);

$conn->close();
?>
