<?php
// Sunucudan gelen isteğin bir POST isteği olup olmadığını kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek metodu.']);
    exit;
}

// Gelen verinin JSON formatında olduğunu belirt ve ham POST verisini al
$json_data = file_get_contents('php://input');
// Gelen JSON verisini bir PHP objesine dönüştür
$data = json_decode($json_data);

// JSON verisi çözülemezse hata ver
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz JSON formatı.']);
    exit;
}

// Kaydedilecek dosyanın adını belirle
$file = 'water_footprint_data.csv';

// Dosyanın başlık satırını tanımla
// Not: Excel'in Türkçe versiyonları için ayraç olarak noktalı virgül (;) kullanmak daha uyumludur.
$header = "Tarih;Toplam Ayak Izi (m3);Evsel (m3);Gida (m3);Diger (m3);Mavi Su (m3);Yesil Su (m3);Gri Su (m3)\n";

// Dosya daha önce oluşturulmamışsa, başlık satırını ekle
if (!file_exists($file)) {
    // file_put_contents fonksiyonu dosyayı oluşturur ve içeriği yazar.
    file_put_contents($file, $header, LOCK_EX);
}

// Gelen veriyi CSV formatında bir satıra dönüştür
// number_format ile ondalık ayracının nokta olmasını sağlıyoruz.
$line = date('Y-m-d H:i:s') . ';' .
        number_format($data->total ?? 0, 3, '.', '') . ';' .
        number_format($data->household ?? 0, 3, '.', '') . ';' .
        number_format($data->food ?? 0, 3, '.', '') . ';' .
        number_format($data->others ?? 0, 3, '.', '') . ';' .
        number_format($data->blue ?? 0, 3, '.', '') . ';' .
        number_format($data->green ?? 0, 3, '.', '') . ';' .
        number_format($data->gray ?? 0, 3, '.', '') . "\n";

// Yeni satırı dosyanın sonuna ekle (FILE_APPEND)
// LOCK_EX, aynı anda iki isteğin dosyaya yazmasını engeller.
$result = file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

// Yanıt olarak gönderilecek JSON'u hazırla
header('Content-Type: application/json');

if ($result === false) {
    // Dosyaya yazılamadıysa hata mesajı gönder
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Veri dosyaya kaydedilemedi. Klasör yazma izinlerini kontrol edin.']);
} else {
    // Başarılıysa başarı mesajı gönder
    echo json_encode(['status' => 'success', 'message' => 'Veri başarıyla kaydedildi.']);
}
?>
