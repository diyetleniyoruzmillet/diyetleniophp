# Database Migration Kılavuzu

## 📋 Migration Durumu

Tüm migration dosyaları hazır ve bekliyor!

## 🚀 Migration'ları Çalıştırma (2 Yöntem)

### Yöntem 1: Web Arayüzü (ÖNERİLEN)

1. **Admin olarak giriş yapın:** https://www.diyetlenio.com/login.php

2. **Migration sayfasını açın:**
   ```
   https://www.diyetlenio.com/admin/run-migrations.php?token=a847a0a04aec0065ac0e5b0399caa2b9
   ```

3. **Sayfayı yenileyip sonuçları kontrol edin**

4. **Migration başarılı olduysa, dosyayı silin:**
   ```bash
   rm /home/monster/diyetlenio/public/admin/run-migrations.php
   ```

### Yöntem 2: Komut Satırı

```bash
cd /home/monster/diyetlenio
php scripts/simple-migrate.php
```

## 📝 Migration Dosyaları

1. ✅ `007_create_contact_messages_table.sql` - İletişim mesajları
2. ✅ `008_create_password_resets_table.sql` - Şifre sıfırlama
3. ✅ `009_create_article_comments_table.sql` - Blog yorumları
4. ✅ `010_add_search_indexes.sql` - Arama indexleri
5. ✅ `011_create_notifications_table.sql` - Bildirim sistemi
6. ✅ `add_is_on_call_column.sql` - Diyetisyen çağrı durumu
7. ✅ `add_diet_plan_meals.sql` - Diyet planı öğünleri
8. ✅ `add_iban_to_dietitians.sql` - IBAN ödeme sistemi

## 🔒 Güvenlik Notları

- Migration sayfası sadece admin kullanıcılar tarafından erişilebilir
- Token ile korunmuştur
- Migration tamamlandıktan sonra dosyayı **MUTLAKA SİLİN**

## ✅ Migration Sonrası Kontrol

Migration başarılı olduysa:

1. Tüm tablolar oluşturulmuş olmalı
2. Arama indexleri eklenmiş olmalı
3. Notification sistemi çalışır hale gelmiş olmalı
4. Blog yorumları aktif olmalı
5. Contact form mesajları kayıt edilebilir olmalı

## 🆘 Sorun Giderme

**Hata: "Access denied for user"**
- `.env` dosyasındaki DB credentials'ı kontrol edin
- Database kullanıcısının izinlerini kontrol edin

**Hata: "Table already exists"**
- Normal! Migration zaten çalıştırılmış demektir
- Bu tablonun migration'ını atlayabilirsiniz

**Hata: "File not found"**
- Migration dosyasının path'ini kontrol edin
- `database/migrations/` klasörünün varlığını kontrol edin

## 📞 İletişim

Sorun yaşarsanız:
- Log dosyalarını kontrol edin: `/var/log/apache2/error.log`
- Database loglarını kontrol edin
- Admin panelden sistem durumunu kontrol edin
