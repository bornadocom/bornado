(function () {
    'use strict';

    if (window.__bornadoMessagePollGuardLoaded) {
        return;
    }
    window.__bornadoMessagePollGuardLoaded = true;

    // AJAX actions that AdForest/SB Chat poll on a timer. Each is throttled so a
    // slow response can never let overlapping requests pile up and exhaust the
    // browser's socket/memory budget (net::ERR_INSUFFICIENT_RESOURCES).
    var GUARDED_ACTIONS = {
        sb_check_messages: { inFlight: false, lastSentAt: 0, minIntervalMs: 20000, idleResponse: '0|' }
    };

    function readAction(data) {
        if (!data) {
            return '';
        }

        if (typeof data === 'string') {
            var match = data.match(/(?:^|&)action=([^&]+)/);
            return match ? decodeURIComponent(match[1].replace(/\+/g, ' ')) : '';
        }

        if (typeof data === 'object' && typeof data.action === 'string') {
            return data.action;
        }

        return '';
    }

    function skippedPromise($, idleResponse) {
        // Mimic a resolved jqXHR so existing .done()/.success() chains keep working
        // without firing a real network request.
        var deferred = $.Deferred();
        deferred.resolve(idleResponse, 'success', null);
        var promise = deferred.promise();
        promise.success = promise.done;
        promise.error = promise.fail;
        promise.complete = promise.always;
        promise.abort = function () {};
        return promise;
    }

    function ensureNewMessagesPayload(data) {
        if (typeof data === 'string') {
            if (/(?:^|&)new_msgs=/.test(data)) {
                return data;
            }

            return data + (data ? '&' : '') + 'new_msgs=0';
        }

        if (data && typeof data === 'object') {
            if (typeof data.new_msgs === 'undefined' || data.new_msgs === null || data.new_msgs === '') {
                data.new_msgs = '0';
            }

            return data;
        }

        return {
            action: 'sb_check_messages',
            new_msgs: '0'
        };
    }

    function attachGuard($) {
        if (!$ || typeof $.ajax !== 'function' || $.__bornadoPollGuardAttached) {
            return;
        }
        $.__bornadoPollGuardAttached = true;

        var originalAjax = $.ajax;

        $.ajax = function (url, options) {
            if (typeof url === 'object' && url !== null) {
                options = url;
                url = undefined;
            }
            options = options || {};

            var guard = GUARDED_ACTIONS[readAction(options.data)];
            if (!guard) {
                return url === undefined
                    ? originalAjax.call(this, options)
                    : originalAjax.call(this, url, options);
            }

            options.data = ensureNewMessagesPayload(options.data);

            var now = Date.now();
            if (guard.inFlight || (now - guard.lastSentAt) < guard.minIntervalMs) {
                return skippedPromise($, guard.idleResponse);
            }

            guard.inFlight = true;
            guard.lastSentAt = now;

            var originalComplete = options.complete;
            options.complete = function () {
                guard.inFlight = false;
                if (typeof originalComplete === 'function') {
                    originalComplete.apply(this, arguments);
                }
            };

            return url === undefined
                ? originalAjax.call(this, options)
                : originalAjax.call(this, url, options);
        };
    }

    if (window.jQuery) {
        attachGuard(window.jQuery);
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
            attachGuard(window.jQuery);
        }
    });
})();
