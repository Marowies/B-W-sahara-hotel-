/* Preserve the existing payment form, while supplying its required customer token. */
(() => {
    'use strict';
    const form = document.querySelector('.booking-form-main');
    if (!form) return;
    const action = new URL(form.action, location.href);
    if (action.origin !== location.origin || action.pathname !== '/checkout') return;
    const status = document.createElement('p');
    status.setAttribute('role', 'status');
    const button = form.querySelector('[type="submit"]');
    button.before(status);
    const originalLabel = button.innerHTML;
    const lang = document.documentElement.lang.toLowerCase();
    const message = (en, ar, zh) => lang.startsWith('ar') ? ar : lang.startsWith('zh') ? zh : en;
    let submitting = false;
    async function submit(event) {
        event.preventDefault();
        if (submitting) return;
        submitting = true;
        button.disabled = true;
        try {
            const sessionResponse = await fetch('/api/hotel/session', {credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json'}});
            const session = await sessionResponse.json();
            if (!sessionResponse.ok || !session.csrf_token) throw new Error(message('Reload this page and try again.', 'حدّث الصفحة وحاول مرة أخرى.', '请刷新页面后重试。'));
            const refresh = await fetch('/api/hotel/auth/refresh', {method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': session.csrf_token}, body: '{}'});
            const auth = await refresh.json();
            if (!refresh.ok || !auth.access_token) throw new Error(message('Your sign-in expired. Sign in again, then return to review your booking.', 'انتهت جلسة الدخول. سجّل دخولك مرة أخرى ثم ارجع لمراجعة الحجز.', '登录已过期，请重新登录后返回核对预订。'));
            const body = new FormData(form);
            body.set('_token', session.csrf_token);
            const response = await fetch(action.href, {method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': session.csrf_token, Authorization: 'Bearer ' + auth.access_token}, body});
            // Access tokens stay in this function, never in fields, URLs or browser storage.
            if (response.redirected && response.ok) {
                const next = new URL(response.url);
                if (next.origin === location.origin || next.protocol === 'https:') { location.assign(next.href); return; }
            }
            const result = await response.json();
            if (!response.ok || result.error) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || message('Booking could not be completed.', 'تعذّر إكمال الحجز.', '无法完成预订。'));
            const destination = result.data?.checkoutUrl || result.data?.next_url || result.data?.url;
            if (destination) {
                const next = new URL(destination, location.origin);
                if (next.origin === location.origin || next.protocol === 'https:') { location.assign(next.href); return; }
            }
            throw new Error(message('The hotel did not return a confirmation. Please check your bookings before retrying.', 'لم يرجع الفندق تأكيدًا. راجع حجوزاتك قبل إعادة المحاولة.', '酒店未返回确认信息，请先检查已有预订再重试。'));
        } catch (error) {
            status.textContent = error.message;
            button.disabled = false;
            button.innerHTML = originalLabel;
        } finally {
            submitting = false;
        }
    }
    // jQuery also catches the existing payment plugin's programmatic submit().
    if (window.jQuery) window.jQuery(form).on('submit.hotelCustomerToken', submit);
    else form.addEventListener('submit', submit);
})();
