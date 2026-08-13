/**
 * assets/js/custom_order.js
 * Ringkasan harga langsung dan pengaturan area desain pada halaman
 * pelanggan/custom_order.php.
 *
 * Angka yang ditampilkan di sini hanya pratinjau. Harga yang tersimpan selalu
 * dihitung ulang di sisi server memakai data master dari basis data, sehingga
 * perubahan apa pun dari peramban tidak dapat memengaruhi harga pesanan.
 *
 * Seluruh nilai yang dibutuhkan sudah menempel pada HTML (data-faktor,
 * data-harga, data-nama, dan atribut min pada isian jumlah), jadi berkas ini
 * tidak perlu disisipi nilai dari PHP sama sekali.
 */
(function () {
    'use strict';

    var elQty = document.getElementById('qty');
    if (!elQty) return;   // form tidak ditampilkan, mis. data master belum ada

    var MIN_QTY = parseInt(elQty.min, 10) || 1;

    var elModel   = document.getElementById('r-model');
    var elBahan   = document.getElementById('r-bahan');
    var elRumus   = document.getElementById('r-rumus');
    var elPerPcs  = document.getElementById('r-perpcs');
    var elQtyView = document.getElementById('r-qty');
    var elTotal   = document.getElementById('r-total');

    function rupiah(n) {
        return 'Rp' + Math.round(n).toLocaleString('id-ID');
    }

    function hitung() {
        var model = document.querySelector('input[name="model_id"]:checked');
        var bahan = document.querySelector('input[name="bahan_id"]:checked');
        var qty   = Math.max(parseInt(elQty.value, 10) || 0, 0);

        elQtyView.textContent = qty + ' pcs';
        elModel.textContent = model ? model.dataset.nama : '—';
        elBahan.textContent = bahan ? bahan.dataset.nama : '—';

        if (!model || !bahan) {
            elRumus.textContent  = 'Pilih model tas dan bahan terlebih dahulu.';
            elPerPcs.textContent = '—';
            elTotal.textContent  = 'Rp0';
            return;
        }

        var hargaBahan = parseFloat(bahan.dataset.harga);
        var faktor     = parseFloat(model.dataset.faktor);
        var perPcs     = hargaBahan * faktor;
        var total      = perPcs * qty;

        elRumus.innerHTML =
            rupiah(hargaBahan) + ' &times; ' + faktor.toLocaleString('id-ID') +
            ' = ' + rupiah(perPcs) + ' / pcs<br>' +
            rupiah(perPcs) + ' &times; ' + qty + ' pcs = ' + rupiah(total);

        elPerPcs.textContent = rupiah(perPcs);
        elTotal.textContent  = rupiah(total);
    }

    function aturAreaDesain() {
        var pakaiUpload = document.getElementById('sd-upload').checked;

        document.getElementById('area-template').classList.toggle('d-none', pakaiUpload);
        document.getElementById('area-upload').classList.toggle('d-none', !pakaiUpload);

        // Isian wajib disesuaikan agar validasi peramban tidak menghalangi.
        document.querySelectorAll('input[name="desain_id"]').forEach(function (el) {
            el.required = !pakaiUpload;
        });
        document.getElementById('file_desain').required = pakaiUpload;
    }

    document.querySelectorAll('input[name="model_id"], input[name="bahan_id"]')
        .forEach(function (el) { el.addEventListener('change', hitung); });

    document.querySelectorAll('input[name="sumber_desain"]')
        .forEach(function (el) { el.addEventListener('change', aturAreaDesain); });

    elQty.addEventListener('input', hitung);

    elQty.addEventListener('blur', function () {
        if ((parseInt(elQty.value, 10) || 0) < MIN_QTY) {
            elQty.value = MIN_QTY;
            hitung();
        }
    });

    aturAreaDesain();
    hitung();
})();
