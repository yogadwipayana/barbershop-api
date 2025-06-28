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
    case 'GET':
        try {
            $sql = 'SELECT 
        (SELECT COUNT(*) FROM member) AS totalMember,
        (SELECT COUNT(*) FROM booking) AS totalBooking,
        (SELECT COUNT(*) FROM transaksi) AS totalTransaksi,
        (SELECT SUM(grandTotal) FROM transaksi) AS totalPenjualan';
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();

            $response = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $data
            ];
            echo json_encode($response);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'code' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
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
            $sql = 'SELECT id, nama, password FROM admin WHERE nama= ?';
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmt->bind_param('s', $nama);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if ($admin && password_verify($password, $admin['password'])) {
                http_response_code(200);
                echo json_encode([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Login berhasil.',
                    'data' => [
                        'id' => $admin['id'],
                        'nama' => $admin['nama']
                    ]
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

    case 'DELETE':
        $data = json_decode((file_get_contents("php://input")));
        $idPegawai = $data->idPegawai ?? null;

        if (empty($idPegawai)) {
            http_response_code(400);
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'idPegawai tidak boleh kosong.'
            ]);
            exit();
        }

        try {
            $sql = 'UPDATE pegawai SET status = 0 WHERE id = ?';
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmt->bind_param('i', $idPegawai);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Delete berhasil'
                ]);
            } else {
                throw new Exception("Gagal menghapus pegawai: " . $stmt->error);
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
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->id) || empty($data->nama) || empty($data->password)) {
            http_response_code(400);
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'ID, nama, dan password wajib diisi.'
            ]);
            exit();
        }

        try {
            $id = $data->id;
            $nama = htmlspecialchars(strip_tags($data->nama));
            $password = htmlspecialchars(strip_tags($data->password));
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $sql = "UPDATE admin SET nama = ?, password = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmt->bind_param("ssi", $nama, $hashed_password, $id);
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Data admin berhasil diperbarui.'
                ]);
            } else {
                throw new Exception("Gagal memperbarui data admin: " . $stmt->error);
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
