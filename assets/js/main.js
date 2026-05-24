/**
 * Main JavaScript – Wentworth Lost and Found Management System
 */
document.addEventListener('DOMContentLoaded', function () {

    // ================================================================
    // MOBILE NAVIGATION TOGGLE
    // ================================================================
    const navToggle = document.getElementById('navToggle');
    const navLinks  = document.getElementById('navLinks');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('open');
            }
        });
    }

    // ================================================================
    // IMAGE PREVIEW ON FILE UPLOAD
    // ================================================================
    const imageInput   = document.querySelector('#image');
    const imagePreview = document.querySelector('#imagePreview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showFieldError(imageInput, 'Only JPG, PNG and GIF images are allowed.');
                imagePreview.style.display = 'none';
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showFieldError(imageInput, 'Image size must be less than 5 MB.');
                imagePreview.style.display = 'none';
                this.value = '';
                return;
            }

            clearFieldError(imageInput);
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    // ================================================================
    // LIVE SEARCH & FILTER (Browse Page)
    // ================================================================
    const searchInput    = document.getElementById('searchInput');
    const filterType     = document.getElementById('filterType');
    const filterCategory = document.getElementById('filterCategory');
    const filterStatus   = document.getElementById('filterStatus');
    const itemCards      = document.querySelectorAll('.item-card');
    const noResultsMsg   = document.getElementById('noResults');

    function filterItems() {
        const searchVal   = (searchInput    ? searchInput.value.toLowerCase().trim()    : '');
        const typeVal     = (filterType     ? filterType.value.toLowerCase()             : '');
        const categoryVal = (filterCategory ? filterCategory.value.toLowerCase()         : '');
        const statusVal   = (filterStatus   ? filterStatus.value.toLowerCase()           : '');

        let visible = 0;
        itemCards.forEach(card => {
            const name        = (card.dataset.name        || '').toLowerCase();
            const type        = (card.dataset.type        || '').toLowerCase();
            const category    = (card.dataset.category    || '').toLowerCase();
            const location    = (card.dataset.location    || '').toLowerCase();
            const description = (card.dataset.description || '').toLowerCase();
            const status      = (card.dataset.status      || '').toLowerCase();

            const ok =
                (!searchVal   || name.includes(searchVal) || location.includes(searchVal) || description.includes(searchVal)) &&
                (!typeVal     || type === typeVal) &&
                (!categoryVal || category.includes(categoryVal)) &&
                (!statusVal   || status === statusVal);

            card.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });

        if (noResultsMsg) noResultsMsg.style.display = visible === 0 ? 'block' : 'none';
    }

    if (searchInput)    searchInput.addEventListener('input', filterItems);
    if (filterType)     filterType.addEventListener('change', filterItems);
    if (filterCategory) filterCategory.addEventListener('change', filterItems);
    if (filterStatus)   filterStatus.addEventListener('change', filterItems);

    // ================================================================
    // CLIENT-SIDE FORM VALIDATION
    // ================================================================
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function (e) {
            let valid = true;

            // Clear previous errors
            this.querySelectorAll('.field-error').forEach(el => el.remove());
            this.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));

            // Required fields
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    showFieldError(field, 'This field is required.');
                    valid = false;
                }
            });

            // Email format
            this.querySelectorAll('input[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    showFieldError(field, 'Please enter a valid email address.');
                    valid = false;
                }
            });

            // Password minimum length
            const pw = this.querySelector('#password');
            if (pw && pw.value && pw.value.length < 6) {
                showFieldError(pw, 'Password must be at least 6 characters.');
                valid = false;
            }

            // Confirm password match
            const cpw = this.querySelector('#confirm_password');
            if (pw && cpw && pw.value !== cpw.value) {
                showFieldError(cpw, 'Passwords do not match.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                const first = this.querySelector('.form-control.error');
                if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    // ================================================================
    // CONFIRMATION DIALOGS (Admin destructive actions)
    // ================================================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ================================================================
    // TABS (My Reports page)
    // ================================================================
    const tabBtns     = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const target = document.getElementById(this.dataset.tab);
            if (target) target.classList.add('active');
        });
    });

    // Activate tab from URL hash
    const hashTab = window.location.hash.slice(1);
    if (hashTab) {
        const btn = document.querySelector(`.tab-btn[data-tab="${hashTab}"]`);
        if (btn) btn.click();
    } else if (tabBtns.length > 0) {
        tabBtns[0].classList.add('active');
        const firstContent = document.getElementById(tabBtns[0].dataset.tab);
        if (firstContent) firstContent.classList.add('active');
    }

    // ================================================================
    // AUTO-DISMISS ALERTS after 5 seconds
    // ================================================================
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 520);
        }, 5000);
    });

    // ================================================================
    // HELPERS
    // ================================================================
    function showFieldError(field, message) {
        field.classList.add('error');
        const div = document.createElement('div');
        div.className = 'field-error';
        div.style.cssText = 'color:#dc3545;font-size:.81rem;margin-top:4px;';
        div.textContent = message;
        field.parentNode.insertBefore(div, field.nextSibling);
    }

    function clearFieldError(field) {
        field.classList.remove('error');
        const next = field.nextElementSibling;
        if (next && next.classList.contains('field-error')) next.remove();
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
