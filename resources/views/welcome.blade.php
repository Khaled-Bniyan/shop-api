<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="واجهة متجر إلكتروني لإدارة المنتجات والطلبات والإشعارات">
    <meta name="theme-color" content="#173f3a">
    <title>رِواق | متجر إلكتروني</title>
    <link rel="stylesheet" href="{{ asset('assets/shop.css') }}?v={{ filemtime(public_path('assets/shop.css')) }}">
    <script type="module" src="{{ asset('assets/shop.js') }}?v={{ filemtime(public_path('assets/shop.js')) }}"></script>
</head>
<body>
    <noscript><div class="noscript">تحتاج هذه الواجهة إلى تفعيل JavaScript للعمل.</div></noscript>

    <div id="auth-view" class="auth-page">
        <header class="public-header container">
            <a class="brand" href="/" aria-label="الصفحة الرئيسية">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 8V6a5 5 0 0 1 10 0v2M5 8h14l1 13H4L5 8Z"/></svg>
                </span>
                <span><strong>رِواق</strong><small>متجر أبسط، تجربة أوضح</small></span>
            </a>
            <button class="icon-button theme-toggle" type="button" aria-label="تبديل نمط الألوان">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
            </button>
        </header>

        <main class="auth-layout container">
            <section class="hero-panel" aria-labelledby="hero-title">
                <div class="eyebrow"><span></span> مشروع تطبيقات الإنترنت 2026</div>
                <h1 id="hero-title">كل ما يحتاجه متجرك، في مكان واحد.</h1>
                <div class="hero-visual" aria-hidden="true">
                    <div class="visual-card visual-card-main"><span>طلبك الحالي</span><strong>#1048</strong><div class="progress-track"><i></i></div><small>قيد التجهيز</small></div>
                    <div class="visual-card visual-card-float"><span class="float-dot"></span><div><strong>تم تحديث الطلب</strong><small>منذ دقيقة واحدة</small></div></div>
                </div>
            </section>

            <section class="auth-card" aria-label="المصادقة">
                <div class="auth-card-header">
                    <span class="mini-mark"><svg viewBox="0 0 24 24"><path d="M7 8V6a5 5 0 0 1 10 0v2M5 8h14l1 13H4L5 8Z"/></svg></span>
                    <div><h2>مرحباً بك</h2><p>سجّل الدخول أو أنشئ حساباً جديداً</p></div>
                </div>
                <div class="auth-tabs" role="tablist">
                    <button id="login-tab" class="auth-tab active" type="button" role="tab" aria-selected="true">تسجيل الدخول</button>
                    <button id="register-tab" class="auth-tab" type="button" role="tab" aria-selected="false">حساب جديد</button>
                </div>

                <form id="login-form" class="auth-form" novalidate>
                    <div class="field"><label for="login-email">البريد الإلكتروني</label><div class="input-wrap"><svg viewBox="0 0 24 24"><path d="m3 6 9 7 9-7"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg><input id="login-email" name="email" type="email" autocomplete="email" placeholder="name@example.com" required></div></div>
                    <div class="field"><label for="login-password">كلمة المرور</label><div class="input-wrap"><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required><button class="password-toggle" type="button">إظهار</button></div></div>
                    <div class="form-message" aria-live="polite"></div>
                    <button class="primary-button submit-button" type="submit"><span>دخول إلى المتجر</span><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
                    <div class="demo-access"><p>دخول سريع للحسابات التجريبية</p><div><button id="demo-customer" type="button"><span class="avatar-mini">ز</span> حساب زبون</button><button id="demo-admin" type="button"><span class="avatar-mini admin">م</span> حساب مسؤول</button></div></div>
                </form>

                <form id="register-form" class="auth-form" novalidate hidden>
                    <div class="field"><label for="register-name">الاسم الكامل</label><div class="input-wrap"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><input id="register-name" name="name" type="text" autocomplete="name" placeholder="اكتب اسمك" required></div></div>
                    <div class="field"><label for="register-email">البريد الإلكتروني</label><div class="input-wrap"><svg viewBox="0 0 24 24"><path d="m3 6 9 7 9-7"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg><input id="register-email" name="email" type="email" autocomplete="email" placeholder="name@example.com" required></div></div>
                    <div class="field"><label for="register-password">كلمة المرور</label><div class="input-wrap"><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="register-password" name="password" type="password" autocomplete="new-password" placeholder="8 محارف على الأقل" minlength="8" required></div></div>
                    <div class="field"><label for="register-password-confirmation">تأكيد كلمة المرور</label><div class="input-wrap"><svg viewBox="0 0 24 24"><path d="M20 7 9 18l-5-5"/></svg><input id="register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="أعد كتابة كلمة المرور" minlength="8" required></div></div>
                    <div class="form-message" aria-live="polite"></div>
                    <button class="primary-button submit-button" type="submit"><span>إنشاء الحساب</span><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
                </form>
            </section>
        </main>
    </div>

    <div id="app-shell" class="app-shell" hidden>
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-head">
                <a class="brand brand-light" href="#overview"><span class="brand-mark"><svg viewBox="0 0 24 24"><path d="M7 8V6a5 5 0 0 1 10 0v2M5 8h14l1 13H4L5 8Z"/></svg></span><span><strong>رِواق</strong><small>لوحة المتجر</small></span></a>
                <button id="sidebar-close" class="sidebar-close" type="button" aria-label="إغلاق القائمة">×</button>
            </div>
            <nav class="sidebar-nav" aria-label="التنقل الرئيسي">
                <span class="nav-label">القائمة الرئيسية</span>
                <button class="nav-item active" type="button" data-view-target="overview"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg><span>نظرة عامة</span></button>
                <button class="nav-item" type="button" data-view-target="products"><svg viewBox="0 0 24 24"><path d="M20 7 12 3 4 7l8 4 8-4Z"/><path d="m4 7 8 4v10l-8-4V7Zm16 0-8 4v10l8-4V7Z"/></svg><span>المنتجات</span></button>
                <button class="nav-item" type="button" data-view-target="orders"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6m-6 4h6"/></svg><span>الطلبات</span></button>
                <button class="nav-item" type="button" data-view-target="notifications"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span>الإشعارات</span><b id="sidebar-notification-count" class="nav-count" hidden>0</b></button>
                <div class="admin-only" hidden><span class="nav-label">إدارة المتجر</span><button class="nav-item" type="button" data-view-target="add-product"><svg viewBox="0 0 24 24"><path d="M12 5v14m-7-7h14"/><circle cx="12" cy="12" r="9"/></svg><span>إضافة منتج</span></button></div>
            </nav>
            <div class="sidebar-user"><div id="sidebar-avatar" class="user-avatar">ر</div><div><strong id="sidebar-user-name">المستخدم</strong><small id="sidebar-user-role">زبون</small></div><button id="logout-button" class="logout-icon" type="button" aria-label="تسجيل الخروج"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5m5 5H3m12-9h5v18h-5"/></svg></button></div>
        </aside>
        <div id="sidebar-backdrop" class="sidebar-backdrop"></div>

        <div class="workspace">
            <header class="topbar">
                <div class="topbar-title"><button id="menu-button" class="icon-button menu-button" type="button" aria-label="فتح القائمة"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button><div><span id="page-kicker">لوحة التحكم</span><h1 id="page-title">نظرة عامة</h1></div></div>
                <div class="topbar-actions">
                    <button class="icon-button theme-toggle" type="button" aria-label="تبديل نمط الألوان"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg></button>
                    <button id="notification-shortcut" class="icon-button notification-button" type="button" aria-label="الإشعارات"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><b id="top-notification-count" hidden>0</b></button>
                    <button id="cart-toggle" class="cart-button customer-only" type="button"><svg viewBox="0 0 24 24"><path d="M3 3h2l2 13h10l3-9H6M9 21h.01M17 21h.01"/></svg><span>السلة</span><b id="cart-count">0</b></button>
                </div>
            </header>

            <main class="main-content">
                <section id="view-overview" class="app-view active" data-view="overview">
                    <div class="welcome-banner"><div><span class="eyebrow light"><span></span> أهلاً بعودتك</span><h2>مرحباً، <span id="welcome-name">بك</span></h2><p id="welcome-copy">اكتشف أحدث المنتجات وتابع طلباتك من هنا.</p><button class="banner-action" type="button" data-go-to="products">تصفّح المنتجات <span>←</span></button></div><div class="banner-art" aria-hidden="true"><i></i><i></i><i></i></div></div>
                    <div class="stats-grid">
                        <article class="stat-card"><span class="stat-icon teal"><svg viewBox="0 0 24 24"><path d="M20 7 12 3 4 7l8 4 8-4ZM4 7v10l8 4 8-4V7"/></svg></span><div><small>إجمالي المنتجات</small><strong id="products-stat">—</strong><span>متاحة في المتجر</span></div></article>
                        <article class="stat-card"><span class="stat-icon orange"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/></svg></span><div><small id="orders-stat-label">طلباتي</small><strong id="orders-stat">—</strong><span>طلب مسجل</span></div></article>
                        <article class="stat-card"><span class="stat-icon purple"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/></svg></span><div><small>إشعارات جديدة</small><strong id="notifications-stat">—</strong><span>بانتظار القراءة</span></div></article>
                    </div>
                    <div class="dashboard-grid"><section class="panel"><div class="panel-head"><div><span>آخر النشاطات</span><h3>أحدث الطلبات</h3></div><button type="button" data-go-to="orders">عرض الكل</button></div><div id="recent-orders" class="compact-list"></div></section><section class="panel"><div class="panel-head"><div><span>وصل حديثاً</span><h3>أحدث المنتجات</h3></div><button type="button" data-go-to="products">عرض الكل</button></div><div id="recent-products" class="mini-products"></div></section></div>
                </section>

                <section id="view-products" class="app-view" data-view="products">
                    <div class="section-toolbar"><div><span class="section-eyebrow">كتالوج المتجر</span><h2>المنتجات</h2><p>اختر ما يناسبك من المنتجات المتوفرة.</p></div><div class="toolbar-controls"><label class="search-field"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input id="product-search" type="search" placeholder="ابحث عن منتج..."></label><select id="category-filter" aria-label="تصفية حسب التصنيف"><option value="">كل التصنيفات</option></select></div></div>
                    <div id="products-meta" class="result-meta"></div><div id="products-grid" class="products-grid"></div><div id="products-pagination" class="pagination"></div>
                </section>

                <section id="view-orders" class="app-view" data-view="orders">
                    <div class="section-toolbar"><div><span class="section-eyebrow">المتابعة والإدارة</span><h2 id="orders-heading">طلباتي</h2><p id="orders-description">تابع حالة طلباتك وتفاصيلها.</p></div><button id="refresh-orders" class="secondary-button" type="button"><svg viewBox="0 0 24 24"><path d="M20 6v5h-5M4 18v-5h5"/><path d="M18.5 9A7 7 0 0 0 6 6.5M5.5 15A7 7 0 0 0 18 17.5"/></svg> تحديث</button></div>
                    <div id="orders-list" class="orders-list"></div><div id="orders-pagination" class="pagination"></div>
                </section>

                <section id="view-notifications" class="app-view" data-view="notifications">
                    <div class="section-toolbar"><div><span class="section-eyebrow">ابقَ على اطلاع</span><h2>الإشعارات</h2><p>آخر تحديثات المنتجات وحالات الطلبات.</p></div><button id="refresh-notifications" class="secondary-button" type="button"><svg viewBox="0 0 24 24"><path d="M20 6v5h-5M4 18v-5h5"/><path d="M18.5 9A7 7 0 0 0 6 6.5M5.5 15A7 7 0 0 0 18 17.5"/></svg> تحديث</button></div>
                    <div id="notifications-list" class="notifications-list"></div>
                </section>

                <section id="view-add-product" class="app-view" data-view="add-product">
                    <div class="section-toolbar"><div><span class="section-eyebrow">إدارة الكتالوج</span><h2>إضافة منتج جديد</h2><p>أدخل تفاصيل المنتج وارفع صورة واضحة له.</p></div></div>
                    <div class="product-form-layout">
                        <form id="product-form" class="panel product-form" enctype="multipart/form-data" novalidate>
                            <div class="form-grid">
                                <div class="field span-2"><label for="product-name">اسم المنتج</label><input id="product-name" name="name" type="text" placeholder="مثال: كاميرا رقمية" required></div>
                                <div class="field"><label for="product-price">السعر</label><div class="price-input"><input id="product-price" name="price" type="number" min="0" step="0.01" placeholder="0.00" required><span>$</span></div></div>
                                <div class="field"><label for="product-category">التصنيف</label><select id="product-category" name="category_id" required><option value="">اختر التصنيف</option></select></div>
                                <div class="field span-2"><label for="product-description">الوصف</label><textarea id="product-description" name="description" rows="5" placeholder="وصف مختصر وواضح للمنتج"></textarea></div>
                                <div class="field span-2"><label>صورة المنتج</label><label class="upload-zone" for="product-image"><input id="product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><span class="upload-icon"><svg viewBox="0 0 24 24"><path d="M12 16V4m-5 5 5-5 5 5M5 20h14"/></svg></span><strong>اسحب الصورة هنا أو اضغط للاختيار</strong><small>JPG أو PNG أو WEBP — بحد أقصى 2MB</small></label></div>
                            </div>
                            <div class="form-message" aria-live="polite"></div><div class="form-actions"><button class="primary-button" type="submit">حفظ المنتج</button><button class="ghost-button" type="reset">إلغاء</button></div>
                        </form>
                        <aside class="panel preview-panel"><span>معاينة المنتج</span><div id="image-preview" class="image-preview"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg><small>ستظهر الصورة هنا</small></div><h3 id="preview-name">اسم المنتج</h3><p id="preview-description">وصف المنتج سيظهر هنا بعد إدخاله.</p><strong id="preview-price">$0.00</strong></aside>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <aside id="cart-drawer" class="cart-drawer" aria-hidden="true"><div class="drawer-head"><div><span>مشترياتك</span><h2>سلة الطلب</h2></div><button id="cart-close" type="button" aria-label="إغلاق السلة">×</button></div><div id="cart-items" class="cart-items"></div><div class="cart-summary"><div><span>المجموع</span><strong id="cart-total">$0.00</strong></div><small>يُحسب السعر النهائي من الخادم عند إنشاء الطلب.</small><button id="checkout-button" class="primary-button" type="button">إنشاء الطلب</button></div></aside>
    <div id="drawer-backdrop" class="drawer-backdrop"></div>
    <div id="order-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title" hidden><div class="modal-card"><button id="modal-close" class="modal-close" type="button" aria-label="إغلاق">×</button><div id="order-modal-body"></div></div></div>
    <div id="toast-region" class="toast-region" aria-live="polite" aria-atomic="true"></div>
    <div id="loading-layer" class="loading-layer" hidden><span class="loader"></span><p>جارٍ تحميل بيانات المتجر...</p></div>
</body>
</html>
