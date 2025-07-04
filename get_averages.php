<?php
// Sonucun JSON formatında olacağını tarayıcıya bildir
header('Content-Type: application/json');

$file_path = 'water_footprint_data.csv';

// Veri dosyasının var olup olmadığını kontrol et
if (!file_exists($file_path)) {
    // Dosya yoksa, veri olmadığını belirten bir mesaj gönder
    echo json_encode(['status' => 'no_data', 'message' => 'Henüz kaydedilmiş veri bulunmamaktadır.']);
    exit;
}

// Toplamları ve kayıt sayısını saklamak için değişkenleri başlat
$sums = [
    'total'     => 0,
    'household' => 0,
    'food'      => 0,
    'others'    => 0
];
$record_count = 0;

// CSV dosyasını okumak için aç
// '@' işareti, dosya açılamazsa PHP'nin uyarı vermesini engeller, hatayı kendimiz yöneteceğiz.
if (($handle = @fopen($file_path, "r")) !== FALSE) {
    
    // İlk satırı (başlık satırını) atla
    fgetcsv($handle, 1000, ";");

    // Dosyanın sonuna kadar satır satır oku
    // fgetcsv, satırı ';' ayıracına göre bir diziye böler
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Her sütundaki değeri ilgili toplama ekle
        // Sütun sıralaması: 0:Tarih, 1:Toplam, 2:Evsel, 3:Gıda, 4:Diğer...
        $sums['total']     += (float)$data[1];
        $sums['household'] += (float)$data[2];
        $sums['food']      += (float)$data[3];
        $sums['others']    += (float)$data[4];
        
        $record_count++;
    }
    // Dosyayı kapat
    fclose($handle);
} else {
    // Dosya okunamadıysa bir sunucu hatası gönder
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Veri dosyası okunamadı.']);
    exit;
}

// Eğer hiç veri kaydı yoksa (sadece başlık varsa)
if ($record_count === 0) {
    echo json_encode(['status' => 'no_data', 'message' => 'Henüz kaydedilmiş veri bulunmamaktadır.']);
    exit;
}

// Ortalamaları hesapla
$averages = [
    'total'     => $sums['total'] / $record_count,
    'household' => $sums['household'] / $record_count,
    'food'      => $sums['food'] / $record_count,
    'others'    => $sums['others'] / $record_count
];

// Başarılı sonucu, kayıt sayısı ve ortalamalarla birlikte gönder
echo json_encode([
    'status'       => 'success',
    'record_count' => $record_count,
    'averages'     => $averages
]);

?>
