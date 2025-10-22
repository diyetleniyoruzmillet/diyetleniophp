# 🚀 Web-Based Migration Runner

## ✅ EN KOLAY YÖNTEM: Tarayıcıdan Çalıştır!

### 1. Admin Olarak Giriş Yap
Önce admin kullanıcısı olarak giriş yapın:
- URL: https://www.diyetlenio.com/login.php
- Email: admin@diyetlenio.com
- Şifre: Admin123!

### 2. Migration Sayfasını Aç

Tarayıcıda şu URL'i açın:

```
https://www.diyetlenio.com/admin/run-migrations.php?token=a847a0a04aec0065ac0e5b0399caa2b9
```

### 3. Bekle!

Sayfa otomatik olarak şunları yapacak:
- ✅ client_profiles tablosunu oluştur
- ✅ weight_tracking tablosunu oluştur
- ✅ Tüm kullanıcı isimlerini düzgün formata çevir
- ✅ Diğer eksik migration'ları çalıştır

### 4. Sonucu Gör

Sayfa yeşil ✓ işaretleriyle başarılı migration'ları gösterecek.

---

## 🧪 Test Et

Migration tamamlandıktan sonra:

1. **Client Profile Sayfası:**
   - https://www.diyetlenio.com/client/profile.php

2. **Weight Tracking Sayfası:**
   - https://www.diyetlenio.com/client/weight-tracking.php

3. **Kullanıcı Listesi (isimlerin düzgün olduğunu kontrol et):**
   - https://www.diyetlenio.com/admin/users.php

---

## 🔒 Güvenlik

- Admin authentication gereklidir
- Security token ile korumalı
- Birden fazla çalıştırılabilir (güvenli)

---

## ⚠️ ÖNEMLİ

Migration tamamlandıktan sonra dosyayı silin:

```bash
rm /home/monster/diyetlenio/public/admin/run-migrations.php
```

---

## 🛑 Sorun mu yaşıyorsunuz?

Alternatif metot için `DEPLOY_NOW.md` dosyasına bakın.

---

**Hemen başla:** https://www.diyetlenio.com/admin/run-migrations.php?token=a847a0a04aec0065ac0e5b0399caa2b9
