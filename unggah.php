<?php
session_start();

// Password configuration
$correct_password = "unggahmu"; // Change this to your desired password
$password_required = true; // Set to false to disable password protection

// Check if password is submitted and correct
if ($password_required && isset($_POST['password'])) {
    if ($_POST['password'] === $correct_password) {
        $_SESSION['authenticated'] = true;
    } else {
        $password_error = "Password salah!";
    }
}

// If password is required but user isn't authenticated, show password form
if ($password_required && !isset($_SESSION['authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Unggah File</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-container {
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                width: 350px;
                text-align: center;
            }
            h1 {
                color: #2c3e50;
                margin-bottom: 20px;
            }
            .password-input {
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 5px;
                box-sizing: border-box;
            }
            .submit-btn {
                background-color: #3498db;
                color: white;
                border: none;
                padding: 12px;
                border-radius: 5px;
                cursor: pointer;
                width: 100%;
                font-size: 16px;
                transition: background-color 0.3s;
            }
            .submit-btn:hover {
                background-color: #2980b9;
            }
            .error {
                color: #d32f2f;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1>Masukkan Password</h1>
            <?php if (isset($password_error)): ?>
                <div class="error"><?php echo $password_error; ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="password" class="password-input" placeholder="Password" required>
                <button type="submit" class="submit-btn">Masuk</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Konfigurasi upload
$upload_dir = 'dokumen/';
$max_file_size = 50 * 1024 * 1024; // 50 MB
$allowed_file_types = [
    'image' => ['jpg', 'jpeg', 'png', 'gif'],
    'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']
];

// Pesan status
$status = '';
$status_class = '';
$original_filename = '';
$preview = '';

// Buat folder upload jika belum ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Proses upload saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $file = $_FILES['file_upload'];
    $original_filename = basename($file['name']);
    
    // Cek error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi ukuran maksimal yang diizinkan server',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi ukuran maksimal 50 MB',
            UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diunggah',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
        ];
        
        $status = isset($error_messages[$file['error']]) ? 
                 $error_messages[$file['error']] : 
                 'Terjadi kesalahan saat mengunggah file. Error code: ' . $file['error'];
        $status_class = 'error';
    } 
    // Cek ukuran file
    elseif ($file['size'] > $max_file_size) {
        $status = 'Ukuran file terlalu besar. Maksimal 50 MB.';
        $status_class = 'error';
    } else {
        // Dapatkan ekstensi file
        $file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        
        // Cek tipe file
        $file_type = '';
        foreach ($allowed_file_types as $type => $extensions) {
            if (in_array($file_ext, $extensions)) {
                $file_type = $type;
                break;
            }
        }
        
        if (empty($file_type)) {
            $status = 'Format file tidak didukung.';
            $status_class = 'error';
        } else {
            // Gunakan nama file asli
            $destination = $upload_dir . $original_filename;
            
            // Cek jika file sudah ada, tambahkan angka unik
            $counter = 1;
            while (file_exists($destination)) {
                $file_info = pathinfo($original_filename);
                $destination = $upload_dir . $file_info['filename'] . '_' . $counter . '.' . $file_info['extension'];
                $counter++;
            }
            
            // Pindahkan file ke folder tujuan
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $status = 'File "' . htmlspecialchars($original_filename) . '" berhasil diunggah!';
                $status_class = 'success';
                
                // Tampilkan pratinjau untuk gambar
                if ($file_type === 'image') {
                    $preview = $destination;
                }
            } else {
                $status = 'Gagal menyimpan file. Pastikan folder "dokumen" ada dan memiliki izin yang cukup.';
                $status_class = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unggah Dokumen dan Gambar</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .file-input {
            padding: 10px;
            border: 2px dashed #3498db;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input:hover {
            background-color: #f0f8ff;
        }
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .submit-btn:hover {
            background-color: #2980b9;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .error {
            background-color: #ffdddd;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }
        .success {
            background-color: #ddffdd;
            color: #388e3c;
            border: 1px solid #388e3c;
        }
        .preview-container {
            text-align: center;
            margin-top: 20px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .file-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .requirements {
            background-color: #fff8e1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
        .logout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            float: right;
        }
        .logout-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($password_required): ?>
            <form action="dokumen.php" method="post" style="text-align: right;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        <?php endif; ?>
        
        <h1>Unggah Dokumen dan Gambar</h1>
        
        <?php if (!empty($status)): ?>
            <div class="status <?php echo $status_class; ?>">
                <?php echo $status; ?>
            </div>
            
            <?php if (!empty($preview)): ?>
                <div class="preview-container">
                    <h3>Pratinjau Gambar:</h3>
                    <img src="<?php echo $preview; ?>" alt="Pratinjau Gambar" class="preview-image">
                    <div class="file-info">
                        <p>Nama File: <?php echo htmlspecialchars($original_filename); ?></p>
                        <p>Tipe File: <?php echo strtoupper(pathinfo($original_filename, PATHINFO_EXTENSION)); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form action="" method="post" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="file_upload">Pilih file (maksimal 50 MB):</label>
                <input type="file" name="file_upload" id="file_upload" class="file-input" required>
            </div>
            
            <button type="submit" class="submit-btn">Unggah File</button>
        </form>
        
        <div class="requirements">
            <h3>Persyaratan Unggah:</h3>
            <ul>
                <li>Ukuran file maksimal: 50 MB</li>
                <li>Format gambar yang didukung: JPG, JPEG, PNG, GIF</li>
                <li>Format dokumen yang didukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT</li>
                <li>Nama file akan dipertahankan sesuai aslinya</li>
                <li>Jika ada file dengan nama yang sama, akan ditambahkan angka unik</li>
            </ul>
        </div>
    </div>
</body>
</html>