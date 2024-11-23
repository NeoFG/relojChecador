<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

date_default_timezone_set('America/Mexico_City'); // Ajusta según tu zona horaria

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reloj_checador";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$data = json_decode(file_get_contents("php://input"));
$userId = $data->userId;
$date = date('Y-m-d');
$time = date('Y-m-d H:i:s');

// Verificar si el usuario existe
$sql = "SELECT * FROM empleados WHERE user_id='$userId'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "ID no válido. El empleado no está registrado.";
    $conn->close();
    exit;
}

// Verificar el registro más reciente del usuario en la jornada actual
$sql = "SELECT * FROM registros WHERE user_id='$userId' AND DATE(hora_entrada) = '$date' ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    // Primera entrada de la jornada
    $sql = "INSERT INTO registros (user_id, hora_entrada, contador_entrada) VALUES ('$userId', '$time', 1)";
    if ($conn->query($sql) === TRUE) {
        echo "Entrada registrada exitosamente a las $time.";
    } else {
        echo "Error al registrar la entrada: " . $conn->error;
    }
} else {
    $registro = $result->fetch_assoc();

    if (is_null($registro['hora_salida']) && $registro['contador_salida'] == 0) {
        // Permitir salida a comer
        $sql = "UPDATE registros SET contador_salida = 1 WHERE id=" . $registro['id'];
        if ($conn->query($sql) === TRUE) {
            echo "Tu salida a comer ha sido permitida.";
        } else {
            echo "Error al registrar la salida a comer: " . $conn->error;
        }
    } elseif ($registro['contador_salida'] == 1 && $registro['contador_entrada'] == 1) {
        // Permitir regreso de comida
        $sql = "UPDATE registros SET contador_entrada = 2 WHERE id=" . $registro['id'];
        if ($conn->query($sql) === TRUE) {
            echo "Tu regreso a trabajar ha sido registrado.";
        } else {
            echo "Error al registrar el regreso: " . $conn->error;
        }
    } elseif ($registro['contador_salida'] == 1 && $registro['contador_entrada'] == 2 && is_null($registro['hora_salida'])) {
        // Registrar salida final
        $sql = "UPDATE registros SET hora_salida='$time' WHERE id=" . $registro['id'];
        if ($conn->query($sql) === TRUE) {
            echo "Salida final registrada exitosamente a las $time.";
        } else {
            echo "Error al registrar la salida final: " . $conn->error;
        }
    } else {
        echo "Acción no permitida. Revisa tu horario de jornada.";
    }
}

$conn->close();
?>
