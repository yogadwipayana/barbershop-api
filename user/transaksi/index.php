<?php
require_once dirname(__DIR__, 2) . '/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->idPegawai) || empty($data->idBooking) || empty($data->grandTotal)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'Data tidak lengkap. idPegawai, idBooking, grandTotal wajib diisi.'
            ]);
            exit();
        }
        $idPegawai = htmlspecialchars($data->idPegawai);
        $idBooking = htmlspecialchars($data->idBooking);
        $grandTotal = htmlspecialchars($data->grandTotal);

        try {
            $sqlIdBooking = 'SELECT id FROM booking WHERE id = ?';
            $stmtIdBooking = $db->prepare($sqlIdBooking);
            if ($stmtIdBooking === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmtIdBooking->bind_param("i", $idBooking);
            if ($stmtIdBooking->execute()) {
                $resultIdBooking = $stmtIdBooking->get_result();
                if ($resultIdBooking->num_rows === 0) {
                    http_response_code(404);
                    echo json_encode([
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'ID Booking tidak ditemukan'
                    ]);
                    exit();
                } else {
                    $sql = 'INSERT INTO transaksi (idPegawai, idBooking, grandTotal) VALUES (?, ?, ?)';
                    $stmt = $db->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception("Gagal mempersiapkan statement: " . $db->error);
                    }
                    $stmt->bind_param("iis", $idPegawai, $idBooking, $grandTotal);

                    if ($stmt->execute()) {
                        $sqlBooking = 'UPDATE booking SET status = "selesai" WHERE id = ?';
                        $stmtBooking = $db->prepare($sqlBooking);
                        if ($stmtBooking === false) {
                            throw new Exception("Gagal mempersiapkan statement: " . $db->error);
                        }
                        $stmtBooking->bind_param("i", $idBooking);
                        if ($stmtBooking->execute()) {
                            http_response_code(200);
                            echo json_encode([
                                'code' => 200,
                                'status' => 'success',
                                'message' => 'Transaksi sukses',
                            ]);
                        }
                    }
                }
            } else {
                throw new Exception("Gagal mengeksekusi query: " . $stmtIdBooking->error);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'code' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'GET':

        try {
            $sql = 'SELECT b.id, m.nama AS namaMember, b.tanggal, b.waktu, l.nama AS layanan, b.status FROM booking b INNER JOIN member m ON b.idMember = m.id INNER JOIN detailbooking db ON b.id = db.idBooking INNER JOIN layanan l ON db.idLayanan = l.id ORDER BY b.tanggal ASC';
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($data) {
                    http_response_code(200);
                    echo json_encode([
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'Data berhasil diambil',
                        'data' => $data
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'Data tidak ditemukan'
                    ]);
                }
            } else {
                throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
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
}
