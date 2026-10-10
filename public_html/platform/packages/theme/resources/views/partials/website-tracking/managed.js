(function (w, d) {
    'use strict';
    if (w.hotelTracking) return;
    const node = d.getElementById('hotel-tracking-config');
    if (!node) return;
    const config = JSON.parse(node.textContent);
    let granted = false;
    let loaded = false;
    const seen = new Set();
    w.dataLayer = w.dataLayer || [];
    const gtag = function () { w.dataLayer.push(arguments); };
    // Queue consent before either loader. No Google request before an explicit analytics opt-in.
    gtag('consent', 'default', {
        analytics_storage: 'denied', ad_storage: 'denied',
        ad_user_data: 'denied', ad_personalization: 'denied'
    });

    function flush() {
        if (!granted || !loaded) return;
        d.querySelectorAll('.hotel-tracking-event').forEach(function (element) {
            let payload;
            try { payload = JSON.parse(element.textContent); } catch (_) { return; }
            if (!['view_item', 'begin_checkout'].includes(payload.event)) return;
            const items = payload.ecommerce && payload.ecommerce.items;
            if (!Array.isArray(items) || items.length !== 1 || !/^room-[0-9]+$/.test(items[0].item_id)) return;
            // Copy only approved fields; names, contacts, tokens, dates and amounts are not collected.
            const ecommerce = {items: [{item_id: items[0].item_id, item_category: 'Hotel room'}]};
            const key = payload.event + ':' + items[0].item_id;
            if (seen.has(key)) return;
            const attempt = payload.event === 'begin_checkout' && /^[a-f0-9]{64}$/.test(payload.attempt || '') ? 'hotel-checkout:' + payload.attempt : null;
            try {
                if (attempt && w.sessionStorage.getItem(attempt)) return;
                if (attempt) w.sessionStorage.setItem(attempt, 'sent');
            } catch (_) { /* Page-level deduplication still works when storage is unavailable. */ }
            seen.add(key);
            if (config.mode === 'gtm') {
                w.dataLayer.push({ecommerce: null});
                w.dataLayer.push({event: payload.event, ecommerce: ecommerce});
            } else {
                gtag('event', payload.event, ecommerce);
            }
        });
    }

    function load() {
        if (loaded || !granted) return;
        // Do not coexist with an injected tag or third-party owner of the Google loader.
        if (w.google_tag_manager || w.gtag || d.querySelector('script[src*="googletagmanager.com"]')) return;
        loaded = true;
        const script = d.createElement('script');
        script.async = true;
        if (config.mode === 'gtm') {
            w.dataLayer.push({hotel_page_location: w.location.origin + safePath(), hotel_page_title: 'Hotel website', hotel_page_referrer: ''});
            w.dataLayer.push({'gtm.start': Date.now(), event: 'gtm.js'});
            w.dataLayer.push({event: 'hotel_page_view'});
            script.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(config.id);
        } else {
            w.gtag = gtag;
            gtag('js', new Date());
            // Avoid booking tokens, guest fields and query strings in automatic page URLs/referrers.
            gtag('config', config.id, {send_page_view: false, page_location: w.location.origin + safePath(), page_title: 'Hotel website', page_referrer: '', allow_google_signals: false, allow_ad_personalization_signals: false});
            gtag('event', 'page_view', {page_location: w.location.origin + safePath(), page_title: 'Hotel website', page_referrer: ''});
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.id);
        }
        d.head.appendChild(script);
        flush();
    }

    function safePath() {
        // Booking URLs may contain opaque transaction/session tokens; report a fixed path.
        const path = w.location.pathname;
        return /\/(booking|checkout)(\/|$)/i.test(path) ? '/booking' : path;
    }

    function consent(preferences) {
        granted = Boolean(preferences && preferences.analytics === true);
        gtag('consent', 'update', {
            analytics_storage: granted ? 'granted' : 'denied',
            ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied'
        });
        if (granted) { load(); flush(); }
        else if (loaded) {
            // Unloading a live GTM container is unreliable; reload into a denied state.
            w.location.reload();
        }
    }

    w.hotelTracking = {setConsent: consent, refresh: flush};
    d.addEventListener('hotel:consent', function (event) { consent(event.detail); });
    d.addEventListener('DOMContentLoaded', flush);
    try {
        const part = d.cookie.split(';').map(function (s) { return s.trim(); })
            .find(function (s) { return s.startsWith(config.cookie + '='); });
        if (part) consent(JSON.parse(decodeURIComponent(part.slice(config.cookie.length + 1))));
    } catch (_) { /* Invalid or legacy consent is not permission. */ }
})(window, document);
