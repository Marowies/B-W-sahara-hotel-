(() => {
  'use strict';
  const runtimePath = document.currentScript?.src ? new URL(document.currentScript.src).pathname : null;
  const nativeAdd = EventTarget.prototype.addEventListener;
  const nativeRemove = EventTarget.prototype.removeEventListener;
  const NativeIntersection = window.IntersectionObserver;
  const NativeMutation = window.MutationObserver;
  const views = new Map();
  const initial = { active: true, listeners: [], observers: [], page: document.body.dataset.page };
  let current = initial;
  window.__hotelViewScope = initial;
  const metrics = window.hotelPerformance = {
    sceneLoads: 0, modelBuilds: 0, renderersCreated: 0, clientNavigations: 0,
    largestContentfulPaint: null, cumulativeLayoutShift: 0,
  };
  window.hotelDebounce = (callback, delay = 120) => {
    const scope = window.__hotelViewScope;
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => { if (!scope || scope.active) callback.apply(this, args); }, delay);
    };
  };
  // Only a read-only public catalogue can use this small memory cache.
  // Session, availability, quote and payment endpoints always go to the server.
  const catalogueRequests = new Map();
  window.hotelCatalogue = (path, loader) => {
    const cacheKey = (window.hotelI18n?.locale()||'en') + ':' + path;
    const hit = catalogueRequests.get(cacheKey);
    if (hit && hit.expires > Date.now()) return hit.promise.then(structuredClone);
    const entry = {expires:Date.now()+15000};
    entry.promise = loader().catch(error => { if (catalogueRequests.get(cacheKey) === entry) catalogueRequests.delete(cacheKey); throw error; });
    catalogueRequests.set(cacheKey, entry);
    if (catalogueRequests.size > 32) catalogueRequests.delete(catalogueRequests.keys().next().value);
    return entry.promise.then(structuredClone);
  };
  function scoped(scope, callback, receiver, args) {
    const previous = window.__hotelViewScope;
    window.__hotelViewScope = scope;
    try { return callback.apply(receiver, args); }
    finally { window.__hotelViewScope = previous; }
  }
  // Route scripts keep their original closures and DOM. Inactive views cannot react to events.
  // The persistent 3D controller runs outside this scope and survives route changes.
  EventTarget.prototype.addEventListener = function (type, handler, options) {
    const scope = window.__hotelViewScope;
    if (!scope || !handler) return nativeAdd.call(this, type, handler, options);
    const wrapped = function (...args) {
      if (!scope.active) return;
      return scoped(scope, typeof handler === 'function' ? handler : handler.handleEvent, typeof handler === 'function' ? this : handler, args);
    };
    scope.listeners.push([this, type, handler, wrapped, options]);
    return nativeAdd.call(this, type, wrapped, options);
  };
  EventTarget.prototype.removeEventListener = function (type, handler, options) {
    for (const view of [initial, ...views.values()]) {
      const entry = view.listeners?.find(x => x[0] === this && x[1] === type && x[2] === handler);
      if (entry) return nativeRemove.call(this, type, entry[3], options);
    }
    return nativeRemove.call(this, type, handler, options);
  };
  if (NativeIntersection) window.IntersectionObserver = class extends NativeIntersection {
    constructor(callback, options) {
      const scope = window.__hotelViewScope;
      super((...args) => { if (!scope || scope.active) scoped(scope, callback, undefined, args); }, options);
      scope?.observers.push(this);
    }
  };
  window.MutationObserver = class extends NativeMutation {
    constructor(callback) {
      const scope = window.__hotelViewScope;
      super((...args) => { if (!scope || scope.active) scoped(scope, callback, undefined, args); });
      scope?.observers.push(this);
    }
  };
  let scenePromise;
  let sceneController;
  window.hotel3D = {
    load() {
      if (scenePromise) return scenePromise;
      metrics.sceneLoads++;
      scenePromise = import('./3d/scene.js').then(module => {
        sceneController = scoped(null, module.createWorld, module, []);
        if (!sceneController) throw new Error('WebGL unavailable');
        return sceneController;
      }).catch(error => {
        scenePromise = undefined;
        const stage = document.getElementById('model-stage');
        if (stage) {
          const loading = stage.querySelector('.model-loading');
          loading.textContent = 'The 3D view is unavailable. You can still explore the hotel photographs.';
          const retry = document.createElement('button');
          retry.className = 'button small'; retry.dataset.retryModel = ''; retry.textContent = 'Try 3D again';
          retry.onclick = () => { loading.textContent = 'Preparing your perspective…'; window.hotel3D.load().catch(() => {}); };
          loading.append(retry);
          stage.querySelector('.model-fallback').style.opacity = '1';
        }
        throw error;
      });
      return scenePromise;
    },
    getStats() { return { ...metrics, ...(sceneController?.getStats() || {}), canvases: sceneController ? 1 : 0 }; },
  };

  const assetInfo = window.__HOTEL_ASSETS__ || {};
  function enhanceImage(img) {
    if (img.dataset.optimized === 'true') return;
    const path = new URL(img.getAttribute('src') || '', location.href).pathname;
    const info = assetInfo[path];
    // A carousel can replace an optimised image with a backend URL. Leaving the
    // old srcset attached makes the browser show the previous photograph.
    if (!info) {
      if (img.dataset.hotelResponsive === 'true') {
        img.removeAttribute('srcset');
        img.removeAttribute('sizes');
        delete img.dataset.hotelResponsive;
      }
      return;
    }
    img.dataset.optimized = 'true';
    img.decoding = 'async';
    img.width = info.width; img.height = info.height;
    if (info.srcset && !img.closest('dialog')) {
      img.srcset = info.srcset;
      img.dataset.hotelResponsive = 'true';
      img.sizes = img.classList.contains('hero-image') || img.closest('.page-hero,.horizon,.final-photo')
        ? '100vw' : '(max-width: 760px) 100vw, 60vw';
    }
    if (img.fetchPriority !== 'high' && !img.closest('.brand') && !img.closest('.page-hero')) img.loading = 'lazy';
  }
  function enhance(root = document) { root.querySelectorAll('img').forEach(enhanceImage); }
  // Unscoped observer supports images generated by all page templates and dialog interactions.
  const images = new NativeMutation(records => {
    for (const record of records) {
      if (record.type === 'attributes') {
        const img = record.target;
        delete img.dataset.optimized; enhanceImage(img);
      } else for (const node of record.addedNodes) if (node.nodeType === 1) {
        if (node.tagName === 'IMG') enhanceImage(node);
        enhance(node);
      }
    }
  });
  images.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });
  if ('PerformanceObserver' in window) {
    try { new PerformanceObserver(list => { for (const e of list.getEntries()) metrics.largestContentfulPaint = Math.round(e.startTime); }).observe({type:'largest-contentful-paint', buffered:true}); } catch {}
    try { new PerformanceObserver(list => { for (const e of list.getEntries()) if (!e.hadRecentInput) metrics.cumulativeLayoutShift += e.value; }).observe({type:'layout-shift', buffered:true}); } catch {}
  }
  // Native document navigation keeps each route's head, styles and scripts in one release.
  window.hotelNavigate = href => { location.assign(new URL(href, location.href).href); };
  nativeAdd.call(document, 'DOMContentLoaded', () => {
    window.__hotelViewScope = null;
    enhance();
    let edited = false, refreshed = false, checkingRelease = false;
    document.addEventListener('input', () => { edited = true; }, {once:true});
    const refreshPage = () => {
      if (refreshed) return;
      refreshed = true;
      if (!edited) { location.reload(); return; }
      const notice = document.createElement('button');
      notice.type = 'button'; notice.className = 'button';
      notice.textContent = ({ar:'تحديث الموقع متاح — اضغط بعد حفظ بياناتك', 'zh-Hans':'网站已更新，保存输入后点击刷新'})[document.documentElement.lang] || 'Site update available — refresh after saving your details';
      notice.style.cssText = 'position:fixed;bottom:20px;left:20px;right:20px;z-index:9999';
      notice.onclick = () => location.reload(); document.body.append(notice);
    };
    const checkRelease = async () => {
      if (!runtimePath || checkingRelease || refreshed) return;
      checkingRelease = true;
      try {
        const response = await fetch('/build-manifest.json', {cache:'no-store'});
        if (!response.ok) return;
        const manifest = await response.json(), latest = manifest.files?.['runtime.js'];
        if (latest && new URL(latest, location.origin).pathname !== runtimePath) refreshPage();
      } catch {} finally { checkingRelease = false; }
    };
    nativeAdd.call(window, 'pageshow', event => { if (event.persisted) checkRelease(); });
    nativeAdd.call(document, 'visibilitychange', () => { if (document.visibilityState === 'visible') checkRelease(); });
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || ['127.0.0.1','localhost'].includes(location.hostname))) {
      // Warm only the small shell; image/3D caching begins only after actual use.
      const hadController = !!navigator.serviceWorker.controller;
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!hadController || refreshed) return;
        refreshPage();
      });
      const register = () => navigator.serviceWorker.register('/sw.js', {updateViaCache:'none'}).then(reg => reg.update()).catch(() => {});
      if ('requestIdleCallback' in window) requestIdleCallback(register, {timeout:2500}); else setTimeout(register, 1200);
    }
  });
})();
