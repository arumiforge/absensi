/**
 * Photo preview before saving (docs/04 FS-MD-07 UI note, docs/09 HAL-MD-09).
 *
 * A file input with data-pratinjau-foto="<id of a hidden box with an <img>>"
 * shows the chosen JPG, PNG, or WebP in that box through a blob: URL
 * (allowed by the panel CSP, docs/12 SEC-36). Other files hide the box; the
 * server still checks every file (docs/11 VAL-01). Nothing is sent here.
 */

export const TIPE = ['image/jpeg', 'image/png', 'image/webp'];

/** True when the browser may preview this file as a photo. */
export function bisaDipratinjau(file) {
    return Boolean(file) && TIPE.includes(file.type);
}

function pasang(isian) {
    const kotak = document.getElementById(isian.dataset.pratinjauFoto);
    const gambar = kotak?.querySelector('img');
    if (!gambar) return;
    let alamat = null;

    const tutup = () => {
        if (alamat !== null) URL.revokeObjectURL(alamat);
        alamat = null;
        gambar.removeAttribute('src');
        kotak.hidden = true;
    };

    isian.addEventListener('change', () => {
        const file = isian.files?.[0];
        tutup();
        if (!bisaDipratinjau(file)) return;
        alamat = URL.createObjectURL(file);
        gambar.onerror = tutup;
        gambar.src = alamat;
        kotak.hidden = false;
    });
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-pratinjau-foto]').forEach(pasang);
}
