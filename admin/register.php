<?php
require_once dirname(__DIR__, 1) . '/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'OPTIONS':
        http_response_code(200);
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->nama) || empty($data->password)) {
            http_response_code(400);
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'Nama dan password tidak boleh kosong.'
            ]);
            exit();
        }

        $nama = htmlspecialchars($data->nama);
        $password = htmlspecialchars($data->password);

        try {
            $sql = 'INSERT INTO admin (nama, password) VALUES (?, ?)';
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt->bind_param('ss', $nama, $hashed_password);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Register berhasil.',
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'code' => '401',
                    'status' => 'error',
                    'message' => 'Nama atau password salah.'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'code' => '500',
                'status' => 'error',
                'message' => 'Terjadi kegagalan pada server: ' . $e->getMessage()
            ]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode([
            'code' => '405',
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
        break;
}
