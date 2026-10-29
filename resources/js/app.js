import Alpine from 'alpinejs';

/**
 * Peringatan bila meninggalkan halaman dengan perubahan form yang belum disimpan.
 */
Alpine.data('dirtyForm', () => ({
    dirty: false,
    submitting: false,
    init() {
        this.$el.addEventListener('input', () => (this.dirty = true));
        this.$el.addEventListener('change', () => (this.dirty = true));
        this.$el.addEventListener('submit', () => (this.submitting = true));
        window.addEventListener('beforeunload', (e) => {
            if (this.dirty && !this.submitting) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },
}));

/**
 * Form yang dikirim lewat fetch dengan status simpan yang jelas dan percobaan ulang otomatis.
 * Isi form tetap di halaman (memori) bila koneksi terputus; tidak disimpan ke localStorage.
 */
Alpine.data('ajaxForm', (opts = {}) => ({
    state: 'idle', // idle | saving | retrying | saved | failed | invalid
    message: '',
    errors: {},
    attempt: 0,
    dirty: false,
    init() {
        this.$el.addEventListener('input', () => (this.dirty = true));
        window.addEventListener('beforeunload', (e) => {
            if (this.dirty && this.state !== 'saved') {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },
    error(field) {
        return this.errors[field] ? this.errors[field][0] : '';
    },
    async submit() {
        const form = this.$el.tagName === 'FORM' ? this.$el : this.$el.querySelector('form');
        const maxAttempts = 4;
        this.errors = {};
        for (this.attempt = 1; this.attempt <= maxAttempts; this.attempt++) {
            this.state = this.attempt === 1 ? 'saving' : 'retrying';
            this.message = this.attempt === 1 ? 'Menyimpan…' : `Koneksi bermasalah, mencoba kembali (${this.attempt}/${maxAttempts})…`;
            let response;
            try {
                response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
            } catch (e) {
                await new Promise((r) => setTimeout(r, 1500 * this.attempt));
                continue;
            }
            if (response.ok) {
                const data = await response.json().catch(() => ({}));
                this.state = 'saved';
                this.dirty = false;
                this.message = data.message || 'Tersimpan.';
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
                return;
            }
            if (response.status === 422) {
                const data = await response.json();
                this.errors = data.errors || {};
                this.state = 'invalid';
                this.message = 'Belum tersimpan — periksa isian yang ditandai merah.';
                return;
            }
            if (response.status === 419 || response.status === 401) {
                this.state = 'failed';
                this.message = 'Sesi Anda telah berakhir. Data BELUM tersimpan. Salin isian Anda, lalu muat ulang halaman dan login kembali.';
                return;
            }
            if (response.status >= 500 || response.status === 429) {
                await new Promise((r) => setTimeout(r, 1500 * this.attempt));
                continue;
            }
            this.state = 'failed';
            this.message = 'Gagal menyimpan (kode ' + response.status + '). Data belum tersimpan.';
            return;
        }
        this.state = 'failed';
        this.message = 'Gagal menyimpan karena koneksi. Data BELUM tersimpan, isian Anda masih ada di form — tekan Simpan lagi saat sinyal membaik.';
    },
}));

/**
 * Logout otomatis setelah tidak aktif (komputer bersama). Server juga mengakhiri sesi.
 */
Alpine.data('idleLogout', (minutes) => ({
    remaining: minutes * 60,
    warnAt: 120,
    timer: null,
    init() {
        const reset = () => (this.remaining = minutes * 60);
        ['click', 'keydown', 'scroll', 'touchstart'].forEach((ev) => window.addEventListener(ev, reset, { passive: true }));
        this.timer = setInterval(() => {
            this.remaining--;
            if (this.remaining <= 0) {
                clearInterval(this.timer);
                document.getElementById('logout-form')?.submit();
            }
        }, 1000);
    },
    get warning() {
        return this.remaining <= this.warnAt;
    },
}));

window.Alpine = Alpine;
Alpine.start();
