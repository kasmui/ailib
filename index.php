<?php
// Set execution time limit to prevent timeout for large PDFs
set_time_limit(300); // 5 minutes

// URLs for document sources
$urlHisabmu = 'https://falakmu.id/dokumen2/'; // Source Ebook 1 (KHGT & Ilmu Falak)
$urlFalakmu = 'https://falakmu.id/dokumen.php'; // Source Ebook 2 (Islami Umum)

// Function to fetch HTML content from a URL
function fetchHtmlContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['html' => $html, 'error' => $error, 'http_code' => $httpCode];
}

// Function to return generic thumbnail URL
function get_generic_thumbnail_url() {
    return 'https://hisabmu.com/ailib/cover-buku.jpg'; // Generic cover image URL
}

// Function to parse HTML and extract PDF files
function parsePdfFiles($html, $baseUrl) {
    $pdfFiles = [];
    $counter = 1;

    // The content from the external sites is not always well-formed HTML,
    // so using a DOM parser can fail. A regular expression is more robust
    // for this specific task of extracting PDF links.
    $regex = '/<a\s+href="([^"]+\.pdf)"[^>]*>(.*?)<\/a>/i';

    if (preg_match_all($regex, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $href = $match[1];
            // The filename is the text content of the link.
            $name = trim(strip_tags($match[2]));

            // If the link text is empty or just an image tag, fall back to the filename from the URL.
            if (empty($name)) {
                $name = basename(urldecode($href));
            }

            // It's difficult to reliably get the file size with regex from malformed HTML,
            // so we will default to 'Unknown'. The core functionality is listing the file.
            $size = 'Unknown';

            // Construct the full, absolute URL.
            $fullUrl = strpos($href, 'http') === 0 ? $href : rtrim($baseUrl, '/') . '/' . ltrim($href, '/');

            $thumbnailUrl = get_generic_thumbnail_url();

            $pdfFiles[] = [
                'name'      => $name,
                'size'      => $size,
                'url'       => $fullUrl,
                'thumbnail' => $thumbnailUrl,
                'number'    => $counter++
            ];
        }
    }
    return $pdfFiles;
}

// Fetch data for both sources
$pdfFilesHisabmu = [];
$pdfFilesFalakmu = [];
$errorMessages = [];

// Fetch Hisabmu data (Ebook 1)
$hisabmuData = fetchHtmlContent($urlHisabmu);
if (!$hisabmuData['html'] || $hisabmuData['http_code'] !== 200) {
    $errorMessages[] = '❌ Gagal memuat daftar file dari <a href="' . $urlHisabmu . '" target="_blank">' . $urlHisabmu . '</a>. Pastikan server Anda bisa mengakses situs tersebut. Error: ' . $hisabmuData['error'];
} else {
    $pdfFilesHisabmu = parsePdfFiles($hisabmuData['html'], $urlHisabmu);
}

// Fetch Falakmu data (Ebook 2)
$falakmuData = fetchHtmlContent($urlFalakmu);
if (!$falakmuData['html'] || $falakmuData['http_code'] !== 200) {
    $errorMessages[] = '❌ Gagal memuat daftar file dari <a href="' . $urlFalakmu . '" target="_blank">' . $urlFalakmu . '</a>. Pastikan server Anda bisa mengakses situs tersebut. Error: ' . $falakmuData['error'];
} else {
    $pdfFilesFalakmu = parsePdfFiles($falakmuData['html'], $urlFalakmu);
}

// Store data in a way accessible by JavaScript
$allPdfFilesJson = json_encode([
    'hisabmu' => $pdfFilesHisabmu,
    'falakmu' => $pdfFilesFalakmu
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📄 AI Library | Dokumen Ilmu Falak & KHGT</title>
    <link rel="icon" type="image/png" sizes="32x32" href="book.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Gradient Background */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
            display: flex;
            justify-content: center;
        }
        .container {
            width: 90%;
            max-width: 900px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 35px;
            margin-top: 30px;
            transition: transform 0.3s;
        }
        .container:hover {
            transform: translateY(-5px);
        }
        h1 {
            color: #1e3c72;
            text-align: center;
            margin-bottom: 15px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        h1 i {
            color: #007BFF;
            margin-right: 10px;
        }
        .subtitle {
            text-align: center;
            color: #555;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .subtitle2 {
            text-align: center;
            color: blue;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .input-section {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin: 15px 0 8px 0;
            font-weight: 700;
            color: #1e3c72;
            font-size: 15px;
        }
        .select-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
        }
        .select-group > div {
            flex: 1;
            min-width: 250px;
        }
        .select-group label {
            margin-bottom: 8px;
        }
        .custom-select {
            padding: 10px 15px;
            border: 1px solid #90caf9;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            color: #1e3c72;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="#1e3c72" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            cursor: pointer;
            transition: border-color 0.3s, box-shadow 0.3s;
            width: 100%;
        }
        .custom-select:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
        }
        .file-content-display {
            max-height: 420px;
            overflow-y: auto;
            border: 1px solid #b3d9ff;
            border-radius: 12px;
            background-color: #f9fbfd;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .file-item {
            padding: 14px 18px;
            border-bottom: 1px solid #e3f2fd;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }
        .file-item:hover {
            background: linear-gradient(to right, #e3f2fd, #bbdefb);
            transform: translateX(5px);
            border-left: 3px solid #2196F3;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-name {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-right: 12px;
            color: #0d47a1;
        }
        .file-size {
            color: #1565c0;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            width: 80px;
            text-align: right;
            background: #bbdefb;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            max-width: 800px;
            margin: 0 auto;
            gap: 20px;
            padding: 15px;
        }
        .thumbnail-item {
            background-color: #ffffff;
            border: 1px solid #e3f2fd;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .thumbnail-item:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
            border-color: #2196F3;
        }
        .thumbnail-item img {
            width: 100px;
            height: 140px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #ddd;
            display: block;
            margin: 0 auto;
        }
        .thumbnail-item::before {
            content: attr(data-number);
            position: absolute;
            top: 5px;
            left: 5px;
            background-color: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            z-index: 10;
        }
        .thumbnail-item::after {
            content: attr(data-filename);
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 5px;
            font-size: 12px;
            text-align: center;
            white-space: normal;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            visibility: visible;
            opacity: 0.7;
            transition: all 0.3s ease-out;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            box-sizing: border-box;
            max-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .thumbnail-item:hover::after {
            opacity: 1;
            max-height: 100%;
            top: 0;
            border-radius: 10px;
        }
        .thumbnail-item .thumbnail-info {
            display: none;
        }
        .or-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #79869c;
            font-weight: 600;
        }
        .or-divider::before,
        .or-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px dashed #b0bec5;
        }
        .or-divider::before {
            margin-right: 15px;
        }
        .or-divider::after {
            margin-left: 15px;
        }
        textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #90caf9;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            color: #1e3c72;
            resize: vertical;
            min-height: 100px;
            background-color: #f5f9ff;
            transition: border-color 0.3s;
        }
        textarea:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
        }
        button {
            padding: 14px 24px;
            background: linear-gradient(to right, #007BFF, #0056b3);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            width: 100%;
        }
        button:hover {
            background: linear-gradient(to right, #0069d9, #004085);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .elegant-button {
            display: block;
            width: 100%;
            padding: 14px 24px;
            margin: 10px 0;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .elegant-button:hover {
            background: linear-gradient(135deg, #43A047, #1B5E20);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
        }
        .elegant-button:active {
            transform: translateY(0);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: flex-start;
            justify-content: center;
            padding-top: 50px;
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: scale(0.9);
            opacity: 0;
            animation: modalOpen 0.3s forwards;
        }
        @keyframes modalOpen {
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h2 {
            margin: 0;
            color: #1e3c72;
            font-weight: 700;
        }
        .close {
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            font-weight: bold;
        }
        .close:hover {
            color: #000;
        }
        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: #e3f2fd;
            border-radius: 12px;
            color: #0d47a1;
            font-weight: 700;
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid #007BFF;
            width: 45px;
            height: 45px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            color: #c62828;
            background-color: #ffebee;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
            font-weight: 700;
            border-left: 5px solid #ef5350;
        }
        .error-message a {
            color: #d32f2f;
            font-weight: 700;
            text-decoration: underline;
        }
        .tutorial-section {
            display: none;
            margin-bottom: 25px;
            padding: 20px;
            background: #f9fbfd;
            border-radius: 12px;
            border: 1px solid #b3d9ff;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .tutorial-section h2 {
            color: #1e3c72;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .tutorial-section h3 {
            color: #1e3c72;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            font-size: 20px;
        }
        .tutorial-section p {
            color: #4b5563;
            margin-bottom: 16px;
            line-height: 1.6;
        }
        .tutorial-section ul {
            list-style: disc;
            padding-left: 24px;
            margin-bottom: 16px;
        }
        .tutorial-section li {
            color: #4b5563;
            margin-bottom: 8px;
        }
        .tutorial-section a {
            color: #007BFF;
            text-decoration: underline;
        }
        .tutorial-section a:hover {
            color: #0056b3;
        }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            h1 { font-size: 26px; }
            .file-item { padding: 12px 15px; }
            button, .elegant-button { font-size: 15px; padding: 12px 20px; }
            .thumbnail-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            .thumbnail-item img { width: 80px; height: 112px; }
            .select-group { flex-direction: column; align-items: stretch; }
            .select-group > div { min-width: unset; }
            .tutorial-section h2 { font-size: 20px; }
            .tutorial-section h3 { font-size: 18px; }
            .tutorial-section { padding: 15px; }
        }
        footer {
            margin-top: 50px;
            margin-bottom: 50px;
            color: white;
            font-size: 12px;
            text-align: center;
            background-color: black;
            padding: 10px;
        }
    </style>
</head>
<body background="https://webspace.science.uu.nl/~gent0113/islam/images/surat_al_ikhlas.gif" bgproperties="fixed" oncontextmenu="return false;">
    <script>
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 83)) {
                e.preventDefault();
            }
        });
    </script>
    <div class="container">
        <h1><i class="fas fa-file-pdf"></i> AI LIBRARY ISLAMI</h1>
        <p class="subtitle">Integrasi Kepustakaan Ilmu Falak, KHGT & Islami Umum dengan AI (AI Library)</p>
        <p class="subtitle2" style="font-weight: bold; color: blue;">AI adalah alat bantu, bukan pengganti pemikiran kritis, kendali kualitas dan kebenaran di tangan Anda.</p>
        <button onclick="toggleTutorial()" class="elegant-button">
            <i class="fas fa-book-open"></i> TUTORIAL SINGKAT
        </button>
        <div class="tutorial-section" id="tutorialSection">
            <div style="
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 16px;
                padding: 30px;
                margin: 20px 0;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                border-left: 5px solid #4e54c8;
            ">
            <h2 style="
                color: #4e54c8;
                font-size: 28px;
                margin-bottom: 25px;
                text-align: center;
                font-weight: 700;
                text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
            ">
                <i class="fas fa-star" style="color: #ffc107;"></i> Fitur Unggulan AI Library
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <!-- Fitur 1 -->
                <div style="
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                    border-top: 4px solid #4CAF50;
                    transition: all 0.3s ease;
                ">
                    <h3 style="color: #4CAF50; font-size: 20px; margin-bottom: 15px;">
                        <i class="fas fa-book-open" style="margin-right: 10px;"></i> Akses Dokumen Lengkap
                    </h3>
                    <p style="color: #555; line-height: 1.6;">
                        Koleksi lengkap dokumen Ilmu Falak, KHGT, dan Islami Umum dalam format PDF yang siap diakses dan diproses.
                    </p>
                    <div style="
                        background: #E8F5E9;
                        padding: 10px;
                        border-radius: 8px;
                        margin-top: 15px;
                        border-left: 3px solid #4CAF50;
                    ">
                        <p style="color: #2E7D32; margin: 0; font-weight: 500;">
                            <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>
                            Klik langsung pada dokumen untuk memprosesnya dengan AI
                        </p>
                    </div>
                </div>
                
                <!-- Fitur 2 -->
                <div style="
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                    border-top: 4px solid #2196F3;
                    transition: all 0.3s ease;
                ">
                    <h3 style="color: #2196F3; font-size: 20px; margin-bottom: 15px;">
                        <i class="fas fa-robot" style="margin-right: 10px;"></i> Integrasi dengan ChatGPT
                    </h3>
                    <p style="color: #555; line-height: 1.6;">
                        Otomatis membuka ChatGPT dengan dokumen dan prompt yang sudah disiapkan, siap untuk diproses lebih lanjut.
                    </p>
                    <div style="
                        background: #E3F2FD;
                        padding: 10px;
                        border-radius: 8px;
                        margin-top: 15px;
                        border-left: 3px solid #2196F3;
                    ">
                        <p style="color: #1565C0; margin: 0; font-weight: 500;">
                            <i class="fas fa-magic" style="margin-right: 8px;"></i>
                            Cukup pilih dokumen, AI akan langsung bekerja!
                        </p>
                    </div>
                </div>
                
                <!-- Fitur 3 -->
                <div style="
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                    border-top: 4px solid #9C27B0;
                    transition: all 0.3s ease;
                ">
                    <h3 style="color: #9C27B0; font-size: 20px; margin-bottom: 15px;">
                        <i class="fas fa-pen-fancy" style="margin-right: 10px;"></i> Penyusun Khutbah Otomatis
                    </h3>
                    <p style="color: #555; line-height: 1.6;">
                        Fitur khusus untuk menyusun naskah khutbah Jumat lengkap dengan struktur Islami yang benar.
                    </p>
                    <div style="
                        background: #F3E5F5;
                        padding: 10px;
                        border-radius: 8px;
                        margin-top: 15px;
                        border-left: 3px solid #9C27B0;
                    ">
                        <p style="color: #7B1FA2; margin: 0; font-weight: 500;">
                            <i class="fas fa-mosque" style="margin-right: 8px;"></i>
                            Lengkap dengan teks Arab, terjemahan, dan tafsir singkat
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div style="
            background: linear-gradient(135deg, #fffde7 0%, #fff9c4 100%);
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-left: 5px solid #FFA000;
        ">
    <h2 style="
        color: #FF6F00;
        font-size: 28px;
        margin-bottom: 25px;
        text-align: center;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
    ">
        <i class="fas fa-graduation-cap" style="color: #FFA000;"></i> Tutorial Penggunaan
    </h2>
    
    <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
        <!-- Langkah 1 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        ">
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 5px;
                background: #FF5722;
            "></div>
            <h3 style="color: #FF5722; font-size: 20px; margin-bottom: 15px;">
                <span style="
                    background: #FF5722;
                    color: white;
                    width: 30px;
                    height: 30px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    margin-right: 10px;
                ">1</span>
                Pilih Sumber Dokumen
            </h3>
            <p style="color: #555; line-height: 1.6; margin-bottom: 15px;">
                AI Library menyediakan dua sumber dokumen utama:
            </p>
            <ul style="color: #555; padding-left: 20px; margin-bottom: 15px;">
                <li style="margin-bottom: 8px;">
                    <strong style="color: #FF5722;">Ebook 1</strong>: KHGT & Ilmu Falak
                </li>
                <li style="margin-bottom: 8px;">
                    <strong style="color: #FF5722;">Ebook 2</strong>: Islami Umum
                </li>
            </ul>
            <div style="
                background: #FFEBEE;
                padding: 12px;
                border-radius: 8px;
                border-left: 3px solid #F44336;
            ">
                <p style="color: #D32F2F; margin: 0; font-weight: 500;">
                    <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                    Pilih sesuai kebutuhan Anda dari dropdown "Pilih Sumber Ebook"
                </p>
            </div>
        </div>
        
        <!-- Langkah 2 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        ">
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 5px;
                background: #3F51B5;
            "></div>
            <h3 style="color: #3F51B5; font-size: 20px; margin-bottom: 15px;">
                <span style="
                    background: #3F51B5;
                    color: white;
                    width: 30px;
                    height: 30px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    margin-right: 10px;
                ">2</span>
                Tentukan Mode Tampilan
            </h3>
            <p style="color: #555; line-height: 1.6; margin-bottom: 15px;">
                Anda bisa memilih tampilan dokumen dalam dua mode:
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div style="
                    background: #E8EAF6;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <i class="fas fa-list" style="font-size: 24px; color: #3F51B5; margin-bottom: 10px;"></i>
                    <p style="color: #303F9F; font-weight: 500; margin: 0;">Mode Daftar</p>
                </div>
                <div style="
                    background: #E8EAF6;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <i class="fas fa-th" style="font-size: 24px; color: #3F51B5; margin-bottom: 10px;"></i>
                    <p style="color: #303F9F; font-weight: 500; margin: 0;">Mode Gambar</p>
                </div>
            </div>
            <p style="color: #555; line-height: 1.6;">
                Mode Gambar akan menampilkan thumbnail cover buku untuk pengalaman browsing yang lebih visual.
            </p>
        </div>
        
        <!-- Langkah 3 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        ">
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 5px;
                background: #009688;
            "></div>
            <h3 style="color: #009688; font-size: 20px; margin-bottom: 15px;">
                <span style="
                    background: #009688;
                    color: white;
                    width: 30px;
                    height: 30px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    margin-right: 10px;
                ">3</span>
                Pilih Jenis Pemrosesan
            </h3>
            <p style="color: #555; line-height: 1.6; margin-bottom: 15px;">
                AI Library menawarkan 4 pilihan pemrosesan dokumen:
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="
                    background: #E0F2F1;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <i class="fas fa-file-alt" style="font-size: 24px; color: #00796B; margin-bottom: 10px;"></i>
                    <p style="color: #00796B; font-weight: 500; margin: 0;">Artikel Default</p>
                </div>
                <div style="
                    background: #E0F2F1;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <i class="fas fa-edit" style="font-size: 24px; color: #00796B; margin-bottom: 10px;"></i>
                    <p style="color: #00796B; font-weight: 500; margin: 0;">Prompt Kustom</p>
                </div>
                <div style="
                    background: #E0F2F1;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <i class="fas fa-mosque" style="font-size: 24px; color: #00796B; margin-bottom: 10px;"></i>
                    <p style="color: #00796B; font-weight: 500; margin: 0;">Naskah Khutbah</p>
                </div>
                <div style="
                    background: #E0F2F1;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <i class="fas fa-download" style="font-size: 24px; color: #00796B; margin-bottom: 10px;"></i>
                    <p style="color: #00796B; font-weight: 500; margin: 0;">Download PDF</p>
                </div>
            </div>
            <div style="
                background: #B2DFDB;
                padding: 12px;
                border-radius: 8px;
            ">
                <p style="color: #004D40; margin: 0; font-weight: 500;">
                    <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>
                    Untuk pemrosesan AI, cukup klik pada dokumen yang dipilih dan sistem akan otomatis membuka ChatGPT
                </p>
            </div>
        </div>
        
        <!-- Langkah 4 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        ">
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 5px;
                background: #E91E63;
            "></div>
            <h3 style="color: #E91E63; font-size: 20px; margin-bottom: 15px;">
                <span style="
                    background: #E91E63;
                    color: white;
                    width: 30px;
                    height: 30px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    margin-right: 10px;
                ">4</span>
                Hasil & Penyempurnaan
            </h3>
            <p style="color: #555; line-height: 1.6; margin-bottom: 15px;">
                Setelah ChatGPT terbuka dengan dokumen dan prompt yang sudah disiapkan:
            </p>
            <ul style="color: #555; padding-left: 20px; margin-bottom: 15px;">
                <li style="margin-bottom: 8px;">
                    <strong style="color: #E91E63;">Review hasil</strong> yang diberikan oleh AI
                </li>
                <li style="margin-bottom: 8px;">
                    <strong style="color: #E91E63;">Ajukan pertanyaan lanjutan</strong> untuk memperdalam analisis
                </li>
                <li style="margin-bottom: 8px;">
                    <strong style="color: #E91E63;">Minta revisi</strong> jika diperlukan
                </li>
                <li style="margin-bottom: 8px;">
                    <strong style="color: #E91E63;">Salin hasil</strong> untuk digunakan sesuai kebutuhan Anda
                </li>
            </ul>
            <div style="
                background: #FCE4EC;
                padding: 12px;
                border-radius: 8px;
                border-left: 3px solid #E91E63;
            ">
                <p style="color: #C2185B; margin: 0; font-weight: 500;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                    Selalu verifikasi kebenaran konten yang dihasilkan AI dengan referensi yang valid
                </p>
            </div>
        </div>
    </div>
</div>

<div style="
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 16px;
    padding: 25px;
    margin: 30px 0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-left: 5px solid #1976D2;
">
    <h2 style="
        color: #0D47A1;
        font-size: 24px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 700;
    ">
        <i class="fas fa-question-circle" style="color: #1976D2;"></i> Tips & Trik
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <!-- Tip 1 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        ">
            <div style="
                width: 50px;
                height: 50px;
                background: #E3F2FD;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
            ">
                <i class="fas fa-search" style="font-size: 24px; color: #1976D2;"></i>
            </div>
            <h3 style="
                color: #1976D2;
                font-size: 18px;
                text-align: center;
                margin-bottom: 10px;
            ">Gunakan Kata Kunci Spesifik</h3>
            <p style="color: #555; text-align: center; line-height: 1.5;">
                Tambahkan kata kunci spesifik dalam prompt untuk hasil yang lebih relevan, seperti "astronomi", "hisab", atau "rukyat".
            </p>
        </div>
        
        <!-- Tip 2 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        ">
            <div style="
                width: 50px;
                height: 50px;
                background: #E3F2FD;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
            ">
                <i class="fas fa-language" style="font-size: 24px; color: #1976D2;"></i>
            </div>
            <h3 style="
                color: #1976D2;
                font-size: 18px;
                text-align: center;
                margin-bottom: 10px;
            ">Bahasa Arab & Transliterasi</h3>
            <p style="color: #555; text-align: center; line-height: 1.5;">
                Untuk teks Arab, sertakan permintaan transliterasi dan terjemahan dalam prompt Anda.
            </p>
        </div>
        
        <!-- Tip 3 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        ">
            <div style="
                width: 50px;
                height: 50px;
                background: #E3F2FD;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
            ">
                <i class="fas fa-layer-group" style="font-size: 24px; color: #1976D2;"></i>
            </div>
            <h3 style="
                color: #1976D2;
                font-size: 18px;
                text-align: center;
                margin-bottom: 10px;
            ">Struktur Hierarkis</h3>
            <p style="color: #555; text-align: center; line-height: 1.5;">
                Mintalah AI untuk menyusun hasil dalam format terstruktur dengan poin-poin penting dan sub-bagian.
            </p>
        </div>
        
        <!-- Tip 4 -->
        <div style="
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        ">
            <div style="
                width: 50px;
                height: 50px;
                background: #E3F2FD;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
            ">
                <i class="fas fa-check-double" style="font-size: 24px; color: #1976D2;"></i>
            </div>
            <h3 style="
                color: #1976D2;
                font-size: 18px;
                text-align: center;
                margin-bottom: 10px;
            ">Verifikasi Silang</h3>
            <p style="color: #555; text-align: center; line-height: 1.5;">
                Selalu lakukan verifikasi silang informasi dari AI dengan sumber-sumber terpercaya.
            </p>
        </div>
    </div>
</div>
        </div>

        <?php if (!empty($errorMessages)): ?>
            <?php foreach ($errorMessages as $error): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($errorMessages) || !empty($pdfFilesHisabmu) || !empty($pdfFilesFalakmu)): ?>
            <div class="select-group">
                <div>
                    <label for="sourceSelect">📚 Pilih Sumber Ebook:</label>
                    <select id="sourceSelect" class="custom-select" onchange="updateDisplayedFiles()">
                        <option value="hisabmu">Ebook 1: KHGT & Ilmu Falak</option>
                        <option value="falakmu">Ebook 2: Islami Umum</option>
                    </select>
                </div>
                <div>
                    <label for="displayModeSelect">📝 Pilih Mode Tampilan:</label>
                    <select id="displayModeSelect" class="custom-select" onchange="updateDisplayedFiles()">
                        <option value="list">Mode Daftar</option>
                        <option value="thumbnail">Mode Gambar</option>
                    </select>
                </div>
            </div>

            <div class="input-section">
                <label style="margin-bottom: 15px; font-size: 20px; color: red;">📄 PILIHAN 1: Membuat artikel DEFAULT dari pilihan dokumen berikut:</label>
                <div class="file-content-display" id="fileDisplayArea">
                    <div style="text-align: center; padding: 20px; color: #777;">Pilih sumber dan mode tampilan di atas untuk melihat daftar file.</div>
                </div>
            </div>

            <div class="or-divider">ATAU</div>

            <!-- PILIHAN 2 & 3 tetap sama -->
            <div class="input-section">
                <label style="margin-bottom: 15px; font-size: 20px; color: red;" for="customPrompt"><i class="fas fa-comment-alt"></i> 🛠️ PILIHAN 2: Gunakan Prompt Kustom:</label>
                <textarea id="customPrompt" rows="3">
Susunkan artikel sebanyak 5000 kata dari file pdf ini dengan kalimat jurnalistik yang mudah dipahami dan akademis. Sertakan poin-poin penting dan jelaskan konsep-konsep utama secara singkat dan sederhana. Di akhir artikel berikan tantangan untuk mengeksplorasi lebih jauh.
                </textarea>
                <div class="select-group" style="margin-top: 15px;">
                    <div>
                        <label for="customSourceSelect">📚 Pilih Sumber Ebook:</label>
                        <select id="customSourceSelect" class="custom-select">
                            <option value="hisabmu">Ebook 1: KHGT & Ilmu Falak</option>
                            <option value="falakmu">Ebook 2: Islami Umum</option>
                        </select>
                    </div>
                    <div>
                        <label for="customDisplayModeSelect">📝 Pilih Mode Tampilan:</label>
                        <select id="customDisplayModeSelect" class="custom-select">
                            <option value="list">Mode Daftar</option>
                            <option value="thumbnail">Mode Gambar</option>
                        </select>
                    </div>
                </div>
                <button onclick="openFileModal('customPrompt')">
                    <i class="fas fa-list"></i> 🔍 Pilih File dari Daftar
                </button>
            </div>

            <div class="or-divider">ATAU</div>

            <div class="input-section">
                <label style="margin-bottom: 15px; font-size: 20px; color: red;" for="khutbahPrompt"><i class="fas fa-mosque"></i> 🕌 PILIHAN 3: Menyusun Naskah Khutbah Jumat (draft)</label>
                <textarea id="khutbahPrompt" rows="8">
Susunkan naskah khutbah Jumat lengkap (6000 kata) tentang tema sesuai tema ebook yang dipilih dengan catatan: ada Pembukaan (mukadimah) dengan pujian kepada Allah dan shalawat Nabi (sertakan teks arab berharakat) ada ayat Quran sesuai tema, awal khutbah pertama ada pesan wasiat taqwa kepada jamaah Jumat, dalam khutbah pertama menyertakan dalil Ayat Al-Qur'an dan Hadits yang relevan (cari di internet jika diperlukan), sertakan teks arab berharakat dengan arti dan tafsir singkat, ada Penjelasan materi khutbah berdasarkan dalil Al-Qur'an dan Hadits, pada akhir Bagian khutbah ke-2 sertakan doa dengan teks arab berharakat. Semua kalimat khutbah menggunakan gaya bahasa umum yang Islami yang mudah dipahami dan mengalir, dengan penekanan pada urgensi dan relevansi topik ini bagi umat Islam. Pastikan struktur dan isi khutbah standar khutbah Jumat yang baik. Pastikan tidak ada yang salah dalam penulisan teks arab Al-Qur'an dan Hadits, juga nomor surat dan ayatnya.
                </textarea>
                <div class="select-group" style="margin-top: 15px;">
                    <div>
                        <label for="khutbahSourceSelect">📚 Pilih Sumber Ebook untuk Khutbah:</label>
                        <select id="khutbahSourceSelect" class="custom-select">
                            <option value="hisabmu">Ebook 1: KHGT & Ilmu Falak</option>
                            <option value="falakmu">Ebook 2: Islami Umum</option>
                        </select>
                    </div>
                    <div>
                        <label for="khutbahDisplayModeSelect">📝 Pilih Mode Tampilan Khutbah:</label>
                        <select id="khutbahDisplayModeSelect" class="custom-select">
                            <option value="list">Mode Daftar</option>
                            <option value="thumbnail">Mode Gambar</option>
                        </select>
                    </div>
                </div>
                <button onclick="openFileModal('khutbahPrompt')">
                    <i class="fas fa-list-alt"></i> 📖 Pilih Ebook untuk Tema Khutbah
                </button>
            </div>

            <div class="or-divider">ATAU</div>

            <div class="input-section">
                <label style="margin-bottom: 15px; font-size: 20px; color: #d32f2f; font-weight: bold; display: block; text-align: left;">
                    <i class="fas fa-download"></i> 📤 PILIHAN 4: Mendownload File PDF dari AI Library
                </label>
                <div class="row justify-content-center g-3">
                    <div class="col-md-6 col-lg-4">
                        <a href="https://falakmu.id/dokumen2/" target="_self" class="text-decoration-none">
                            <button class="btn btn-primary w-100 py-3" style="background: linear-gradient(135deg, #007BFF, #0056b3); border: none; border-radius: 10px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
                                <i class="fas fa-book-open me-2"></i> Ebook 1
                            </button>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="https://falakmu.id/dokumen/" target="_self" class="text-decoration-none">
                            <button class="btn btn-success w-100 py-3" style="background: linear-gradient(135deg, #28a745, #1e7e34); border: none; border-radius: 10px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
                                <i class="fas fa-book me-2"></i> Ebook 2
                            </button>
                        </a>
                    </div>
                </div>
                <p class="text-center mt-3 text-muted" style="font-size: 14px; margin-top: 50px;">
                    Copyright &copy; 2025 Kasmui. All Rights Reserved.
                </p>
            </div>

            <!-- Modal Pilih File -->
            <div id="fileModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Pilih File PDF</h2>
                        <span class="close" onclick="closeModal()">&times;</span>
                    </div>
                    <div id="modalFileListContainer" class="file-content-display"></div>
                    <div class="modal-footer">
                        <button onclick="closeModal()" style="background: #6c757d;">Batal</button>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>🚀 Membuka di ChatGPT... Tunggu sebentar!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const allPdfFiles = <?= $allPdfFilesJson ?>;
        let currentPromptType = '';
        let currentSourceForModal = '';

        function toggleTutorial() {
            const tutorialSection = document.getElementById('tutorialSection');
            tutorialSection.style.display = tutorialSection.style.display === 'block' ? 'none' : 'block';
        }

        function openFileModal(promptType) {
            currentPromptType = promptType;
            currentSourceForModal = promptType === 'khutbahPrompt' 
                ? document.getElementById('khutbahSourceSelect').value 
                : document.getElementById('customSourceSelect').value;
            displayFiles(currentSourceForModal, 'list', 'modalFileListContainer', true);
            document.getElementById('fileModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('fileModal').style.display = 'none';
        }

        function selectCustomFile(url, name) {
            let promptText = '';
            if (currentPromptType === 'customPrompt') {
                promptText = document.getElementById('customPrompt').value.trim();
            } else if (currentPromptType === 'khutbahPrompt') {
                promptText = document.getElementById('khutbahPrompt').value.trim();
            } else {
                promptText = "Buatkan resume sebanyak 5000 kata dari file pdf ini dengan kalimat yang mudah dipahami. Sertakan poin-poin penting dan jelaskan konsep-konsep utama secara sederhana.";
            }
            processPdf(url, name, promptText);
            closeModal();
        }

        function processPdf(pdfUrl, fileName, promptContent) {
            const fullPrompt = `${promptContent}
📄 Dokumen: ${fileName}
🔗 URL: ${pdfUrl}`;
            openInChatGPT(fullPrompt);
        }

        function openInChatGPT(prompt) {
            document.getElementById('loading').style.display = 'block';
            setTimeout(() => {
                const chatGPTUrl = `https://chat.openai.com/?q=${encodeURIComponent(prompt)}`;
                window.open(chatGPTUrl, '_blank');
                document.getElementById('loading').style.display = 'none';
            }, 800);
        }

        function displayFiles(source, mode, targetElementId, isModal = false) {
            const targetElement = document.getElementById(targetElementId);
            const files = allPdfFiles[source] || [];
            let htmlContent = '';

            if (files.length === 0) {
                htmlContent = `<div style="text-align: center; padding: 20px; color: #777;">Tidak ada file PDF ditemukan atau gagal memuat dari sumber ini.</div>`;
            } else {
                if (mode === 'list') {
                    files.forEach(file => {
                        const encodedUrl = encodeURIComponent(file.url);
                        const fileName = file.name.replace(/'/g, "\\'");
                        const prompt = "Buatkan resume sebanyak 5000 kata dari file pdf ini dengan kalimat yang mudah dipahami. Sertakan poin-poin penting dan jelaskan konsep-konsep utama secara sederhana.";
                        const fullPrompt = `${prompt}
📄 Dokumen: ${file.name}
🔗 URL: ${file.url}`;
                        const encodedPrompt = encodeURIComponent(fullPrompt);
                        const onclick = `openInChatGPTWithLoading('https://chat.openai.com/?q=${encodedPrompt}')`;
                        htmlContent += `
                            <div class="file-item" onclick="${onclick}">
                                <span class="file-name">${file.name}</span>
                                <span class="file-size">${file.size}</span>
                            </div>
                        `;
                    });
                    targetElement.classList.remove('thumbnail-grid');
                } else if (mode === 'thumbnail') {
                    htmlContent += '<div class="thumbnail-grid">';
                    files.forEach(file => {
                        const prompt = "Buatkan resume sebanyak 5000 kata dari file pdf ini dengan kalimat yang mudah dipahami. Sertakan poin-poin penting dan jelaskan konsep-konsep utama secara sederhana.";
                        const fullPrompt = `${prompt}
📄 Dokumen: ${file.name}
🔗 URL: ${file.url}`;
                        const encodedPrompt = encodeURIComponent(fullPrompt);
                        const onclick = `openInChatGPTWithLoading('https://chat.openai.com/?q=${encodedPrompt}')`;
                        htmlContent += `
                            <div class="thumbnail-item" onclick="${onclick}" data-number="${file.number}" data-filename="${file.name}">
                                <img src="${file.thumbnail}" alt="${file.name}">
                            </div>
                        `;
                    });
                    htmlContent += '</div>';
                    targetElement.classList.add('thumbnail-grid');
                    targetElement.style.maxWidth = '800px';
                    targetElement.style.margin = '0 auto';
                }
            }

            targetElement.innerHTML = htmlContent;
            targetElement.style.maxHeight = isModal ? '300px' : '420px';
            if (!isModal && mode === 'list') {
                targetElement.style.border = '1px solid #b3d9ff';
                targetElement.style.borderRadius = '12px';
                targetElement.style.backgroundColor = '#f9fbfd';
                targetElement.style.boxShadow = 'inset 0 1px 3px rgba(0,0,0,0.1)';
            }
        }

        function openInChatGPTWithLoading(url) {
            document.getElementById('loading').style.display = 'block';
            setTimeout(() => {
                window.open(url, '_blank');
                document.getElementById('loading').style.display = 'none';
            }, 800);
        }

        function updateDisplayedFiles() {
            const selectedSource = document.getElementById('sourceSelect').value;
            const selectedMode = document.getElementById('displayModeSelect').value;
            displayFiles(selectedSource, selectedMode, 'fileDisplayArea');
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('fileModal')) {
                closeModal();
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            updateDisplayedFiles();
        });
    </script>
</body>
</html>