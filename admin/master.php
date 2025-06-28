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
            $sql = 'SELECT id, nama, notelpon, alamat FROM pegawai WHERE status = 1 AND id = ?';
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($data) {
                    $response = [
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'Data edit retrieved successfully',
                        'data' => $data
                    ];
                } else {
                    http_response_code(404);
                    $response = [
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'Data not found'
                    ];
                }
            } else {
                http_response_code(500);
                $response = [
                    'code' => 500,
                    'status' => 'error',
                    'message' => $db->error
                ];
            }
            echo json_encode($response);
            exit();
        } else {

            $sql = 'SELECT id, nama, notelpon, alamat FROM pegawai WHERE status = 1';
            try {
                $stmt = $db->prepare($sql);
                if ($stmt === false) {
                    throw new Exception("Gagal mempersiapkan statement: " . $db->error);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }

                $dataPegawai = [];
                foreach ($data as $Pegawai) {
                    $idPegawai = $Pegawai['id'];

                    $sqlTransaksi = 'SELECT COUNT(*) AS jumlahTransaksi FROM transaksi t WHERE t.idPegawai = ?';
                    $stmtTransaksi = $db->prepare($sqlTransaksi);
                    if ($stmtTransaksi === false) {
                        throw new Exception("Gagal mempersiapkan statement transaksi: " . $db->error);
                    }
                    $stmtTransaksi->bind_param('i', $idPegawai);
                    $stmtTransaksi->execute();
                    $resultTransaksi = $stmtTransaksi->get_result();
                    $jumlahTransaksi = $resultTransaksi->fetch_assoc()['jumlahTransaksi'];

                    $dataPegawaiList = [];
                    $dataPegawaiList = [
                        'id' => $Pegawai['id'],
                        'nama' => $Pegawai['nama'],
                        'notelpon' => $Pegawai['notelpon'],
                        'alamat' => $Pegawai['alamat'],
                        'jumlahTransaksi' => $jumlahTransaksi
                    ];

                    $dataPegawai[] = $dataPegawaiList;
                }

                $response = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Data retrieved successfully',
                    'data' => $dataPegawai
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
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->id) || empty($data->nama) || empty($data->noTelpon) || empty($data->alamat)) {
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'ID, nama, noTelpon, dan alamat tidak boleh kosong.'
            ]);
            exit();
        }

        try {
            $id = intval($data->id);
            $nama = htmlspecialchars(strip_tags($data->nama));
            $noTelpon = htmlspecialchars(strip_tags($data->noTelpon));
            $alamat = htmlspecialchars((strip_tags($data->alamat)));

            if (!empty($data->password)) {
                $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
                $sql = "UPDATE pegawai SET nama = ?, noTelpon = ?, alamat = ?, password = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ssssi", $nama, $noTelpon, $alamat, $hashed_password, $id);
            } else {
                $sql = "UPDATE pegawai SET nama = ?, noTelpon = ?, alamat = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssi", $nama, $noTelpon, $alamat, $id);
            }

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Data pegawai berhasil diperbarui.'
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
    default:
        http_response_code(405);
        echo json_encode([
            'code' => 405,
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
        break;
}
