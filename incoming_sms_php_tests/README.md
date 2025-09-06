# SMS Gateway - PHP Test Handlers

Bu klasör, SMS Gateway'den gelen bildirimleri test etmek için PHP 7.4 uyumlu test dosyalarını içerir.

## 📁 Dosyalar

### 1. `incoming_sms_get_test.php`
- **Amaç:** GET yöntemiyle gelen SMS bildirimlerini test eder
- **Özellikler:**
  - JWT token kontrolü (basit parse)
  - Parçalı SMS desteği
  - Özel parametreler
  - Hata simülasyonu (`?simulate_error=true`)

### 2. `incoming_sms_post_test.php`
- **Amaç:** POST yöntemiyle gelen SMS bildirimlerini test eder
- **Özellikler:**
  - JSON veri işleme
  - Mesaj analizi (uzunluk, kelime sayısı, URL/telefon tespiti)
  - Zaman analizi (gecikme hesaplama)
  - Özel yanıt verileri

### 3. `log.txt`
- **Amaç:** Tüm gelen istekler bu dosyaya kaydedilir
- **Format:** JSON formatında detaylı loglar

## 🚀 Kurulum

1. Bu dosyaları web sunucunuzun erişilebilir bir dizinine kopyalayın
2. `log.txt` dosyası için yazma izni verin:
   ```bash
   chmod 666 log.txt
   ```

## 📝 Kullanım

### GET Test URL'si:
```
http://yourserver.com/test_php/incoming_sms_get_test.php?from=905551234567&to=905559876543&message=Test mesajı&auth_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...&deneme=123&api_version=v1&source=sms-gateway&messageLength=11&isMultiPart=false
```

**Yukarıdaki URL'de:**
- `deneme=123` - CustomGetParams'tan gelen özel parametre
- `api_version=v1` - CustomGetParams'tan gelen özel parametre  
- `source=sms-gateway` - CustomGetParams'tan gelen özel parametre
- `messageLength=11` - include parametresi ile otomatik eklenen
- `isMultiPart=false` - include parametresi ile otomatik eklenen

### POST Test URL'si:
```
http://yourserver.com/test_php/incoming_sms_post_test.php
```

**POST Veri Örneği:**
```json
{
    "from": "905551234567",
    "to": "905559876543", 
    "message": "Test mesajı",
    "received_at": 1703123456,
    "customer_uuid": "uuid-here",
    "message_id": "incoming_1703123456_0",
    "is_multi_part": false,
    "deneme": "456",
    "callback_type": "incoming_sms",
    "priority": "high",
    "messageLength": 11,
    "receivedTimestamp": "2023-12-21T10:17:36Z"
}
```

**Yukarıdaki JSON'da:**
- `deneme: "456"` - CustomPostFields'tan gelen özel alan
- `callback_type: "incoming_sms"` - CustomPostFields'tan gelen özel alan
- `priority: "high"` - CustomPostFields'tan gelen özel alan
- `messageLength: 11` - include parametresi ile otomatik eklenen
- `receivedTimestamp: "2023-12-21T10:17:36Z"` - include parametresi ile otomatik eklenen

## 🔧 CustomerIncoming Yapılandırması

### GET Yöntemi için:
```json
{
    "RedirectType": "GET",
    "RedirectUrl": "http://yourserver.com/test_php/incoming_sms_get_test.php",
    "CustomGetParams": "{\"include\": [\"messageLength\", \"isMultiPart\"], \"deneme\": \"123\", \"api_version\": \"v1\", \"source\": \"sms-gateway\"}"
}
```

### POST Yöntemi için:
```json
{
    "RedirectType": "POST", 
    "RedirectUrl": "http://yourserver.com/test_php/incoming_sms_post_test.php",
    "CustomPostFields": "{\"include\": [\"messageLength\", \"receivedTimestamp\"], \"deneme\": \"456\", \"callback_type\": \"incoming_sms\", \"priority\": \"high\"}"
}
```

## 🧪 Test Senaryoları

### 1. Başarılı Bildirim Testi
- Normal SMS bildirimi gönder
- Başarı yanıtı kontrol et
- Log dosyasını incele

### 2. Hata Simülasyonu
- GET: `?simulate_error=true` ekle
- POST: `"simulate_error": true` ekle
- 500 hatası alınmalı

### 3. Parçalı SMS Testi
- `total_parts > 1` ile test et
- `reference` numarası kontrol et

### 4. JWT Token Testi
- Geçerli token ile test et
- Geçersiz token ile test et

### 5. **YENİ!** Özel Parametre Testi
- CustomGetParams/CustomPostFields'a JSON yaz
- Hem "include" hem direkt key-value'lar test et
- Cache reset özelliğini test et (update/delete sonrası)

## 🆕 **YENİ ÖZELLİKLER**

### ✅ **Akıllı Cache Yönetimi**
- Update/Delete sonrası otomatik cache temizleme
- Performans artışı ve veri tutarlılığı

### ✅ **Gelişmiş JSON Parametre Desteği**
```json
{
    "CustomGetParams": "{\"deneme\":\"123\", \"api_version\":\"v1\", \"include\":[\"messageLength\"]}",
    "CustomPostFields": "{\"callback_type\":\"sms\", \"priority\":\"high\", \"include\":[\"timestamp\"]}"
}
```

**İki tip parametre:**
1. **"include" Parametreleri:** Sistem tarafından hesaplanan özel değerler
   - `messageLength`, `isMultiPart`, `timestamp`, `processingTime`, vb.
2. **Direkt Parametreler:** JSON'a yazdığınız key-value çiftleri direkt eklenir

## 📊 Log Formatı

```json
{
    "request_data": {
        "timestamp": "2024-01-15 14:30:25",
        "method": "POST",
        "remote_addr": "192.168.1.100"
    },
    "sms_data": {
        "from": "905551234567",
        "to": "905559876543",
        "message": "Test mesajı"
    },
    "processed_at": "2024-01-15 14:30:25"
}
```

## ⚠️ Güvenlik Notları

- Bu dosyalar **sadece test amaçlı**dır
- Üretim ortamında kullanmayın
- JWT doğrulama basitleştirilmiştir
- Log dosyası hassas bilgiler içerebilir

## 🔍 Sorun Giderme

1. **Log dosyası yazılmıyor:**
   - Dosya izinlerini kontrol edin
   - Web sunucusu yazma hakkı var mı?

2. **JSON parse hatası:**
   - Content-Type header'ını kontrol edin
   - POST verisinin geçerli JSON olduğunu doğrulayın

3. **PHP hatası:**
   - error_log dosyasını kontrol edin
   - PHP 7.4+ kullandığınızdan emin olun
