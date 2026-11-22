@echo off
setlocal enabledelayedexpansion

echo =========================================================
echo   SKRIP PEMULIHAN MYSQL XAMPP - AMAN UNTUK DATA SKRIPSI
echo =========================================================
echo.
echo Skrip ini akan memperbaiki MariaDB/MySQL XAMPP yang crash
echo TANPA menghapus database ekraf skripsi Anda.
echo.
echo PERHATIAN:
echo Pastikan tombol MySQL di XAMPP Control Panel sedang STOP.
echo.
pause

:: 1. Pastikan mysqld mati sepenuhnya
taskkill /F /IM mysqld.exe >nul 2>&1

:: 2. Cek apakah folder C:\xampp\mysql ada
if not exist "C:\xampp\mysql\data" (
    echo [ERROR] Folder C:\xampp\mysql\data tidak ditemukan!
    pause
    exit /b 1
)

if not exist "C:\xampp\mysql\backup" (
    echo [ERROR] Folder C:\xampp\mysql\backup tidak ditemukan!
    pause
    exit /b 1
)

:: 3. Backup folder data lama ke data_rusak
echo [1/5] Membuat cadangan folder data ke data_rusak...
if exist "C:\xampp\mysql\data_rusak" (
    echo Menghapus backup data_rusak lama...
    rmdir /S /Q "C:\xampp\mysql\data_rusak"
)
move "C:\xampp\mysql\data" "C:\xampp\mysql\data_rusak"
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Gagal memindahkan folder data. Pastikan XAMPP dan program lain tidak sedang mengunci file!
    pause
    exit /b 1
)

:: 4. Buat folder data baru dan salin file sistem bersih dari backup
echo [2/5] Menyiapkan struktur MySQL baru dari folder backup...
mkdir "C:\xampp\mysql\data"
xcopy /E /I /Q /Y "C:\xampp\mysql\backup\*" "C:\xampp\mysql\data\"

:: 5. Kembalikan folder database user (ekraf, dll) dari data_rusak
echo [3/5] Mengembalikan folder database skripsi (ekraf)...
if exist "C:\xampp\mysql\data_rusak\ekraf" (
    xcopy /E /I /Q /Y "C:\xampp\mysql\data_rusak\ekraf" "C:\xampp\mysql\data\ekraf\"
    echo       - Database 'ekraf' berhasil dikembalikan.
)
if exist "C:\xampp\mysql\data_rusak\ekraf_batch_test" (
    xcopy /E /I /Q /Y "C:\xampp\mysql\data_rusak\ekraf_batch_test" "C:\xampp\mysql\data\ekraf_batch_test\"
    echo       - Database 'ekraf_batch_test' berhasil dikembalikan.
)
if exist "C:\xampp\mysql\data_rusak\kdmp_test" (
    xcopy /E /I /Q /Y "C:\xampp\mysql\data_rusak\kdmp_test" "C:\xampp\mysql\data\kdmp_test\"
    echo       - Database 'kdmp_test' berhasil dikembalikan.
)

:: 6. Kembalikan file kunci ibdata1 (Jantung data InnoDB)
echo [4/5] Mengembalikan file ibdata1 (indeks & tabel InnoDB)...
if exist "C:\xampp\mysql\data_rusak\ibdata1" (
    copy /Y "C:\xampp\mysql\data_rusak\ibdata1" "C:\xampp\mysql\data\ibdata1" >nul
    echo       - File 'ibdata1' berhasil dipulihkan.
) else (
    echo [PERINGATAN] File ibdata1 tidak ditemukan di data_rusak!
)

echo [5/5] Selesai!
echo.
echo =========================================================
echo   PEMULIHAN SELESAI!
echo =========================================================
echo Silakan buka XAMPP Control Panel sekarang, lalu klik tombol
echo [Start] pada MySQL.
echo.
pause
