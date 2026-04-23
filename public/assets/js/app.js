/**
 * BAH Eczane - Genel JavaScript
 */

/* -----------------------------------------------
   Sidebar Toggle
----------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            if (content) content.classList.toggle('expanded');
        });
    }

    /* -----------------------------------------------
       Aktif nav linkini işaretle (fallback)
    ----------------------------------------------- */
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });

    /* -----------------------------------------------
       Dosya upload önizleme
    ----------------------------------------------- */
    const fileInputs = document.querySelectorAll('[data-preview]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (!preview) return;

            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    /* -----------------------------------------------
       Form submit çift tıklamayı engelle
    ----------------------------------------------- */
    document.querySelectorAll('form[data-once]').forEach(form => {
        form.addEventListener('submit', function () {
            const btn = this.querySelector('[type=submit]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>İşleniyor...';
            }
        });
    });

    /* -----------------------------------------------
       Onay gerektiren silme butonları
    ----------------------------------------------- */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Bu işlemi onaylıyor musunuz?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    /* -----------------------------------------------
       Sayı alanlarında negatif girişi engelle
    ----------------------------------------------- */
    document.querySelectorAll('input[data-positive]').forEach(input => {
        input.addEventListener('input', function () {
            if (parseFloat(this.value) < 0) this.value = '';
        });
    });
});
