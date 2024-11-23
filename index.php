<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php'; // Incluye la clase Db

// Configurar la zona horaria a la de México
date_default_timezone_set('America/Mexico_City');

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

$app = AppFactory::create();

// Ruta base (GET /) para comprobar el estado del servidor
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("API Reloj Checador");
    return $response;
});

// Ruta POST para la lógica del reloj checador
$app->post('/check', function (Request $request, Response $response, $args) {
    $db = new Db(); // Usa la clase Db para la conexión
    $conn = $db->connect();

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
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $response->getBody()->write(json_encode(["message" => "Por favor, ingrese un ID válido."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $conn->prepare(
            "SELECT * FROM registros WHERE user_id = ? AND DATE(hora_entrada) = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $date, PDO::PARAM_STR);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            $stmt = $conn->prepare(
                "INSERT INTO registros (user_id, hora_entrada, contador_entrada) VALUES (?, ?, 1)"
            );
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $time, PDO::PARAM_STR);
            $stmt->execute();

            $response->getBody()->write(json_encode(["message" => "Entrada registrada exitosamente.", "hora_entrada" => $time]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if (is_null($registro['hora_salida']) && $registro['contador_salida'] == 0) {
            $stmt = $conn->prepare("UPDATE registros SET contador_salida = 1 WHERE id = ?");
            $stmt->bindParam(1, $registro['id'], PDO::PARAM_INT);
            $stmt->execute();

            $response->getBody()->write(json_encode(["message" => "Tu salida a comer ha sido permitida."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if ($registro['contador_salida'] == 1 && $registro['contador_entrada'] == 1) {
            $stmt = $conn->prepare("UPDATE registros SET contador_entrada = 2 WHERE id = ?");
            $stmt->bindParam(1, $registro['id'], PDO::PARAM_INT);
            $stmt->execute();

            $response->getBody()->write(json_encode(["message" => "Tu regreso a trabajar ha sido registrado."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if ($registro['contador_salida'] == 1 && $registro['contador_entrada'] == 2 && is_null($registro['hora_salida'])) {
            $stmt = $conn->prepare("UPDATE registros SET hora_salida = ? WHERE id = ?");
            $stmt->bindParam(1, $time, PDO::PARAM_STR);
            $stmt->bindParam(2, $registro['id'], PDO::PARAM_INT);
            $stmt->execute();

            $response->getBody()->write(json_encode(["message" => "Salida final registrada exitosamente.", "hora_salida" => $time]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $response->getBody()->write(json_encode(["message" => "Acción no permitida. Revisa horario de jornada."]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->run();