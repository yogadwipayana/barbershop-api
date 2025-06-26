<?php
require_once dirname(__DIR__, 2) . '/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (empty($_GET['idBooking'])) {
            http_response_code(200);
            echo json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'idBooking tidak boleh kosong'
            ]);
            exit();
        }

        try {
            $idBooking = $_GET['idBooking'];
            $sql = "SELECT db.idBooking, db.subTotal FROM detailbooking db WHERE db.idBooking = ?";
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmt->bind_param("i", $idBooking);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    if ($result->num_rows > 1) {
                        $total = 0;

                        while ($row = $result->fetch_assoc()) {
                            $total += (float)$row['subTotal'];
                        }

                        // Format ke Rupiah: Rp 250.000,00
                        $rupiah = number_format($total, 2, ',', '.');

                        http_response_code(200);
                        echo json_encode([
                            'code' => 200,
                            'status' => 'success',
                            'message' => 'Total SubTotal',
                            'data' => [
                                'subTotal' => $total
                            ]
                        ]);
                    } else {
                        $row = $result->fetch_assoc();
                        $total = (float)$row['subTotal'];

                        // Format ke Rupiah: Rp 250.000,00
                        $rupiah = number_format($total, 2, ',', '.');
                        http_response_code(200);
                        echo json_encode([
                            'code' => 200,
                            'status' => 'success',
                            'message' => 'SubTotal',
                            'data' => [
                                'subTotal' => $total
                            ]
                        ]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'idBooking tidak ditemukan'
                    ]);
                }
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
}
