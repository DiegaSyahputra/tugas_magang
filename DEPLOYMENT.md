# Panduan Deployment Gratis 100% (Render.com + Cloud MySQL)

Dokumen ini berisi panduan langkah demi langkah untuk men-deploy aplikasi **SkillSet Academy Platform** beserta databasenya ke layanan Cloud gratisan secara permanen.

---

## ☁️ Langkah 1: Siapkan Database Cloud MySQL Gratis

Anda dapat menggunakan salah satu penyedia database MySQL gratisan berikut:
1. **Aiven.io** (Recommended - Gratis 1 GB Free Tier Database Cloud MySQL)
2. **db4free.net** (Gratis database MySQL untuk pengujian)
3. **FreeSQLDatabase.com** (Gratis 5 MB database)

### Setelah membuat database cloud, catat kredensial berikut:
- `DB_HOST`: Host/server database cloud (contoh: `mysql-xxx.aivencloud.com` / `db4free.net`)
- `DB_PORT`: `3306`
- `DB_DATABASE`: Nama database (contoh: `skillset_db`)
- `DB_USERNAME`: Username database
- `DB_PASSWORD`: Password database

---

## 🌐 Langkah 2: Deploy Aplikasi ke Render.com (Web Service Gratis)

1. **Push Kode ke GitHub**:
   - Simpan seluruh berkas proyek ini ke repository GitHub Anda (Public/Private).

2. **Daftar & Hubungkan ke Render.com**:
   - Buka [https://render.com](https://render.com) dan masuk menggunakan akun GitHub.
   - Klik tombol **"New +"** &rarr; Pilih **"Web Service"**.
   - Pilih Repository GitHub proyek SkillSet Anda.

3. **Atur Environment Variables di Dashboard Render**:
   Tambahkan variabel berikut pada menu **Environment**:
   - `APP_NAME` = `SkillSet Academy`
   - `APP_ENV` = `production`
   - `APP_KEY` = `base64:3m7a...` (bisa di-generate atau ambil dari `.env` lokal)
   - `APP_DEBUG` = `false`
   - `APP_URL` = `https://<nama-app-anda>.onrender.com`
   - `DB_CONNECTION` = `mysql`
   - `DB_HOST` = `<DB_HOST_CLOUD>`
   - `DB_PORT` = `3306`
   - `DB_DATABASE` = `<DB_NAME_CLOUD>`
   - `DB_USERNAME` = `<DB_USER_CLOUD>`
   - `DB_PASSWORD` = `<DB_PASS_CLOUD>`

4. **Jalankan Migrasi & Seeder Database di Cloud**:
   - Buka tab **Shell** di dashboard Render web service Anda.
   - Jalankan perintah:
     ```bash
     php artisan migrate:fresh --seed --force
     php artisan storage:link
     ```

5. **Selesai!** Aplikasi Anda kini aktif secara publik dan dapat diakses siapa saja melalui URL `https://<nama-app-anda>.onrender.com`.

---

## ⚡ Langkah 3: Akses Publik Instan (Opsi Alternatif Tanpa Build Cloud)

Jika Anda ingin orang lain mengakses server lokal Anda sekarang secara instan tanpa perlu mendaftar cloud:

Jalankan perintah berikut di terminal:
```bash
npx localtunnel --port 8000
```
Link publik HTTPS akan muncul (contoh: `https://skillset-course.loca.lt`) dan dapat langsung dikirimkan ke orang lain untuk diakses!
