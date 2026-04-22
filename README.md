# Viscom Maintenance Tracking System v2.0

## Özellikler
- 5 (veya daha fazla) makine için ayrı ayrı bakım takibi
- ODS tablosuyla birebir aynı görevler (Monthly / 3-Monthly / 6-Monthly / Yearly)
- Her makine için yıllık görünüm ve ilerleme takibi
- Dönem bazlı kayıt (tarih + teknisyen + notlar)
- Yazdırma: tek makine veya tüm makineler (ODS tablosuyla aynı format)
- Makine ekleme/düzenleme/silme (Settings sayfası)

---

## Kurulum (XAMPP / Linux PHP + MySQL)

### 1. Veritabanı Oluştur
```sql
-- phpMyAdmin veya MySQL komut satırı:
SOURCE /path/to/viscom_maintenance/db/schema.sql
```

### 2. config.php Ayarla
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'viscom_maintenance');
define('DB_USER', 'root');     // DB kullanıcı adınız
define('DB_PASS', '');         // DB şifreniz
```

### 3. Dosyaları Web Sunucusuna Kopyala
```
htdocs/viscom_maintenance/   (XAMPP)
/var/www/html/viscom_maintenance/   (Linux Apache)
```

### 4. Tarayıcıda Aç
```
http://localhost/viscom_maintenance/
```

---

## Dosya Yapısı
```
viscom_maintenance/
├── config.php          → Veritabanı bağlantısı
├── index.php           → Ana sayfa - 5 makine özeti
├── machine.php         → Makine detay + kayıt formu
├── print_machine.php   → Tek makine yazdırma
├── print_all.php       → Tüm makineler yazdırma
├── settings.php        → Makine yönetimi
├── css/
│   └── style.css
└── db/
    └── schema.sql      → Veritabanı şeması
```

---

## Makine İsimleri Değiştirme
Settings sayfasından (⚙ Settings / Machines) herhangi bir makineyi
düzenleyebilir, silebilir veya yeni makine ekleyebilirsiniz.

---

## Bakım Görevleri
ODS tablosundaki tüm görevler `maintenance_tasks` tablosuna aktarıldı:

**Monthly (12x/yıl):**
- Viscom PC → Database Backup Taken
- Viscom Camera and Motor → Checked For Health
- Viscom Machine → PCB Board Rails Cleared
- Viscom Machine → Calibration Cross Checked
- Viscom Compressed Air → 4-6 Bar Checked

**3 Monthly (4x/yıl - Mar/Jun/Sep/Dec):**
- Viscom Camera → Grayscale Value Calibration Done
- Viscom Machine → Transport System Contact Area Checked
- Viscom PC and Camera → Fan Vent Filters Cleaned

**6 Monthly (2x/yıl - Jun/Dec):**
- Viscom Machine → Geometric Calibration Done
- Viscom Camera → 3D Camera For Health In Software Checked
- Viscom Machine → Wear Of Cable Reels Checked
- Viscom Machine → Positioning Unit Lubricated
- Viscom Machine → Screws Checked with Torque Wrench
- Viscom Machine → PCB Board Stoper Checked
- Viscom Machine → PCB Board Conveyor Belt Checked

**Yearly (1x/yıl):**
- Viscom Machine → All Electrical Cable Of Connection Checked
- Viscom Machine → Sensors and Switches Checked
- Viscom Machine → Magnet Checked
- Viscom Machine → Positioning Unit Lubricated
