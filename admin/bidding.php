<?php
include '../database.php';
session_start();

date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu ke WIB

$id_item = $_GET['id'];

$message = ""; // Variabel untuk menyimpan pesan
$timeout = 300; // waktu dalam detik (5 menit)

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

// Logika timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset(); // Hapus semua variabel sesi 
    session_destroy(); // Hancurkan sesi 
    $message = "Sesi telah berakhir karena tidak ada aktivitas selama 5 menit.";
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '../login.php';</script>";
    exit;
}
$_SESSION['last_activity'] = time(); // Perbarui waktu aktivitas terakhir

// Logika logout
if (isset($_GET['logout'])) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    $message = "Log out berhasil.";
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '../login.php';</script>";
    exit;
}

// Ambil data barang
$sql = "SELECT * FROM item WHERE id = $id_item";
$result = $conn->query($sql);
$item = $result->fetch_assoc();

// Ambil data penawaran tertinggi dan nama pengguna
$sql2 = "SELECT bid.*, user.nama AS nama_user FROM bid JOIN user ON bid.user_id = user.id WHERE bid.item_id = $id_item ORDER BY bid.harga_tawaran DESC";
$result2 = $conn->query($sql2);
$bid = $result2->fetch_assoc(); // Mengambil data dari $result2
$bid_terbesar = isset($bid['harga_tawaran']) ? $bid['harga_tawaran'] : 0; // Periksa apakah $bid['harga_tawaran'] null
$nama_penawar_terbesar = isset($bid['nama_user']) ? $bid['nama_user'] : 'Belum Ada Penawar';

// Ambil data penawaran pengguna saat ini
$sql3 = "SELECT * FROM bid WHERE item_id = $id_item AND user_id = {$_SESSION['id']} ORDER BY harga_tawaran DESC";
$result3 = $conn->query($sql3);
$bid_user = $result3->fetch_assoc();
$bid_user_terbesar = isset($bid_user['harga_tawaran']) ? $bid_user['harga_tawaran'] : 0; // Periksa apakah $bid_user['harga_tawaran'] null

// Tentukan status_bid
$current_time = date("Y-m-d H:i:s");
$batas_waktu = $item['batas_waktu'];
$status_bid = (strtotime($current_time) < strtotime($batas_waktu)) ? 'Masih Berlaku' : 'Tidak Berlaku';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Lelang</title>
    <link rel="stylesheet" href="../asset/style.css">
    <link rel="icon" href="../asset/logo.png" type="image/x-icon">
    <script>
        var timeout = 300000; // waktu dalam milidetik (5 menit) 
        var logoutTimer;

        function resetTimer() {
            clearTimeout(logoutTimer);
            logoutTimer = setTimeout(logout, timeout);
        }

        function logout() {
            window.location.href = '?logout=true'; // arahkan ke halaman logout 
        }
        document.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
    </script>
</head>

<body>
    <div class="content-wrapper">
        <div class="content-card">
            <h1>Detail Bidding Item</h1>
            <img src="<?php echo $item['gambar']; ?>" alt="<?php echo $item['gambar']; ?>">
            <form method="post" enctype='multipart/form-data' action="tawar.php?id=<?php echo $id_item; ?>">
                <label for="nama">Nama Item</label>
                <input type="text" name="nama" placeholder="<?php echo $item['nama'] ?>" readonly>
                <label for="deskripsi">Deskripsi Item</label>
                <textarea name="deskripsi" readonly><?php echo $item['deskripsi'] ?></textarea>
                <label for="harga_minimal">Minimal Bid</label>
                <input type="number" name="harga_minimal" placeholder="Rp <?php echo number_format($item['harga_minimal'], 2, ',', '.'); ?>" readonly>
                <label for="penawar_terbesar">Penawar Terbesar (Refresh setiap saat untuk melihat perkembangan terbaru!)</label>
                <input type="text" name="penawar_terbesar" placeholder="<?php echo $nama_penawar_terbesar ?>" readonly>
                <label for="bid_terbesar">Bid Terbesar Saat Ini (Refresh setiap saat untuk melihat perkembangan terbaru!)</label>
                <input type="number" placeholder="Rp <?php echo number_format($bid_terbesar, 2, ',', '.'); ?>" readonly>
                <label for="batas_waktu">Bid Deadline</label>
                <input type="datetime-local" name="batas_waktu" value="<?php echo $item['batas_waktu'] ?>" readonly>
                <label for="status_bid">Status Bid</label>
                <input type="text" value="<?php echo $status_bid ?>" readonly>
            </form>
            <div class="link">
                <?php if ($status_bid == 'Tidak Berlaku'): ?>
                    <a href="pemenang.php?id=<?php echo $item['id']; ?>">Pemenang Lelang</a>
                <?php endif; ?>
                <a href="riwayatBidding.php?id=<?php echo $item['id']; ?>">Riwayat Bidding</a>
                <a href="index.php">Kembali</a>
            </div>
        </div>
    </div>
</body>

</html>