const API_BASE = '/api/v1';
const TOKEN_KEY = 'shop_access_token';
const CART_KEY = 'shop_cart';
const THEME_KEY = 'shop_theme';

const state = {
    token: localStorage.getItem(TOKEN_KEY),
    user: null,
    categories: [],
    products: [],
    productMeta: null,
    orders: [],
    orderMeta: null,
    notifications: [],
    cart: readCart(),
    productPage: 1,
    orderPage: 1,
    selectedCategory: '',
    search: '',
    busyCount: 0,
    productImage: null,
    productImagePromise: null,
};

const viewTitles = {
    overview: ['لوحة التحكم', 'نظرة عامة'],
    products: ['كتالوج المتجر', 'المنتجات'],
    orders: ['المتابعة والإدارة', 'الطلبات'],
    notifications: ['ابقَ على اطلاع', 'الإشعارات'],
    'add-product': ['إدارة المتجر', 'إضافة منتج'],
};

const statusLabels = {
    pending: 'قيد الانتظار',
    processing: 'قيد المعالجة',
    completed: 'مكتمل',
    cancelled: 'ملغي',
};

const icons = {
    box: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7 12 3 4 7l8 4 8-4ZM4 7v10l8 4 8-4V7"/></svg>',
    receipt: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6m-6 4h6"/></svg>',
    bell: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>',
    cart: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h2l2 13h10l3-9H6M9 21h.01M17 21h.01"/></svg>',
    empty: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7 12 3 4 7l8 4 8-4ZM4 7v10l8 4 8-4V7"/></svg>',
};

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

function readCart() {
    try {
        const cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        return Array.isArray(cart) ? cart.filter((item) => item && Number(item.id) > 0) : [];
    } catch {
        return [];
    }
}

function escapeHtml(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function safeImageUrl(value) {
    if (!value) return '';
    try {
        const url = new URL(value, window.location.origin);
        return ['http:', 'https:'].includes(url.protocol) ? escapeHtml(url.href) : '';
    } catch {
        return '';
    }
}

function money(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

function formatDate(value) {
    if (!value) return '—';
    const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(value)
        ? value.replace(' ', 'T')
        : value;
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return escapeHtml(value);
    return new Intl.DateTimeFormat('ar-SY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function firstValidationError(payload) {
    const errors = payload?.errors;
    if (!errors || typeof errors !== 'object') return null;
    const first = Object.values(errors).flat().find(Boolean);
    return first ? String(first) : null;
}

function errorMessage(error) {
    return firstValidationError(error?.payload) || error?.payload?.message || error?.message || 'تعذر تنفيذ العملية.';
}

function toast(message, type = 'success') {
    const element = document.createElement('div');
    element.className = `toast${type === 'error' ? ' error' : ''}`;
    element.textContent = message;
    $('#toast-region').append(element);
    window.setTimeout(() => element.remove(), 4300);
}

function setBusy(isBusy, message = 'جارٍ تحميل بيانات المتجر...') {
    state.busyCount = Math.max(0, state.busyCount + (isBusy ? 1 : -1));
    const layer = $('#loading-layer');
    $('p', layer).textContent = message;
    layer.hidden = state.busyCount === 0;
}

async function api(path, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');
    if (state.token) headers.set('Authorization', `Bearer ${state.token}`);

    let body = options.body;
    if (body && !(body instanceof FormData) && typeof body !== 'string') {
        headers.set('Content-Type', 'application/json');
        body = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(`${API_BASE}${path}`, { ...options, headers, body });
    } catch (cause) {
        const error = new Error('تعذر الاتصال بالخادم. تحقق من تشغيل المشروع ثم أعد المحاولة.');
        error.status = 0;
        error.cause = cause;
        throw error;
    }

    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        const error = new Error(payload.message || `فشل الطلب (${response.status})`);
        error.status = response.status;
        error.payload = payload;
        if (response.status === 401 && state.token) {
            clearSession('انتهت الجلسة. سجّل الدخول من جديد.');
        }
        throw error;
    }

    return payload;
}

function setAuthTab(tab) {
    const login = tab === 'login';
    $('#login-tab').classList.toggle('active', login);
    $('#register-tab').classList.toggle('active', !login);
    $('#login-tab').setAttribute('aria-selected', String(login));
    $('#register-tab').setAttribute('aria-selected', String(!login));
    $('#login-form').hidden = !login;
    $('#register-form').hidden = login;
}

function showAuth() {
    $('#auth-view').hidden = false;
    $('#app-shell').hidden = true;
    closeSidebar();
    closeCart();
    closeOrderModal();
}

function showApp() {
    $('#auth-view').hidden = true;
    $('#app-shell').hidden = false;
}

function saveToken(token) {
    state.token = token;
    localStorage.setItem(TOKEN_KEY, token);
}

function clearSession(message = null) {
    state.token = null;
    state.user = null;
    localStorage.removeItem(TOKEN_KEY);
    state.busyCount = 0;
    $('#loading-layer').hidden = true;
    showAuth();
    if (message) toast(message, 'error');
}

async function performLogin(email, password) {
    setBusy(true, 'جارٍ تسجيل الدخول...');
    try {
        const payload = await api('/login', {
            method: 'POST',
            body: { email, password },
        });
        saveToken(payload.access_token);
        await bootstrapSession();
        toast('مرحباً بك، تم تسجيل الدخول بنجاح.');
    } catch (error) {
        toast(errorMessage(error), 'error');
    } finally {
        setBusy(false);
    }
}

async function submitLogin(event) {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;
    await performLogin($('#login-email').value.trim(), $('#login-password').value);
}

async function submitRegister(event) {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;

    const password = $('#register-password').value;
    const confirmation = $('#register-password-confirmation').value;
    if (password !== confirmation) {
        toast('كلمتا المرور غير متطابقتين.', 'error');
        return;
    }

    setBusy(true, 'جارٍ إنشاء الحساب...');
    try {
        const payload = await api('/register', {
            method: 'POST',
            body: {
                name: $('#register-name').value.trim(),
                email: $('#register-email').value.trim(),
                password,
                password_confirmation: confirmation,
            },
        });
        saveToken(payload.access_token);
        await bootstrapSession();
        form.reset();
        toast('تم إنشاء حسابك بنجاح.');
    } catch (error) {
        toast(errorMessage(error), 'error');
    } finally {
        setBusy(false);
    }
}

async function logout() {
    setBusy(true, 'جارٍ تسجيل الخروج...');
    try {
        await api('/logout', { method: 'POST' });
    } catch (error) {
        if (error.status !== 401) toast(errorMessage(error), 'error');
    } finally {
        clearSession();
        setBusy(false);
    }
}

function configureUserInterface() {
    const admin = state.user?.role === 'admin';
    $$('.admin-only').forEach((element) => { element.hidden = !admin; });
    $$('.customer-only').forEach((element) => { element.hidden = admin; });

    const name = state.user?.name || 'المستخدم';
    $('#sidebar-user-name').textContent = name;
    $('#sidebar-user-role').textContent = admin ? 'مسؤول المتجر' : 'زبون';
    $('#sidebar-avatar').textContent = name.trim().charAt(0) || 'م';
    $('#welcome-name').textContent = name.split(/\s+/)[0];
    $('#welcome-copy').textContent = admin
        ? 'تابع المنتجات والطلبات وإدارة المتجر من لوحة واحدة.'
        : 'اكتشف أحدث المنتجات وتابع طلباتك من هنا.';
    $('#orders-heading').textContent = admin ? 'كل الطلبات' : 'طلباتي';
    $('#orders-description').textContent = admin
        ? 'راجع طلبات الزبائن وحدّث حالتها.'
        : 'تابع حالة طلباتك وتفاصيلها.';
    $('#orders-stat-label').textContent = admin ? 'كل الطلبات' : 'طلباتي';
}

async function bootstrapSession() {
    const me = await api('/me');
    state.user = me.data;
    configureUserInterface();
    showApp();
    showView('overview');
    renderCart();

    const results = await Promise.allSettled([
        loadCategories(),
        loadProducts(1),
        loadOrders(1),
        loadNotifications(),
    ]);
    const failed = results.filter((result) => result.status === 'rejected');
    if (failed.length) {
        toast(`تم تحميل الواجهة، لكن تعذر جلب ${failed.length} من أقسام البيانات.`, 'error');
    }
    renderOverview();
}

async function loadCategories() {
    const payload = await api('/categories');
    state.categories = Array.isArray(payload) ? payload : (payload.data || []);
    renderCategoryOptions();
}

function renderCategoryOptions() {
    const options = state.categories
        .map((category) => `<option value="${Number(category.id)}">${escapeHtml(category.name)}</option>`)
        .join('');
    const filter = $('#category-filter');
    const formSelect = $('#product-category');
    const selectedFilter = filter.value;
    const selectedForm = formSelect.value;
    filter.innerHTML = `<option value="">كل التصنيفات</option>${options}`;
    formSelect.innerHTML = `<option value="">اختر التصنيف</option>${options}`;
    filter.value = selectedFilter;
    formSelect.value = selectedForm;
}

async function loadProducts(page = 1) {
    state.productPage = page;
    $('#products-grid').innerHTML = '<div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div>';
    const params = new URLSearchParams({ page: String(page) });
    if (state.selectedCategory) params.set('category_id', state.selectedCategory);
    const payload = await api(`/products?${params}`);
    state.products = payload.data || [];
    state.productMeta = payload.meta || null;
    renderProducts();
    renderOverview();
}

function filteredProducts() {
    const term = state.search.trim().toLocaleLowerCase('ar');
    if (!term) return state.products;
    return state.products.filter((product) =>
        [product.name, product.description, product.category]
            .some((value) => String(value || '').toLocaleLowerCase('ar').includes(term))
    );
}

function productCard(product) {
    const image = safeImageUrl(product.image_url);
    const isAdmin = state.user?.role === 'admin';
    const availableQuantity = Math.max(0, Number(product.available_quantity || 0));
    const initial = escapeHtml(String(product.name || 'م').charAt(0));
    return `
        <article class="product-card">
            <div class="product-image">
                ${image
                    ? `<img src="${image}" alt="${escapeHtml(product.name)}" loading="lazy">`
                    : `<div class="product-placeholder" aria-hidden="true">${initial}</div>`}
                <span class="category-pill">${escapeHtml(product.category || 'غير مصنف')}</span>
            </div>
            <div class="product-body">
                <h3>${escapeHtml(product.name)}</h3>
                <p>${escapeHtml(product.description || 'لا يوجد وصف لهذا المنتج.')}</p>
                <div class="product-stock${availableQuantity > 0 ? ' available' : ' unavailable'}"><i></i>${availableQuantity > 0 ? `متوفر: ${availableQuantity}` : 'غير متوفر حالياً'}</div>
                <div class="product-footer">
                    <span class="product-price"><strong>${money(product.price)}</strong><small>للوحدة</small></span>
                    ${isAdmin ? '' : `<button class="add-cart-button" type="button"${availableQuantity > 0 ? ` data-add-cart="${Number(product.id)}"` : ' disabled'} aria-label="إضافة ${escapeHtml(product.name)} إلى السلة">${icons.cart}</button>`}
                </div>
            </div>
        </article>`;
}

function renderProducts() {
    const products = filteredProducts();
    const total = state.productMeta?.total ?? state.products.length;
    $('#products-meta').textContent = state.search
        ? `${products.length} نتيجة في الصفحة الحالية من أصل ${total} منتجاً`
        : `عرض ${state.products.length} من أصل ${total} منتجاً`;
    $('#products-grid').innerHTML = products.length
        ? products.map(productCard).join('')
        : emptyState('لا توجد منتجات مطابقة', 'جرّب تغيير البحث أو التصنيف المختار.');
    renderPagination($('#products-pagination'), state.productMeta, (page) => loadProducts(page));
}

async function loadOrders(page = 1) {
    state.orderPage = page;
    $('#orders-list').innerHTML = '<div class="skeleton"></div><div class="skeleton"></div>';
    const payload = await api(`/orders?page=${page}`);
    state.orders = payload.data || [];
    state.orderMeta = payload.meta || null;
    renderOrders();
    renderOverview();
}

function statusBadge(status) {
    const safeStatus = Object.hasOwn(statusLabels, status) ? status : 'pending';
    return `<span class="status-badge status-${safeStatus}">${statusLabels[safeStatus]}</span>`;
}

function orderCard(order) {
    const admin = state.user?.role === 'admin';
    const itemsCount = (order.items || []).reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const statusOptions = Object.entries(statusLabels)
        .map(([value, label]) => `<option value="${value}"${value === order.status ? ' selected' : ''}>${label}</option>`)
        .join('');
    return `
        <article class="order-card">
            <div class="order-main"><span class="order-icon">${icons.receipt}</span><div><strong>طلب #${Number(order.id)}</strong><small>${formatDate(order.created_at)}</small></div></div>
            <div class="order-info${admin ? ' user-info' : ''}"><small>${admin ? 'الزبون' : 'الحالة'}</small>${admin ? `<strong>#${Number(order.user_id)}</strong>` : statusBadge(order.status)}</div>
            <div class="order-info total"><small>${itemsCount} قطعة</small><strong>${money(order.total)}</strong></div>
            <div class="order-actions">
                ${admin ? `<select class="status-select" data-order-status="${Number(order.id)}" aria-label="حالة الطلب رقم ${Number(order.id)}">${statusOptions}</select>` : statusBadge(order.status)}
                <button class="details-button" type="button" data-order-details="${Number(order.id)}">التفاصيل</button>
            </div>
        </article>`;
}

function renderOrders() {
    $('#orders-list').innerHTML = state.orders.length
        ? state.orders.map(orderCard).join('')
        : emptyState('لا توجد طلبات بعد', state.user?.role === 'admin' ? 'ستظهر طلبات الزبائن هنا.' : 'أضف منتجات إلى السلة وأنشئ طلبك الأول.');
    renderPagination($('#orders-pagination'), state.orderMeta, (page) => loadOrders(page));
}

async function changeOrderStatus(orderId, status, select) {
    const previous = state.orders.find((order) => Number(order.id) === Number(orderId))?.status;
    select.disabled = true;
    try {
        const payload = await api(`/orders/${orderId}/status`, {
            method: 'PATCH',
            body: { status },
        });
        const index = state.orders.findIndex((order) => Number(order.id) === Number(orderId));
        if (index !== -1) state.orders[index] = payload.data;
        renderOrders();
        renderOverview();
        toast(`تم تحديث حالة الطلب #${orderId}.`);
    } catch (error) {
        select.value = previous || 'pending';
        select.disabled = false;
        toast(errorMessage(error), 'error');
    }
}

async function showOrderDetails(orderId) {
    setBusy(true, 'جارٍ تحميل تفاصيل الطلب...');
    try {
        const payload = await api(`/orders/${orderId}`);
        const order = payload.data;
        const items = order.items || [];
        $('#order-modal-body').innerHTML = `
            <h2 id="order-modal-title" class="modal-title">طلب #${Number(order.id)}</h2>
            <span class="modal-subtitle">أُنشئ في ${formatDate(order.created_at)}</span>
            <div class="modal-summary">
                <div><small>الحالة</small>${statusBadge(order.status)}</div>
                <div><small>عدد الأصناف</small><strong>${items.length}</strong></div>
                <div><small>الإجمالي</small><strong>${money(order.total)}</strong></div>
            </div>
            <div class="order-lines">
                ${items.length ? items.map((item) => `<div class="order-line"><span>${escapeHtml(item.name)}</span><span>${Number(item.quantity)} × ${money(item.price)}</span><strong>${money(Number(item.price) * Number(item.quantity))}</strong></div>`).join('') : '<p>لا توجد عناصر.</p>'}
            </div>`;
        $('#order-modal').hidden = false;
        document.body.classList.add('no-scroll');
    } catch (error) {
        toast(errorMessage(error), 'error');
    } finally {
        setBusy(false);
    }
}

function closeOrderModal() {
    $('#order-modal').hidden = true;
    document.body.classList.remove('no-scroll');
}

async function loadNotifications() {
    $('#notifications-list').innerHTML = '<div class="skeleton"></div><div class="skeleton"></div>';
    const payload = await api('/notifications');
    state.notifications = payload.notifications || [];
    renderNotifications();
    renderOverview();
}

function notificationTitle(notification) {
    return String(notification.type || '').includes('Product') ? 'منتج جديد' :
        String(notification.type || '').includes('Order') ? 'تحديث طلب' : 'إشعار جديد';
}

function renderNotifications() {
    $('#notifications-list').innerHTML = state.notifications.length
        ? state.notifications.map((notification) => `
            <article class="notification-row${notification.read_at ? '' : ' unread'}">
                <span class="notification-icon">${String(notification.type || '').includes('Product') ? icons.box : icons.bell}</span>
                <div><strong>${notificationTitle(notification)}</strong><p>${escapeHtml(notification.message || 'لديك إشعار جديد.')}</p></div>
                <div class="notification-time"><span>${formatDate(notification.created_at)}</span>${notification.read_at ? '<span>مقروء</span>' : `<button class="mark-read" type="button" data-mark-read="${escapeHtml(notification.id)}">تحديد كمقروء</button>`}</div>
            </article>`).join('')
        : emptyState('لا توجد إشعارات', 'ستظهر هنا تحديثات المنتجات والطلبات.');
    updateNotificationCounters();
}

async function markNotificationRead(id, button) {
    button.disabled = true;
    try {
        await api(`/notifications/${encodeURIComponent(id)}/read`, { method: 'PATCH' });
        const notification = state.notifications.find((item) => String(item.id) === String(id));
        if (notification) notification.read_at = new Date().toISOString();
        renderNotifications();
        renderOverview();
    } catch (error) {
        button.disabled = false;
        toast(errorMessage(error), 'error');
    }
}

function updateNotificationCounters() {
    const unread = state.notifications.filter((notification) => !notification.read_at).length;
    ['#sidebar-notification-count', '#top-notification-count'].forEach((selector) => {
        const counter = $(selector);
        counter.textContent = String(unread);
        counter.hidden = unread === 0;
    });
    $('#notifications-stat').textContent = String(unread);
}

function renderOverview() {
    $('#products-stat').textContent = String(state.productMeta?.total ?? state.products.length ?? 0);
    $('#orders-stat').textContent = String(state.orderMeta?.total ?? state.orders.length ?? 0);
    updateNotificationCounters();

    $('#recent-orders').innerHTML = state.orders.length
        ? state.orders.slice(0, 4).map((order) => `
            <div class="compact-row"><span class="compact-icon">${icons.receipt}</span><div><strong>طلب #${Number(order.id)}</strong><small>${formatDate(order.created_at)}</small></div>${statusBadge(order.status)}<span class="compact-total">${money(order.total)}</span></div>`).join('')
        : emptyState('لا توجد طلبات', 'لا يوجد نشاط لعرضه حالياً.');

    $('#recent-products').innerHTML = state.products.length
        ? state.products.slice(0, 4).map((product) => {
            const image = safeImageUrl(product.image_url);
            return `<div class="mini-product">${image ? `<img src="${image}" alt="" loading="lazy">` : `<span class="mini-product-placeholder">${escapeHtml(String(product.name || 'م').charAt(0))}</span>`}<div><strong>${escapeHtml(product.name)}</strong><small>${money(product.price)}</small></div></div>`;
        }).join('')
        : emptyState('لا توجد منتجات', 'أضف أول منتج إلى المتجر.');
}

function renderPagination(container, meta, onPage) {
    container.innerHTML = '';
    if (!meta || Number(meta.last_page) <= 1) return;
    const current = Number(meta.current_page || 1);
    const last = Number(meta.last_page || 1);
    const pages = new Set([1, last]);
    for (let page = Math.max(1, current - 2); page <= Math.min(last, current + 2); page += 1) pages.add(page);
    [...pages].sort((a, b) => a - b).forEach((page, index, list) => {
        if (index && page - list[index - 1] > 1) {
            const gap = document.createElement('span');
            gap.textContent = '…';
            container.append(gap);
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = String(page);
        button.classList.toggle('active', page === current);
        button.setAttribute('aria-label', `الصفحة ${page}`);
        button.addEventListener('click', () => onPage(page));
        container.append(button);
    });
}

function emptyState(title, description) {
    return `<div class="empty-state">${icons.empty}<h3>${escapeHtml(title)}</h3><p>${escapeHtml(description)}</p></div>`;
}

function persistCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(state.cart));
}

function addToCart(productId) {
    const product = state.products.find((item) => Number(item.id) === Number(productId));
    if (!product) return;
    const availableQuantity = Math.max(0, Number(product.available_quantity || 0));
    const existing = state.cart.find((item) => Number(item.id) === Number(productId));
    if (availableQuantity === 0 || Number(existing?.quantity || 0) >= availableQuantity) {
        toast('لا توجد كمية إضافية متوفرة من هذا المنتج.', 'error');
        return;
    }
    if (existing) {
        existing.quantity += 1;
    } else {
        state.cart.push({
            id: Number(product.id),
            name: product.name,
            price: Number(product.price),
            image_url: product.image_url || null,
            available_quantity: availableQuantity,
            quantity: 1,
        });
    }
    persistCart();
    renderCart();
    toast(`أُضيف «${product.name}» إلى السلة.`);
}

function changeCartQuantity(productId, delta) {
    const item = state.cart.find((entry) => Number(entry.id) === Number(productId));
    if (!item) return;
    const maximum = Math.max(1, Number(item.available_quantity || Number.MAX_SAFE_INTEGER));
    item.quantity = Math.min(maximum, Math.max(1, Number(item.quantity) + delta));
    persistCart();
    renderCart();
}

function removeCartItem(productId) {
    state.cart = state.cart.filter((entry) => Number(entry.id) !== Number(productId));
    persistCart();
    renderCart();
}

function renderCart() {
    const count = state.cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const total = state.cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.quantity || 0), 0);
    $('#cart-count').textContent = String(count);
    $('#cart-total').textContent = money(total);
    $('#checkout-button').disabled = state.cart.length === 0;
    $('#cart-items').innerHTML = state.cart.length
        ? state.cart.map((item) => {
            const image = safeImageUrl(item.image_url);
            return `<div class="cart-item">${image ? `<img src="${image}" alt="">` : `<span class="cart-item-image">${escapeHtml(String(item.name || 'م').charAt(0))}</span>`}<div class="cart-item-info"><strong>${escapeHtml(item.name)}</strong><small>${money(item.price)}</small><div class="quantity-control"><button type="button" data-cart-action="increase" data-product-id="${Number(item.id)}" aria-label="زيادة الكمية">+</button><span>${Number(item.quantity)}</span><button type="button" data-cart-action="decrease" data-product-id="${Number(item.id)}" aria-label="إنقاص الكمية">−</button></div></div><button class="remove-item" type="button" data-cart-action="remove" data-product-id="${Number(item.id)}">حذف</button></div>`;
        }).join('')
        : emptyState('السلة فارغة', 'أضف بعض المنتجات لتتمكن من إنشاء طلب.');
}

function openCart() {
    $('#cart-drawer').classList.add('open');
    $('#cart-drawer').setAttribute('aria-hidden', 'false');
    $('#drawer-backdrop').classList.add('open');
    document.body.classList.add('no-scroll');
}

function closeCart() {
    $('#cart-drawer').classList.remove('open');
    $('#cart-drawer').setAttribute('aria-hidden', 'true');
    $('#drawer-backdrop').classList.remove('open');
    document.body.classList.remove('no-scroll');
}

async function checkout() {
    if (!state.cart.length) return;
    const button = $('#checkout-button');
    button.disabled = true;
    button.textContent = 'جارٍ إنشاء الطلب...';
    try {
        await api('/orders', {
            method: 'POST',
            body: {
                items: state.cart.map((item) => ({
                    product_id: Number(item.id),
                    quantity: Number(item.quantity),
                })),
            },
        });
        state.cart = [];
        persistCart();
        renderCart();
        closeCart();
        await Promise.all([loadOrders(1), loadNotifications()]);
        showView('orders');
        toast('تم إنشاء طلبك بنجاح.');
    } catch (error) {
        toast(errorMessage(error), 'error');
    } finally {
        button.textContent = 'إنشاء الطلب';
        button.disabled = state.cart.length === 0;
    }
}

function showView(viewName) {
    if (viewName === 'add-product' && state.user?.role !== 'admin') viewName = 'overview';
    $$('.app-view').forEach((view) => view.classList.toggle('active', view.dataset.view === viewName));
    $$('[data-view-target]').forEach((button) => button.classList.toggle('active', button.dataset.viewTarget === viewName));
    const [kicker, title] = viewTitles[viewName] || viewTitles.overview;
    $('#page-kicker').textContent = kicker;
    $('#page-title').textContent = title;
    closeSidebar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openSidebar() {
    $('#sidebar').classList.add('open');
    $('#sidebar-backdrop').classList.add('open');
}

function closeSidebar() {
    $('#sidebar').classList.remove('open');
    $('#sidebar-backdrop').classList.remove('open');
}

function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem(THEME_KEY, theme);
    $$('.theme-toggle').forEach((button) => {
        button.setAttribute('aria-label', theme === 'dark' ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');
        button.setAttribute('title', theme === 'dark' ? 'الوضع الفاتح' : 'الوضع الداكن');
    });
}

function toggleTheme() {
    applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
}

function updateProductPreview() {
    $('#preview-name').textContent = $('#product-name').value.trim() || 'اسم المنتج';
    $('#preview-description').textContent = $('#product-description').value.trim() || 'وصف المنتج سيظهر هنا بعد إدخاله.';
    $('#preview-price').textContent = money($('#product-price').value);
}

async function previewProductImage() {
    const file = $('#product-image').files[0];
    const preview = $('#image-preview');
    if (!file) {
        state.productImage = null;
        state.productImagePromise = null;
        preview.innerHTML = `${icons.empty}<small>ستظهر الصورة هنا</small>`;
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        state.productImage = null;
        state.productImagePromise = null;
        $('#product-image').value = '';
        toast('حجم الصورة يجب ألا يتجاوز 2MB.', 'error');
        return;
    }

    const extension = {
        'image/jpeg': 'jpg',
        'image/png': 'png',
        'image/webp': 'webp',
    }[file.type] || 'jpg';
    const safeName = `product-${Date.now()}.${extension}`;
    const imagePromise = file.arrayBuffer().then((bytes) => new File([bytes], safeName, {
        type: file.type,
        lastModified: Date.now(),
    }));
    state.productImagePromise = imagePromise;

    try {
        const cachedImage = await imagePromise;
        if (state.productImagePromise !== imagePromise) return;
        state.productImage = cachedImage;
        const url = URL.createObjectURL(cachedImage);
        preview.innerHTML = `<img src="${url}" alt="معاينة الصورة">`;
        $('img', preview).addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
    } catch {
        if (state.productImagePromise !== imagePromise) return;
        state.productImage = null;
        state.productImagePromise = null;
        $('#product-image').value = '';
        preview.innerHTML = `${icons.empty}<small>تعذر قراءة الصورة</small>`;
        toast('تعذر قراءة الصورة. انسخها إلى مجلد محلي خارج OneDrive ثم اخترها مجددًا.', 'error');
    }
}

function resetProductForm() {
    $('#product-form').reset();
    updateProductPreview();
    previewProductImage();
}

async function submitProduct(event) {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;
    const submit = $('button[type="submit"]', form);
    submit.disabled = true;
    try {
        if (state.productImagePromise) {
            state.productImage = await state.productImagePromise;
        }

        const formData = new FormData(form);
        if (state.productImage) {
            formData.set('image', state.productImage, state.productImage.name);
        } else {
            formData.delete('image');
        }

        const payload = await api('/products', { method: 'POST', body: formData });
        resetProductForm();
        state.selectedCategory = '';
        $('#category-filter').value = '';
        await loadProducts(1);
        showView('products');
        toast(`تمت إضافة «${payload.data.name}» بنجاح.`);
    } catch (error) {
        const message = error.status === 0 && $('#product-image').files.length
            ? 'تعذر رفع الصورة. أعد اختيارها أو انسخها إلى مجلد محلي خارج OneDrive ثم حاول مجددًا.'
            : errorMessage(error);
        toast(message, 'error');
    } finally {
        submit.disabled = false;
    }
}

function bindEvents() {
    $('#login-tab').addEventListener('click', () => setAuthTab('login'));
    $('#register-tab').addEventListener('click', () => setAuthTab('register'));
    $('#login-form').addEventListener('submit', submitLogin);
    $('#register-form').addEventListener('submit', submitRegister);
    $('#demo-customer').addEventListener('click', () => performLogin('a@shop.test', 'password123'));
    $('#demo-admin').addEventListener('click', () => performLogin('admin@shop.test', 'password123'));
    $$('.password-toggle').forEach((button) => button.addEventListener('click', () => {
        const input = $('input', button.parentElement);
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.textContent = visible ? 'إظهار' : 'إخفاء';
    }));
    $$('.theme-toggle').forEach((button) => button.addEventListener('click', toggleTheme));
    $('#logout-button').addEventListener('click', logout);

    $$('[data-view-target], [data-go-to]').forEach((button) => button.addEventListener('click', () => showView(button.dataset.viewTarget || button.dataset.goTo)));
    $('#notification-shortcut').addEventListener('click', () => showView('notifications'));
    $('#menu-button').addEventListener('click', openSidebar);
    $('#sidebar-close').addEventListener('click', closeSidebar);
    $('#sidebar-backdrop').addEventListener('click', closeSidebar);

    $('#product-search').addEventListener('input', (event) => {
        state.search = event.target.value;
        renderProducts();
    });
    $('#category-filter').addEventListener('change', async (event) => {
        state.selectedCategory = event.target.value;
        try { await loadProducts(1); } catch (error) { toast(errorMessage(error), 'error'); }
    });
    $('#products-grid').addEventListener('click', (event) => {
        const button = event.target.closest('[data-add-cart]');
        if (button) addToCart(button.dataset.addCart);
    });

    $('#refresh-orders').addEventListener('click', async () => {
        try { await loadOrders(state.orderPage); toast('تم تحديث الطلبات.'); } catch (error) { toast(errorMessage(error), 'error'); }
    });
    $('#orders-list').addEventListener('click', (event) => {
        const button = event.target.closest('[data-order-details]');
        if (button) showOrderDetails(button.dataset.orderDetails);
    });
    $('#orders-list').addEventListener('change', (event) => {
        const select = event.target.closest('[data-order-status]');
        if (select) changeOrderStatus(select.dataset.orderStatus, select.value, select);
    });
    $('#modal-close').addEventListener('click', closeOrderModal);
    $('#order-modal').addEventListener('click', (event) => { if (event.target === $('#order-modal')) closeOrderModal(); });

    $('#refresh-notifications').addEventListener('click', async () => {
        try { await loadNotifications(); toast('تم تحديث الإشعارات.'); } catch (error) { toast(errorMessage(error), 'error'); }
    });
    $('#notifications-list').addEventListener('click', (event) => {
        const button = event.target.closest('[data-mark-read]');
        if (button) markNotificationRead(button.dataset.markRead, button);
    });

    $('#cart-toggle').addEventListener('click', openCart);
    $('#cart-close').addEventListener('click', closeCart);
    $('#drawer-backdrop').addEventListener('click', closeCart);
    $('#cart-items').addEventListener('click', (event) => {
        const button = event.target.closest('[data-cart-action]');
        if (!button) return;
        const productId = button.dataset.productId;
        if (button.dataset.cartAction === 'increase') changeCartQuantity(productId, 1);
        if (button.dataset.cartAction === 'decrease') changeCartQuantity(productId, -1);
        if (button.dataset.cartAction === 'remove') removeCartItem(productId);
    });
    $('#checkout-button').addEventListener('click', checkout);

    $('#product-form').addEventListener('submit', submitProduct);
    $('#product-form').addEventListener('reset', () => window.setTimeout(() => {
        updateProductPreview();
        previewProductImage();
    }));
    ['#product-name', '#product-description', '#product-price'].forEach((selector) => $(selector).addEventListener('input', updateProductPreview));
    $('#product-image').addEventListener('change', previewProductImage);

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeSidebar();
        closeCart();
        closeOrderModal();
    });
}

async function initialize() {
    const savedTheme = localStorage.getItem(THEME_KEY);
    const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    applyTheme(savedTheme || preferredTheme);
    bindEvents();
    renderCart();

    if (!state.token) {
        showAuth();
        return;
    }

    setBusy(true);
    try {
        await bootstrapSession();
    } catch (error) {
        if (error.status !== 401) {
            clearSession();
            toast(errorMessage(error), 'error');
        }
    } finally {
        setBusy(false);
    }
}

initialize();
