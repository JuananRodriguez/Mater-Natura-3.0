/**
 * Mater-Natura Analytics — Client-side tracking
 * Inyectado por el plugin Analytics via hook page.footer
 */
(function () {
    'use strict';

    var TRACK_URL = '/analytics/track';
    var HEARTBEAT_INTERVAL = 30000; // 30s
    var SESSION_KEY = 'mn_analytics_session';
    var PAGE_START = Date.now();
    var heartbeatTimer = null;

    // Obtener o generar session_id (persiste en sessionStorage)
    function getSessionId() {
        var sid = sessionStorage.getItem(SESSION_KEY);
        if (!sid) {
            sid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = (Math.random() * 16) | 0;
                var v = c === 'x' ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            });
            sessionStorage.setItem(SESSION_KEY, sid);
        }
        return sid;
    }

    function sendEvent(data) {
        data.session_id = getSessionId();
        data.page_url = location.pathname;
        data.page_title = document.title;
        data.referer = document.referrer || '';

        var payload = JSON.stringify(data);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(TRACK_URL, payload);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', TRACK_URL, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(payload);
        }
    }

    function sendHeartbeat() {
        var elapsed = Math.floor((Date.now() - PAGE_START) / 1000);
        if (elapsed < 1) return;
        sendEvent({
            type: 'heartbeat',
            elapsed: elapsed,
        });
    }

    function sendExit(type, url) {
        var elapsed = Math.floor((Date.now() - PAGE_START) / 1000);
        var data = {
            type: 'exit',
            elapsed: elapsed,
            exit_type: type,
        };
        if (url) {
            data.exit_url = url;
        }
        // sendBeacon es síncrono en beforeunload
        navigator.sendBeacon(TRACK_URL, JSON.stringify(data));
    }

    // ─── Pageview al cargar ───
    sendEvent({ type: 'pageview' });

    // ─── Heartbeat cada 30s mientras la página esté visible ───
    function startHeartbeat() {
        if (heartbeatTimer) clearInterval(heartbeatTimer);
        heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
    }

    function stopHeartbeat() {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    }

    // Solo heartbeat cuando la página está visible (el usuario está activo)
    if (document.visibilityState === 'visible') {
        startHeartbeat();
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            // Vuelve — registra heartbeat con elapsed, reanuda
            var elapsed = Math.floor((Date.now() - PAGE_START) / 1000);
            sendEvent({ type: 'heartbeat', elapsed: elapsed, visibility: 'return' });
            startHeartbeat();
        } else {
            // Se va — heartbeat final y pausa
            sendHeartbeat();
            stopHeartbeat();
        }
    });

    // ─── Exit al cerrar/navegar ───
    window.addEventListener('beforeunload', function () {
        sendExit('closed_tab');
    });

    // ─── Tracking de enlaces externos ───
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;

        var href = link.getAttribute('href');
        if (!href) return;

        // Ignorar enlaces vacíos, anclas, javascript, mailto, tel
        if (href === '' || href === '#' || href.startsWith('javascript:') ||
            href.startsWith('mailto:') || href.startsWith('tel:')) return;

        // Determinar tipo de enlace: interno vs externo
        if (href.startsWith('http://') || href.startsWith('https://')) {
            try {
                var linkUrl = new URL(href);
                var isInternal = linkUrl.hostname === location.hostname;

                if (!isInternal) {
                    // Enlace externo
                    sendEvent({
                        type: 'outbound',
                        exit_url: href,
                        exit_type: 'external_link',
                    });
                } else {
                    // Enlace interno — se usa para saber que no fue cierre de pestaña
                    sendEvent({
                        type: 'internal_nav',
                        exit_url: href,
                        exit_type: 'internal_link',
                    });
                }
            } catch (_) {
                // URL malformada, ignorar
            }
        }
    });

})();
