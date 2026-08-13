/**
 * assets/js/sandi.js
 * Memasang tombol "Lihat / Sembunyikan" pada setiap isian kata sandi.
 *
 * Skrip ini bekerja otomatis: semua <input type="password"> pada halaman
 * yang memuatnya akan mendapat tombol, tanpa perlu menambah markup apa pun.
 * Isian kata sandi yang ditambahkan di kemudian hari ikut tertangani
 * dengan sendirinya, sehingga perilakunya tidak mungkin terlewat.
 *
 * Cukup sertakan sebelum penutup </body>:
 *   <script src="<BASE_URL>/assets/js/sandi.js"></script>
 *
 * Bila JavaScript mati, isian tetap berfungsi normal sebagai kolom sandi
 * biasa; hanya tombolnya yang tidak muncul.
 */
(function () {
    'use strict';

    var TEKS_LIHAT      = 'Lihat';
    var TEKS_SEMBUNYIKAN = 'Sembunyikan';

    var daftar = document.querySelectorAll('input[type="password"]');
    if (!daftar.length) return;

    daftar.forEach(function (input, urutan) {

        // Lewati bila sudah pernah dipasangi tombol.
        if (input.dataset.sandiSiap === '1') return;
        input.dataset.sandiSiap = '1';

        var induk = input.parentNode;
        var grup;

        // Isian yang sudah berada dalam .input-group cukup ditumpangi tombol.
        if (induk.classList && induk.classList.contains('input-group')) {
            grup = induk;
        } else {
            // Selain itu, isian dibungkus dulu agar tombol menempel rapi.
            grup = document.createElement('div');
            grup.className = 'input-group';
            induk.insertBefore(grup, input);
            grup.appendChild(input);
        }

        var tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'btn btn-outline-secondary tombol-sandi';
        tombol.textContent = TEKS_LIHAT;
        tombol.setAttribute('aria-pressed', 'false');
        tombol.setAttribute('aria-label', 'Tampilkan kata sandi');

        // Dihubungkan ke isiannya demi pembaca layar.
        if (!input.id) input.id = 'sandi-' + urutan;
        tombol.setAttribute('aria-controls', input.id);

        tombol.addEventListener('click', function () {
            var sedangTerlihat = input.type === 'text';

            input.type = sedangTerlihat ? 'password' : 'text';
            tombol.textContent = sedangTerlihat ? TEKS_LIHAT : TEKS_SEMBUNYIKAN;
            tombol.setAttribute('aria-pressed', sedangTerlihat ? 'false' : 'true');
            tombol.setAttribute(
                'aria-label',
                sedangTerlihat ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi'
            );

            input.focus();
        });

        grup.appendChild(tombol);
    });
})();
