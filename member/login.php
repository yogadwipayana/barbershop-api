<?php
// Memanggil file database dari direktori induk
require_once dirname(__DIR__, 1) . '/database.php';

// Pengaturan Header untuk API
header("Access-Control-Allow-Origin: *"); // Izinkan akses dari semua domain
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Mengambil metode request
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'OPTIONS':
        http_response_code(200);
        break;
    case 'POST':

        $data = json_decode(file_get_contents("php://input"));

        // Validasi input
        if (empty($data->nama) || empty($data->password)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'Nama dan password tidak boleh kosong.'
            ]);
            exit();
        }

        try {
            // 1. Prepare statement dengan placeholder '?'
            $stmt = $db->prepare("SELECT id, nama, password FROM member WHERE nama = ?");
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }

            // 2. Bind parameter ke placeholder
            $stmt->bind_param('s', $data->nama);

            // 3. Eksekusi statement
            $stmt->execute();

            // 4. Dapatkan hasil query
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // 5. Verifikasi user dan password
            if ($user && password_verify($data->password, $user['password'])) {
                // Login Berhasil!
                http_response_code(200);
                echo json_encode([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Login berhasil.',
                    'data' => [
                        'id' => $user['id'],
                        'nama' => $user['nama']
                    ]
                ]);
            } else {
                // Jika user tidak ditemukan atau password salah
                http_response_code(401); // Unauthorized
                echo json_encode([
                    'code' => '401',
                    'status' => 'error',
                    'message' => 'Nama atau password salah.'
                ]);
            }

            $stmt->close();
            $db->close();
        } catch (Exception $e) {
            http_response_code(500); // Internal Server Error
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