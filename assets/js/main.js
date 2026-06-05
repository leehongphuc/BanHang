/* ============================================
   TechStore — main.js
   ============================================ */

// --- Scroll header shadow ---
(function () {
    const header = document.getElementById('header');
    if (!header) return;
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 64);
    }, { passive: true });
})();

// --- User dropdown toggle ---
(function () {
    const trigger = document.getElementById('user-trigger');
    const menu    = document.getElementById('user-menu');
    if (!trigger || !menu) return;

    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = menu.classList.toggle('open');
        trigger.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target) && e.target !== trigger) {
            menu.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });
})();

// --- Autocomplete search ---
(function () {
    const input = document.getElementById('search-input');
    const list  = document.getElementById('autocomplete-list');
    if (!input || !list) return;

    let timer;
    let activeIndex = -1;

    function highlight(text, query) {
        if (!query) return text;
        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return text.replace(new RegExp(`(${escaped})`, 'gi'),
            '<strong style="color:var(--clr-brand)">$1</strong>');
    }

    function getItems() {
        return list.querySelectorAll('a[role="option"]');
    }

    function setActive(idx) {
        const items = getItems();
        items.forEach((el, i) => {
            el.classList.toggle('ac-focus', i === idx);
            if (i === idx) el.setAttribute('aria-selected', 'true');
            else el.removeAttribute('aria-selected');
        });
        activeIndex = idx;
    }

    function closeList() {
        list.innerHTML = '';
        list.style.display = 'none';
        activeIndex = -1;
    }

    function fetchSuggestions(q) {
        fetch('api/autocomplete.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    list.innerHTML = '<div class="ac-empty">Không tìm thấy kết quả phù hợp</div>';
                    list.style.display = 'block';
                    return;
                }

                list.innerHTML = data.map((item, i) => {
                    const price = item.price
                        ? Number(item.price).toLocaleString('vi-VN') + 'đ'
                        : '';
                    const img = item.image
                        ? `<img src="assets/images/${item.image}" alt="" class="ac-thumb">`
                        : `<div class="ac-thumb-placeholder"></div>`;
                    return `<a href="product_detail.php?id=${item.id}"
                                role="option"
                                id="ac-item-${i}"
                                tabindex="-1">
                                ${img}
                                <span class="ac-info">
                                    <span class="ac-name">${highlight(item.name, q)}</span>
                                    ${price ? `<span class="ac-price">${price}</span>` : ''}
                                </span>
                            </a>`;
                }).join('');
                list.style.display = 'block';
                activeIndex = -1;
            })
            .catch(() => closeList());
    }

    // Input event
    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { closeList(); return; }
        timer = setTimeout(() => fetchSuggestions(q), 200);
    });

    // Keyboard navigation
    input.addEventListener('keydown', function (e) {
        const items = getItems();
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIndex + 1, items.length - 1));
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            items[activeIndex]?.click();
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!list.contains(e.target) && e.target !== input) closeList();
    });

    // ARIA
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', 'autocomplete-list');
    input.setAttribute('aria-expanded', 'false');

    const observer = new MutationObserver(() => {
        input.setAttribute('aria-expanded',
            list.style.display === 'block' ? 'true' : 'false');
    });
    observer.observe(list, { attributes: true, attributeFilter: ['style'] });
})();

// --- Wishlist toggle ---
function toggleWishlist(btn, productId) {
    if (!productId) return;
    
    btn.disabled = true;
    const formData = new FormData();
    formData.append('product_id', productId);
    
    fetch('api/toggle_wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.requireLogin) {
            showToast(data.message, 'info');
            setTimeout(() => {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }, 1200);
            return;
        }
        
        if (data.success) {
            const active = data.action === 'added';
            btn.setAttribute('aria-pressed', String(active));
            btn.classList.toggle('active', active);
            
            const svg = btn.querySelector('svg');
            if (svg) {
                svg.setAttribute('fill', active ? 'currentColor' : 'none');
            }
            
            showToast(data.message, 'success');
            
            const badge = document.getElementById('header-wishlist-badge');
            if (badge) {
                badge.textContent = data.wishlistCount;
                badge.style.display = data.wishlistCount > 0 ? 'flex' : 'none';
            }
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => {
        showToast('Có lỗi xảy ra, vui lòng thử lại.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// --- Toast notification ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || '✓'}</div>
        <span class="toast-text">${message}</span>
        <button class="toast-close" aria-label="Đóng thông báo" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('removing');
        toast.addEventListener('animationend', () => toast.remove());
    }, 3000);
}

// --- AJAX Add to Cart ---
document.querySelectorAll('.ajax-cart-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        
        // Find existing SVG to preserve it or just use text
        const hasSvg = submitBtn.querySelector('svg');
        if (hasSvg) {
            submitBtn.innerHTML = hasSvg.outerHTML + ' Đang thêm...';
        } else {
            submitBtn.innerHTML = 'Đang thêm...';
        }

        fetch('api/cart_add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.requireLogin) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                
                const badge = document.getElementById('header-cart-badge');
                if (badge) {
                    badge.textContent = data.cartCount;
                    badge.style.display = 'flex';
                }
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
                else alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showToast === 'function') showToast('Có lỗi xảy ra, vui lòng thử lại.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
