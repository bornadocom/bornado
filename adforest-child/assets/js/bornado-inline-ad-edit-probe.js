(function () {
    'use strict';

    function sendProbe() {
        if (typeof window === 'undefined' || typeof document === 'undefined' || typeof fetch === 'undefined' || typeof bornadoInlineEdit === 'undefined' || !bornadoInlineEdit.ajaxUrl) {
            return;
        }
        var fd = new FormData();
        fd.append('action', 'bornado_inline_edit_debug_ping');
        fd.append('ad_id', String(bornadoInlineEdit.adId || 0));
        fd.append('event_name', 'external_probe_boot');
        fd.append('payload', JSON.stringify({
            build: bornadoInlineEdit.debugBuild || '',
            href: window.location.href,
            readyState: document.readyState
        }));
        fetch(bornadoInlineEdit.ajaxUrl, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sendProbe);
    } else {
        sendProbe();
    }
})();
