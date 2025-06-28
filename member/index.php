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
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($id) {
            // Jika ada parameter id, ambil data berdasarkan id
            $sql = "SELECT id, nama, noTelpon AS telepon, tanggalDaftar AS sejak FROM member WHERE id = $id AND status = 1 LIMIT 1";
        } else {
            // Jika tidak ada parameter id, ambil semua data
            $sql = "SELECT id, nama, noTelpon AS telepon, tanggalDaftar AS sejak FROM member WHERE status = 1";
        }

        $result = mysqli_query($db, $sql);
        $member = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $response = [
            'code' => 200,
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $member
        ];
        echo json_encode($response);
        break;

    case 'POST':
        // LOGIKA UNTUK MEMBUAT DATA BARU (INSERT)
        $data = json_decode(file_get_contents("php://input"));

        // Validasi input sesuai struktur DB (nama, email, password adalah wajib)
        if (empty($data->nama) || empty($data->noTelpon) || empty($data->password)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'Data tidak lengkap. Nama, No Telp, dan password wajib diisi.'
            ]);
            exit();
        }

        try {
            // 1. SQL yang BENAR: Gunakan placeholder '?'
            // Sesuaikan urutan kolom dengan VALUES
            $sql = "INSERT INTO member (nama, password, noTelpon) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);

            // Lakukan sanitasi pada input
            $nama = htmlspecialchars(strip_tags($data->nama));
            $noTelpon = htmlspecialchars(strip_tags($data->noTelpon));

            // noTelpon bersifat opsional, beri nilai null jika tidak ada
            $noTelpon = !empty($data->noTelpon) ? htmlspecialchars(strip_tags($data->noTelpon)) : null;

            // Hash password sebelum disimpan
            $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);

            // 2. Binding Parameter yang BENAR: Gunakan bind_param("tipe_data", variabel...)
            // s = string. Jumlah 's' harus sama dengan jumlah '?'. Urutan variabel harus sama.
            $stmt->bind_param("sss", $nama, $hashed_password, $noTelpon);

            // 3. Eksekusi
            if ($stmt->execute()) {
                http_response_code(201); // Created
                echo json_encode([
                    'code' => '201',
                    'status' => 'error',
                    'message' => 'Register berhasil, silakan login.',
                ]);
            } else {
                // Jika execute gagal karena alasan lain
                throw new Exception("Gagal mendaftarkan member.");
            }
        } catch (Exception $e) {
            // Cek jika error karena email duplikat (error code 1062 untuk UNIQUE constraint)
            if ($db->errno == 1062) {
                http_response_code(409); // Conflict
                echo json_encode([
                    'code' => '201',
                    'status' => 'error',
                    'message' => 'No Telp sudah terdaftar.'
                ]);
            } else {
                // Untuk semua error server lainnya
                http_response_code(500);
                echo json_encode([
                    'code' => '500',
                    'status' => 'error',
                    'message' => 'Terjadi kegagalan pada server: ' . $e->getMessage()
                ]);
            }
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->id) || empty($data->nama) || empty($data->noTelpon)) {
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'ID, nama, dan noTelpon wajib diisi.'
            ]);
            exit();
        }

        try {
            $id = intval($data->id);
            $nama = htmlspecialchars(strip_tags($data->nama));
            $noTelpon = htmlspecialchars(strip_tags($data->noTelpon));

            if (!empty($data->password)) {
                $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
                $sql = "UPDATE member SET nama = ?, noTelpon = ?, password = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssi", $nama, $noTelpon, $hashed_password, $id);
            } else {
                $sql = "UPDATE member SET nama = ?, noTelpon = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ssi", $nama, $noTelpon, $id);
            }

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Data member berhasil diperbarui.'
                ]);
            } else {
                throw new Exception("Gagal memperbarui data.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'code' => 500,
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat update: ' . $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->id)) {
            if (empty($data->idDetailBooking)) {
                http_response_code(400);
                echo json_encode([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'IdDetailBooking wajib diisi.'
                ]);
                exit();
            } else {
                $idDetailBooking = intval($data->idDetailBooking);
                $sql = "DELETE FROM detailbooking WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $idDetailBooking);
                
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode([
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'Booking dibatalkan'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'code' => 500,
                        'status' => 'error',
                        'message' => 'Galat membatalkan booking'
                    ]);
                }
            }
        } else {
            $id = intval($data->id);

            $sql = "UPDATE member SET status = 0 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Member berhasil dihapus.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan saat menghapus member.'
                ]);
            }
        }
    break;    

    default:
        http_response_code(405);
        echo json_encode([
            'code' => '405',
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
}
