/* ============================================================
   STEP 4: MAIN JAVASCRIPT (assets/js/main.js)
   Handles: navbar toggle, alerts, size selection,
   quantity controls, image preview, form validation
   ============================================================ */

// --- Run after DOM is ready ---
document.addEventListener('DOMContentLoaded', function () {

    // 1. MOBILE HAMBURGER MENU
    const hamburger = document.querySelector('.hamburger');
    const navbarNav = document.querySelector('.navbar-nav');
    if (hamburger && navbarNav) {
        hamburger.addEventListener('click', function () {
            navbarNav.classList.toggle('open');
            hamburger.classList.toggle('active');
        });
        // Close menu when a link is clicked
        navbarNav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => navbarNav.classList.remove('open'));
        });
    }

    // 2. AUTO-DISMISS ALERTS after 4 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.4s ease';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });

    // 3. SIZE SELECTOR BUTTONS (jersey detail page)
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled || this.classList.contains('out-of-stock')) return;
            // Deselect siblings
            this.closest('.size-selector').querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            // Update hidden input
            const sizeInput = document.getElementById('selected_size');
            if (sizeInput) sizeInput.value = this.dataset.size;
        });
    });

    // 4. QUANTITY CONTROL (+ / - buttons)
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.closest('.qty-control').querySelector('.qty-input');
            let val = parseInt(input.value) || 1;
            if (this.dataset.action === 'inc') val = Math.min(val + 1, 10);
            if (this.dataset.action === 'dec') val = Math.max(val - 1, 1);
            input.value = val;
        });
    });

    // 5. IMAGE UPLOAD PREVIEW (admin add/edit jersey)
    const imageInput = document.getElementById('jersey_image');
    const imagePreview = document.getElementById('image_preview');
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 6. CONFIRM DELETE
    document.querySelectorAll('.btn-confirm-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // 7. FORM VALIDATION (login & register)
    const authForm = document.getElementById('auth-form');
    if (authForm) {
        authForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let valid = true;

            clearErrors();

            if (email && !isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email address.');
                valid = false;
            }
            if (password && password.value.length < 6) {
                showError(password, 'Password must be at least 6 characters.');
                valid = false;
            }

            const confirmPass = document.getElementById('confirm_password');
            if (confirmPass && confirmPass.value !== password.value) {
                showError(confirmPass, 'Passwords do not match.');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    }

    // 8. LOADING SPINNER on form submit
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.dataset.noLoading) {
                btn.innerHTML = '<span class="spinner"></span> Please wait...';
                btn.disabled = true;
            }
        });
    });

    // 9. SIDEBAR ACTIVE LINK highlight
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar-nav-item a').forEach(link => {
        const href = link.getAttribute('href').split('/').pop().split('?')[0];
        if (href === currentPath) link.classList.add('active');
    });

    // 10. LIVE SEARCH in tables (admin panels)
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // 11. FILTER jerseys by category (shop page)
    const categoryFilter = document.getElementById('category-filter');
    const sizeFilter = document.getElementById('size-filter');
    if (categoryFilter || sizeFilter) {
        [categoryFilter, sizeFilter].forEach(el => {
            if (el) el.addEventListener('change', filterJerseys);
        });
    }

    // 12. STATS COUNTER ANIMATION (landing page)
    animateCounters();

}); // End DOMContentLoaded

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showError(input, message) {
    input.style.borderColor = 'var(--danger)';
    const err = document.createElement('span');
    err.className = 'field-error';
    err.style.cssText = 'color:var(--danger);font-size:0.8rem;display:block;margin-top:4px;';
    err.textContent = message;
    input.parentNode.appendChild(err);
}

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(e => e.remove());
    document.querySelectorAll('input, select').forEach(el => el.style.borderColor = '');
}

function filterJerseys() {
    const cat = document.getElementById('category-filter')?.value || '';
    const size = document.getElementById('size-filter')?.value || '';

    document.querySelectorAll('.jersey-card').forEach(card => {
        const cardCat  = card.dataset.category || '';
        const cardSizes = card.dataset.sizes || '';
        const catMatch  = !cat  || cardCat === cat;
        const sizeMatch = !size || cardSizes.includes(size);
        card.closest('.jersey-card-wrapper').style.display = (catMatch && sizeMatch) ? '' : 'none';
    });

    // Show empty state if no results
    const visible = [...document.querySelectorAll('.jersey-card-wrapper')].filter(c => c.style.display !== 'none');
    const emptyState = document.getElementById('empty-state');
    if (emptyState) emptyState.style.display = visible.length === 0 ? 'block' : 'none';
}

function animateCounters() {
    document.querySelectorAll('.counter').forEach(el => {
        const target = parseInt(el.dataset.target || el.textContent);
        if (isNaN(target)) return;
        let count = 0;
        const step = Math.max(1, Math.floor(target / 60));
        const timer = setInterval(() => {
            count = Math.min(count + step, target);
            el.textContent = count + (el.dataset.suffix || '');
            if (count >= target) clearInterval(timer);
        }, 25);
    });
}

// ============================================================
// CART FUNCTIONS (called from shop/detail pages)
// ============================================================

function updateCartCount(count) {
    const badge = document.getElementById('cart-count');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

// Show a toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed; bottom:25px; right:25px; z-index:9999;
        background:${type === 'success' ? 'var(--success)' : 'var(--danger)'};
        color:white; padding:14px 24px; border-radius:8px;
        font-weight:600; font-size:0.95rem; box-shadow:0 4px 20px rgba(0,0,0,0.3);
        animation: slideInUp 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.4s'; setTimeout(() => toast.remove(), 400); }, 3000);
}

// CSS for toast animation (injected once)
if (!document.getElementById('toast-style')) {
    const s = document.createElement('style');
    s.id = 'toast-style';
    s.textContent = `
        @keyframes slideInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .spinner { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,0.4); border-top-color:#fff; border-radius:50%; animation:spin 0.6s linear infinite; vertical-align:middle; }
        @keyframes spin { to { transform:rotate(360deg); } }
    `;
    document.head.appendChild(s);
}