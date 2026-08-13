/**
 * assets/js/keranjang.js
 * Seluruh perilaku keranjang belanja di sisi peramban, terdiri atas dua
 * bagian yang masing-masing berhenti sendiri bila penandanya tidak ada:
 *
 *   1. [data-tambah-keranjang] - menambah produk dari katalog dan halaman
 *      detail, tanpa memuat ulang halaman.
 *   2. [data-form-qty]         - mengubah jumlah item di halaman keranjang.
 *
 * Berkas ini dimuat includes/footer.php pada semua halaman pelanggan.
 * Bila JavaScript tidak aktif, seluruh form tetap terkirim seperti biasa dan
 * ditangani PHP dengan pesan flash, sehingga tidak ada fitur yang hilang.
 */

/* ==========================================================================
   1. Menambah produk ke keranjang
   --------------------------------------------------------------------------
   Cara pakai pada HTML:
     <form method="post" action="<BASE_URL>/pelanggan/tambah_keranjang.php"
           data-tambah-keranjang>
         <input type="hidden" name="produk_id" value="...">
         <input type="hidden" name="kembali"   value="index|detail">
         <input type="number" name="qty" value="1">   (opsional)
         <button>Tambah ke Keranjang</button>
     </form>
   ========================================================================== */
(function () {
    'use strict';

    var formList = document.querySelectorAll('[data-tambah-keranjang]');
    if (!formList.length) return;

    var jedaNotif = null;

    /** Menampilkan pemberitahuan melayang di sudut kanan bawah. */
    function tampilkanNotif(teks, berhasil) {
        var lama = document.getElementById('notif-keranjang');
        if (lama) lama.remove();
        clearTimeout(jedaNotif);

        var kotak = document.createElement('div');
        kotak.id = 'notif-keranjang';
        kotak.className = 'notif-keranjang' + (berhasil ? '' : ' notif-gagal');
        kotak.setAttribute('role', 'status');
        kotak.setAttribute('aria-live', 'polite');

        var ikon = document.createElement('span');
        ikon.className = 'notif-ikon';
        ikon.textContent = berhasil ? '✓' : '!';

        var pesan = document.createElement('span');
        pesan.textContent = teks;

        kotak.appendChild(ikon);
        kotak.appendChild(pesan);
        document.body.appendChild(kotak);

        jedaNotif = setTimeout(function () {
            kotak.classList.add('notif-keluar');
            setTimeout(function () { kotak.remove(); }, 220);
        }, 2600);
    }

    /** Memperbarui angka pada tombol Keranjang di bilah navigasi. */
    function perbaruiLencana(jumlah) {
        var tautan = document.querySelector('[data-tautan-keranjang]');
        if (!tautan || typeof jumlah !== 'number') return;

        var lencana = tautan.querySelector('.lencana-keranjang');

        if (!lencana) {
            lencana = document.createElement('span');
            lencana.className = 'lencana-keranjang';
            tautan.appendChild(lencana);
        }

        lencana.textContent = jumlah;

        // Animasi denyut dijalankan ulang dengan melepas lalu memasang kelas.
        lencana.classList.remove('lencana-berubah');
        void lencana.offsetWidth;
        lencana.classList.add('lencana-berubah');
    }

    /** Mengubah tampilan tombol sesaat sebagai tanda berhasil. */
    function tandaiTombolBerhasil(tombol, teksAsli) {
        tombol.classList.add('tombol-sukses');
        tombol.textContent = '✓ Ditambahkan';

        setTimeout(function () {
            tombol.classList.remove('tombol-sukses');
            tombol.textContent = teksAsli;
            tombol.disabled = false;
        }, 1600);
    }

    formList.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var tombol = form.querySelector('button[type="submit"], button:not([type])');
            if (!tombol || tombol.disabled) return;

            var teksAsli = tombol.textContent;
            tombol.disabled = true;

            var data = new URLSearchParams(new FormData(form));
            data.append('format', 'json');

            fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.perlu_login) {
                        window.location.href = d.url_login;
                        return;
                    }

                    if (d.sukses) {
                        perbaruiLencana(d.jumlah_item);
                        tampilkanNotif(d.pesan, true);
                        tandaiTombolBerhasil(tombol, teksAsli);
                    } else {
                        tampilkanNotif(d.pesan, false);
                        tombol.disabled = false;
                    }
                })
                .catch(function () {
                    tampilkanNotif('Gagal menambahkan ke keranjang. Periksa koneksi Anda.', false);
                    tombol.disabled = false;
                });
        });
    });
})();

/* ==========================================================================
   2. Mengubah jumlah item di halaman keranjang
   --------------------------------------------------------------------------
   Setiap baris memuat:
     <form method="post" data-form-qty data-id="<id keranjang>"> ... </form>
   Tanpa atribut action, sehingga form mengirim ke halaman keranjang itu
   sendiri, baik lewat fetch maupun lewat pengiriman biasa.

   Wadah ringkasan memakai [data-ringkasan] beserta data-ada-masalah.
   ========================================================================== */
(function () {
    'use strict';

    var daftarForm = document.querySelectorAll('[data-form-qty]');
    if (!daftarForm.length) return;

    // Tombol "Ubah" hanya disembunyikan, bukan dihapus dari HTML. Bila
    // JavaScript mati, tombolnya tetap muncul dan alur lama (kirim form lalu
    // muat ulang halaman) masih berfungsi seperti semula.
    document.querySelectorAll('[data-tombol-ubah]').forEach(function (b) {
        b.style.display = 'none';
    });

    var ringkasan       = document.querySelector('[data-ringkasan]');
    var adaMasalahAwal  = ringkasan.dataset.adaMasalah === '1';
    var elTotal         = document.getElementById('ringkasan-total');
    var elItem          = document.getElementById('ringkasan-item');

    function tampilkanPesan(tipe, teks) {
        var lama = document.getElementById('pesan-keranjang');
        if (lama) lama.remove();

        var div = document.createElement('div');
        div.id = 'pesan-keranjang';
        div.className = 'alert alert-' + tipe + ' alert-dismissible fade show';
        div.textContent = teks;

        var tutup = document.createElement('button');
        tutup.type = 'button';
        tutup.className = 'btn-close';
        tutup.setAttribute('data-bs-dismiss', 'alert');
        div.appendChild(tutup);

        // Wadah konten dibuat includes/header.php: <main><div class="container">
        var wadah = document.querySelector('#konten-utama .container');
        wadah.insertBefore(div, wadah.firstChild);
    }

    daftarForm.forEach(function (form) {
        var input    = form.querySelector('input[name="qty"]');
        var elSub    = form.closest('tr').querySelector('[data-subtotal]');
        var jeda     = null;
        var terakhir = input.value;

        function kirim() {
            var qty = parseInt(input.value, 10);

            // Isian kosong atau belum berubah tidak perlu dikirim.
            if (isNaN(qty) || String(qty) === String(terakhir)) return;

            var data = new URLSearchParams();
            data.append('aksi', 'ubah');
            data.append('id', form.dataset.id);
            data.append('qty', qty);
            data.append('format', 'json');

            input.disabled = true;

            // Tanpa atribut action, form.action berisi alamat halaman ini.
            fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.sukses) {
                        // Ditolak server: kembalikan isian ke nilai tersimpan.
                        input.value = d.qty;
                        tampilkanPesan('danger', d.pesan);
                        return;
                    }

                    terakhir = d.qty;
                    elSub.textContent   = d.subtotal;
                    elTotal.textContent = d.total;
                    elItem.textContent  = d.jumlah_item;

                    // Mengurangi jumlah bisa menghapus peringatan stok dan
                    // mengaktifkan kembali tombol checkout. Perubahan itu
                    // butuh penyajian ulang, jadi halaman dimuat ulang.
                    if (adaMasalahAwal && !d.ada_masalah) {
                        window.location.reload();
                    }
                })
                .catch(function () {
                    input.value = terakhir;
                    tampilkanPesan('warning', 'Gagal memperbarui jumlah. Periksa koneksi Anda.');
                })
                .finally(function () {
                    input.disabled = false;
                });
        }

        // Diberi jeda supaya mengetik "12" tidak mengirim dua permintaan.
        input.addEventListener('input', function () {
            clearTimeout(jeda);
            jeda = setTimeout(kirim, 500);
        });

        // Tombol panah, salin-tempel, dan berpindah fokus dikirim segera.
        input.addEventListener('change', function () {
            clearTimeout(jeda);
            kirim();
        });

        // Menekan Enter tidak boleh mengirim form secara konvensional,
        // karena itu akan memuat ulang halaman.
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            clearTimeout(jeda);
            kirim();
        });
    });
})();
