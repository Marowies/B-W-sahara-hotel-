const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../platform/packages/theme/resources/views/partials/website-tracking/managed.js'), 'utf8');

function harness({mode = 'ga4', cookie = '', existing = false, markers = [], storage = new Map()} = {}) {
    const listeners = {};
    const scripts = [];
    let reloads = 0;
    const doc = {
        cookie, head: {appendChild: script => scripts.push(script)},
        getElementById: () => ({textContent: JSON.stringify({mode, id: mode === 'gtm' ? 'GTM-UNIT' : 'G-UNIT', cookie: 'cookie_for_consent'})}),
        createElement: () => ({}),
        addEventListener: (name, fn) => { (listeners[name] ??= []).push(fn); },
        querySelector: () => existing ? {} : null,
        querySelectorAll: () => markers.map(payload => ({textContent: JSON.stringify(payload)}))
    };
    const window = {location: {origin: 'https://hotel.test', pathname: '/booking/private-token', reload: () => reloads++}, sessionStorage: {getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value)}};
    const context = vm.createContext({window, document: doc});
    vm.runInContext(source, context);
    return {window, scripts, doc, context, reloads: () => reloads, fire: (name, detail) => (listeners[name] ?? []).forEach(fn => fn({detail}))};
}
const room = {event: 'view_item', ecommerce: {items: [{item_id: 'room-7', email: 'guest@example.test'}]}};
const events = h => h.window.dataLayer.filter(row => row[0] === 'event' || row.event === 'view_item' || row.event === 'begin_checkout');

test('No Google loader or behavioral events before explicit analytics consent', () => {
    for (const cookie of ['', 'cookie_for_consent=true', 'cookie_for_consent=bad-json', 'cookie_for_consent=' + encodeURIComponent(JSON.stringify({marketing: true})), 'cookie_for_consent=' + encodeURIComponent(JSON.stringify({analytics: 'true'}))]) {
        const h = harness({cookie, markers: [room]});
        h.fire('DOMContentLoaded');
        assert.equal(h.scripts.length, 0);
        assert.equal(events(h).length, 0);
    }
});
test('Accepting analytics loads one direct tag and emits each room event once', () => {
    const h = harness({markers: [room, room, {event: 'begin_checkout', ecommerce: room.ecommerce}]});
    h.fire('hotel:consent', {analytics: true});
    h.fire('hotel:consent', {analytics: true});
    h.fire('DOMContentLoaded');
    assert.equal(h.scripts.length, 1);
    assert.match(h.scripts[0].src, /\/gtag\/js/);
    assert.equal(events(h).filter(row => row[1] === 'view_item').length, 1);
    assert.equal(events(h).filter(row => row[1] === 'begin_checkout').length, 1);
    const serialized = JSON.stringify(h.window.dataLayer);
    assert.ok(!serialized.includes('guest@example.test'));
    assert.ok(!serialized.includes('private-token'));
    assert.ok(serialized.includes('ad_user_data'));
});
test('Returning consent and GTM use only one container, with no direct GA tag', () => {
    const h = harness({mode: 'gtm', cookie: 'cookie_for_consent=' + JSON.stringify({analytics: true}), markers: [room]});
    h.fire('DOMContentLoaded');
    assert.equal(h.scripts.length, 1);
    assert.match(h.scripts[0].src, /\/gtm.js/);
    assert.equal(typeof h.window.gtag, 'undefined');
    assert.equal(events(h).filter(row => row.event === 'view_item').length, 1);
    assert.equal(h.window.dataLayer.filter(row => row.event === 'hotel_page_view').length, 1);
});
test('Existing Google loader prevents an additional container and behavioral events', () => {
    const h = harness({existing: true, markers: [room]});
    h.fire('hotel:consent', {analytics: true});
    assert.equal(h.scripts.length, 0);
    assert.equal(events(h).length, 0);
});
test('Withdrawal stops new events and reloads into a denied state', () => {
    const h = harness({markers: [room]});
    h.fire('hotel:consent', {analytics: true});
    const count = events(h).length;
    h.fire('hotel:consent', {analytics: false});
    h.window.hotelTracking.refresh();
    assert.equal(events(h).length, count);
    assert.equal(h.reloads(), 1);
    const update = h.window.dataLayer[h.window.dataLayer.length - 1];
    assert.equal(update[2].analytics_storage, 'denied');
});
test('Duplicate renderer and unapproved purchase events cannot send twice or invent a sale', () => {
    const h = harness({markers: [{event: 'purchase', ecommerce: room.ecommerce}, room]});
    vm.runInContext(source, h.context);
    h.fire('hotel:consent', {analytics: true});
    assert.equal(h.scripts.length, 1);
    assert.equal(events(h).filter(row => row[1] === 'purchase' || row.event === 'purchase').length, 0);
    assert.equal(events(h).filter(row => row[1] === 'view_item').length, 1);
});
test('One checkout attempt is not resent on refresh in the same consenting tab', () => {
    const storage = new Map();
    const marker = {event: 'begin_checkout', attempt: 'a'.repeat(64), ecommerce: room.ecommerce};
    const first = harness({storage, markers: [marker]});
    first.fire('hotel:consent', {analytics: true});
    assert.equal(events(first).filter(row => row[1] === 'begin_checkout').length, 1);
    const next = harness({storage, markers: [marker]});
    next.fire('hotel:consent', {analytics: true});
    assert.equal(events(next).filter(row => row[1] === 'begin_checkout').length, 0);
    assert.ok(!JSON.stringify(first.window.dataLayer).includes(marker.attempt));
});
test('The actual cookie dialog publishes saved analytics preferences and a durable rejection', () => {
    const template = fs.readFileSync(path.join(__dirname, '../../platform/plugins/cookie-consent/resources/views/index.blade.php'), 'utf8');
    const script = template.slice(template.lastIndexOf('<script>') + 8, template.lastIndexOf('</script>'));
    const handlers = {};
    const changes = [];
    let cookie = '';
    const dialog = {style: {}, classList: {add() {}, remove() {}}};
    const doc = {
        get cookie() { return cookie; }, set cookie(value) { cookie = value.split(';')[0]; },
        querySelector(selector) {
            if (selector === '.js-site-notice') return dialog;
            if (selector.startsWith('div[data-site-')) return {getAttribute: () => selector.includes('name') ? 'cookie_for_consent' : selector.includes('domain') ? 'hotel.test' : selector.includes('lifetime') ? '180' : ''};
            return null;
        },
        querySelectorAll: () => [{value: 'essential', checked: true}, {value: 'analytics', checked: true}],
        addEventListener: (name, fn) => { handlers[name] = fn; },
        dispatchEvent: event => changes.push(event.detail)
    };
    const window = {location: {hostname: 'hotel.test', protocol: 'https:'}, addEventListener: (name, fn) => { handlers[name] = fn; }};
    vm.runInNewContext(script, {document: doc, window, setTimeout: fn => fn(), CustomEvent: function(type, options) { this.type = type; this.detail = options.detail; }});
    handlers.load();
    window.botbleCookieConsent.savePreferences();
    assert.equal(changes[0].analytics, true);
    assert.ok(cookie.includes('"analytics":true'));
    window.botbleCookieConsent.rejectAllCookies();
    assert.equal(changes[1].analytics, false);
    assert.ok(cookie.includes('"analytics":false'));
    assert.equal(typeof window.botbleCookieConsent.showPreferences, 'function');
});
