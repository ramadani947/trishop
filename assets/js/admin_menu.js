/**
 * assets/js/admin_menu.js
 * Perilaku deretan menu pada bilah admin.
 *
 * Menunya berjajar ke samping dalam satu pita. Ketika pilihannya tidak muat,
 * pita itu digeser mendatar. Berkas ini menangani tiga hal yang membuat
 * geseran tersebut nyaman dipakai:
 *
 *   1. menu yang sedang aktif digeser ke dalam pandangan saat halaman dibuka,
 *      supaya admin langsung tahu ia sedang berada di mana;
 *   2. roda tetikus ikut menggeser pita, sebab pengguna desktop tidak punya
 *      gestur geser dan bilah gulirnya sengaja disembunyikan;
 *   3. penanda bayangan di tepi dinyalakan hanya ke arah yang masih ada
 *      isinya, sebagai isyarat bahwa menu masih bisa digeser.
 *
 * Berhenti dengan sendirinya bila seluruh menu sudah muat, misalnya di layar
 * lebar, sehingga tidak ada penanda maupun perilaku yang muncul sia-sia.
 */
(function () {
    'use strict';

    var menu = document.querySelector('#bilah-admin .menu-admin');
    if (!menu) return;

    /** Nyala-matikan penanda tepi sesuai sisa ruang di kiri dan kanan. */
    function perbaruiPenanda() {
        var sisaKanan = menu.scrollWidth - menu.clientWidth - menu.scrollLeft;

        menu.classList.toggle('bisa-geser-kiri', menu.scrollLeft > 1);
        menu.classList.toggle('bisa-geser-kanan', sisaKanan > 1);
    }

    // Menu aktif ditempatkan di tengah bila memungkinkan, supaya tetangga
    // kiri-kanannya ikut terlihat dan jelas bahwa pita ini masih bisa digeser.
    var aktif = menu.querySelector('.nav-link.active');

    if (aktif && menu.scrollWidth > menu.clientWidth) {
        // Dihitung dari posisi di layar, bukan offsetLeft, sebab induk offset
        // menu berpindah mengikuti bilah yang sticky.
        var kotakAktif = aktif.getBoundingClientRect();
        var kotakMenu  = menu.getBoundingClientRect();

        // scrollLeft dipakai, bukan scrollIntoView(), karena scrollIntoView
        // juga menggulir halaman secara tegak sehingga kontennya ikut melompat.
        var geser = (kotakAktif.left - kotakMenu.left)
            - (kotakMenu.width - kotakAktif.width) / 2;

        // Perataan awal dilakukan tanpa animasi supaya tidak terlihat
        // "meluncur sendiri" setiap kali halaman dibuka.
        var halus = menu.style.scrollBehavior;
        menu.style.scrollBehavior = 'auto';
        menu.scrollLeft += geser;
        menu.style.scrollBehavior = halus;
    }

    // Roda tetikus tegak diterjemahkan menjadi geseran mendatar. Hanya diambil
    // alih ketika pita memang masih bisa digeser ke arah itu, supaya di ujung
    // deretan halamannya tetap bisa digulir seperti biasa.
    menu.addEventListener('wheel', function (e) {
        if (e.deltaY === 0 || menu.scrollWidth <= menu.clientWidth) return;

        var mentok = e.deltaY < 0
            ? menu.scrollLeft <= 0
            : menu.scrollLeft >= menu.scrollWidth - menu.clientWidth - 1;

        if (mentok) return;

        e.preventDefault();
        menu.scrollLeft += e.deltaY;
    }, { passive: false });

    menu.addEventListener('scroll', perbaruiPenanda, { passive: true });
    window.addEventListener('resize', perbaruiPenanda);

    perbaruiPenanda();
})();
