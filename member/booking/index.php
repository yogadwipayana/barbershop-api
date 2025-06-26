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

        if (empty($data->idMember) || empty($data->idLayanan) || empty($data->tanggal) || empty($data->jam)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'code' => '400',
                'status' => 'error',
                'message' => 'Data tidak lengkap. idMember, idLayanan, tanggal, jam wajib diisi.'
            ]);
            exit();
        }
        $idMember = htmlspecialchars($data->idMember);
        $layanan = $data->idLayanan;
        $tanggal = htmlspecialchars($data->tanggal);
        $jam = htmlspecialchars($data->jam);

        try {
            // 1. Mengambil id booking berikutnya
            $sqlIdBooking = 'SELECT id + 1 AS idBooking, idMember, tanggal, waktu from booking WHERE id =(SELECT max(id) from booking)';
            $stmtIdBooking = $db->prepare($sqlIdBooking);
            if ($stmtIdBooking === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmtIdBooking->execute();
            $resultIdBooking = $stmtIdBooking->get_result();
            $idBooking = $resultIdBooking->fetch_assoc();

            //2. Insert data booking
            //if. Member memilih 2 layanan sekaligus
            //else. Member hanya memilih 1 layanan 
            $sqlCekBooking = 'SELECT id AS idBooking FROM booking WHERE idMember = ? AND tanggal = ? AND waktu = ?';
            $stmtCekBooking = $db->prepare($sqlCekBooking);
            if ($stmtCekBooking === false) {
                throw new Exception("Gagal mempersiapkan statement: " . $db->error);
            }
            $stmtCekBooking->bind_param("sss", $idMember, $tanggal, $jam);
            $stmtCekBooking->execute();
            $resultCekBooking = $stmtCekBooking->get_result();
            $dataBooking = $resultCekBooking->fetch_assoc();

            if ($dataBooking) {
                //tidak menambahkan data booking lagi karena member, tanggal, dan jam nya sama
                $bookingBaru = false;
            } else {
                $bookingBaru = true;
                $sqlBooking = 'INSERT INTO booking (id, idMember, tanggal, waktu) VALUES (?, ?, ?, ?)';
                $stmtBooking = $db->prepare($sqlBooking);
                if ($stmtBooking === false) {
                    throw new Exception("Gagal mempersiapkan statement: " . $db->error);
                }
                $stmtBooking->bind_param("ssss", $idBooking['idBooking'], $idMember, $tanggal, $jam);
                $stmtBooking->execute();
            }

            //3. Loop semua layanan
            foreach ($layanan as $idLayanan) {
                $idLayanan = htmlspecialchars($idLayanan);

                $sqlLayanan = 'SELECT harga AS subTotal FROM layanan WHERE id = ?';
                $stmtLayanan = $db->prepare($sqlLayanan);
                $stmtLayanan->bind_param("i", $idLayanan);
                if ($stmtLayanan === false) {
                    throw new Exception("Gagal mempersiapkan statement: " . $db->error);
                }
                $stmtLayanan->execute();
                $resultLayanan = $stmtLayanan->get_result();
                $hargaLayanan = $resultLayanan->fetch_assoc();

                $sqlDetailBooking = 'INSERT INTO detailbooking (idBooking, idLayanan, subTotal) VALUES (?, ?, ?)';
                $stmtDetailBooking = $db->prepare($sqlDetailBooking);
                if ($stmtDetailBooking === false) {
                    throw new Exception("Gagal mempersiapkan statement: " . $db->error);
                }
                $stmtDetailBooking->bind_param("iis", $idBooking['idBooking'], $idLayanan, $hargaLayanan['subTotal']);
                $stmtDetailBooking->execute();
            }

            //4. Response 
            http_response_code(200);
            echo json_encode([
                'code' => 200,
                'status' => 'success',
                'message' => $bookingBaru ? 'Booking baru berhasil' : 'Layanan berhasil ditambahkan ke booking yang sama',
            ]);
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
        //1. Ambil idMember
        $idMember = isset($_GET['id']) ? intval($_GET['id']) : null;

        //2. Query ke database
        //if. data id ditemukan
        //else. id tidak ada lalu response error
        try {
            if ($idMember) {
                $sql = 'SELECT db.id, l.nama, b.tanggal, b.waktu, b.status FROM detailbooking db 
                    INNER JOIN layanan l ON db.idLayanan = l.id 
                    INNER JOIN booking b ON db.idBooking = b.id
                    INNER JOIN member m ON b.idMember = m.id
                    WHERE b.idMember = ? 
                    ORDER BY b.tanggal DESC ';
            } else {
                http_response_code(405);
                echo json_encode([
                    'code' => '404',
                    'status' => 'error',
                    'message' => 'id tidak ditemukan'
                ]);
                exit();
            }

            //3. Eksekusi query
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal mempersiapkan statement " . $db->error);
            }
            $stmt->bind_param("i", $idMember);
            $stmt->execute();
            $resultBooking = $stmt->get_result();
            $dataBooking = [];
            while ($row = $resultBooking->fetch_assoc()) {
                $dataBooking[] = $row;
            }

            //4. Loop data booking
            $data = [];
            $bulanIndo = [
                '01' => 'Januari',
                '02' => 'Februari',
                '03' => 'Maret',
                '04' => 'April',
                '05' => 'Mei',
                '06' => 'Juni',
                '07' => 'Juli',
                '08' => 'Agustus',
                '09' => 'September',
                '10' => 'Oktober',
                '11' => 'November',
                '12' => 'Desember'
            ];
            foreach ($dataBooking as $row) {
                // $tgl = date('d', strtotime($row['tanggal']));
                // $bln = date('m', strtotime($row['tanggal']));
                // $thn = date('Y', strtotime($row['tanggal']));

                // $formatIndo = $tgl . ' ' . $bulanIndo[$bln] . ' ' . $thn;
                $formatBaru = date('d-m-Y', strtotime($row['tanggal']));
                $data[] = [
                    'idDetailBooking' => $row['id'],
                    'namaLayanan' => $row['nama'],
                    'tanggalBooking' => $formatBaru,
                    'waktuBooking' => $row['waktu'],
                    'status' => $row['status']
                ];
            }

            http_response_code(200);
            echo json_encode(
                [
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'data booking ditemukan',
                    'data' => $data
                ]
            );
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'code' => 500,
                'status' => 'error',
                'message' => $e->getMessage()
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
