<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

// Permitir solicitudes de cualquier origen (cuidado en producción)
header('Access-Control-Allow-Origin: *');
// Permitir los métodos que la API acepta
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// Permitir las cabeceras que el cliente puede enviar
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// Responder a solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}


date_default_timezone_set('America/Mexico_City');

$app = AppFactory::create();

// Ruta base (GET /) para comprobar el estado del servidor
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("API Reloj Checador");
    return $response;
});

// Ruta POST para la lógica del reloj checador
$app->post('/check', function (Request $request, Response $response, $args) {
    $db_host = "junction.proxy.rlwy.net";
    $db_user = "root";
    $db_password = "TUBqFlTPLarICfEoVfJnyDQWuBFmviur";
    $db_name = "reloj_checador";
    $db_port = 16510;

    $conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);

    if ($conn->connect_error) {
        $response->getBody()->write(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $data = json_decode($request->getBody()->getContents(), true);
    $userId = $data['userId'] ?? null;

    if (!$userId) {
        $response->getBody()->write(json_encode(["error" => "ID no proporcionado."]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $date = date('Y-m-d');
    $time = date('Y-m-d H:i:s');

    try {
        $stmt = $conn->prepare("SELECT * FROM empleados WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            $response->getBody()->write(json_encode(["message" => "ID no válido. El empleado no está registrado."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $conn->prepare(
            "SELECT * FROM registros WHERE user_id = ? AND DATE(hora_entrada) = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param("is", $userId, $date);
        $stmt->execute();
        $registro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$registro) {
            $stmt = $conn->prepare(
                "INSERT INTO registros (user_id, hora_entrada, contador_entrada) VALUES (?, ?, 1)"
            );
            $stmt->bind_param("is", $userId, $time);
            $stmt->execute();
            $stmt->close();

            $response->getBody()->write(json_encode(["message" => "Entrada registrada exitosamente.", "hora_entrada" => $time]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if (is_null($registro['hora_salida']) && $registro['contador_salida'] == 0) {
            $stmt = $conn->prepare("UPDATE registros SET contador_salida = 1 WHERE id = ?");
            $stmt->bind_param("i", $registro['id']);
            $stmt->execute();
            $stmt->close();

            $response->getBody()->write(json_encode(["message" => "Tu salida a comer ha sido permitida."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if ($registro['contador_salida'] == 1 && $registro['contador_entrada'] == 1) {
            $stmt = $conn->prepare("UPDATE registros SET contador_entrada = 2 WHERE id = ?");
            $stmt->bind_param("i", $registro['id']);
            $stmt->execute();
            $stmt->close();

            $response->getBody()->write(json_encode(["message" => "Tu regreso a trabajar ha sido registrado."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if ($registro['contador_salida'] == 1 && $registro['contador_entrada'] == 2 && is_null($registro['hora_salida'])) {
            $stmt = $conn->prepare("UPDATE registros SET hora_salida = ? WHERE id = ?");
            $stmt->bind_param("si", $time, $registro['id']);
            $stmt->execute();
            $stmt->close();

            $response->getBody()->write(json_encode(["message" => "Salida final registrada exitosamente.", "hora_salida" => $time]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $response->getBody()->write(json_encode(["message" => "Acción no permitida. Revisa horairo de jornada."]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    } finally {
        $conn->close();
    }
});

$app->run();
