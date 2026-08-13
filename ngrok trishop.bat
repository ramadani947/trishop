@echo off
title ngrok - Tri Shop Souvenir
color 0B

set NGROK=C:\laragon\bin\ngrok\ngrok.exe
set DOMAIN=unfailing-stamp-humongous.ngrok-free.dev

echo.
echo   ================================================================
echo    TRI SHOP SOUVENIR - terowongan ngrok untuk webhook Midtrans
echo   ================================================================
echo.

rem --- Periksa ngrok memang ada di tempatnya -------------------------
if not exist "%NGROK%" (
    echo   [X] ngrok tidak ditemukan di:
    echo       %NGROK%
    echo.
    echo   Perbaiki baris "set NGROK=" di dalam berkas ini.
    echo.
    pause
    exit /b 1
)

rem --- Periksa Apache sudah menyala ---------------------------------
rem Tanpa langkah ini, ngrok tetap menyala tetapi meneruskan ke server
rem yang tidak ada, dan Midtrans hanya menerima galat tanpa penjelasan.
netstat -ano | findstr ":80 " | findstr LISTENING >nul
if errorlevel 1 (
    echo   [X] Apache belum jalan pada port 80.
    echo.
    echo       Nyalakan Laragon dulu ^(tombol "Start All"^),
    echo       baru jalankan lagi berkas ini.
    echo.
    pause
    exit /b 1
)
echo   [v] Apache sudah jalan pada port 80.

rem --- Periksa MySQL, sebab pesanan disimpan di sana -----------------
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo   [!] MySQL sepertinya belum jalan. Pembayaran bisa gagal disimpan.
) else (
    echo   [v] MySQL sudah jalan.
)

echo.
echo   URL webhook Midtrans:
echo   https://%DOMAIN%/trishop/payment/callback.php
echo.
echo   Panel pemantau ngrok akan terbuka di peramban:
echo   http://127.0.0.1:4040
echo.
echo   JANGAN tutup jendela ini selama menguji pembayaran.
echo   Untuk mematikan: tekan Ctrl+C atau tutup jendela ini.
echo.
echo   ----------------------------------------------------------------
echo.

rem Panel pemantau dibuka agak telat supaya ngrok sempat menyalakannya.
start "" /min cmd /c "timeout /t 4 >nul & start http://127.0.0.1:4040"

"%NGROK%" http 80 --url=%DOMAIN%

rem Baris di bawah hanya tercapai bila ngrok berhenti atau gagal menyala,
rem sehingga pesan galatnya sempat terbaca sebelum jendela tertutup.
echo.
echo   ngrok berhenti.
echo.
pause
