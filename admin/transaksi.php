<?php
require_once dirname(__DIR__, 1) . '/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $sql = 'SELECT t.id, p.nama AS namaPegawai, m.nama AS namaMember, t.tanggal, t.grandTotal FROM transaksi t INNER JOIN booking b ON t.idBooking = b.id INNER JOIN member m ON b.idMember = m.id INNER JOIN pegawai p ON t.idPegawai = p.id';
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $transaksi = [];

                while ($row = $result->fetch_assoc()) {
                    $transaksi[] = [
                        'id' => $row['id'],
                        'namaPegawai' => $row['namaPegawai'],
                        'namaMember' => $row['namaMember'],
                        'tanggal' => $row['tanggal'],
                        'grandTotal' => $row['grandTotal']
                    ];
                }

                http_response_code(200);
                echo json_encode([
                    'code' => '200',
                    'status' => 'success',
                    'data' => $transaksi ?? null
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'code' => '401',
                    'status' => 'error',
                    'message' => 'Galat mengambil data transaksi.'
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
}
