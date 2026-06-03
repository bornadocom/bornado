(function ($) {
    'use strict';

    var config = window.BornadoSbChatBubbles || {};
    var originalAppend = $.fn.append;
    var originalHtml = $.fn.html;

    function formatBubbleTime(unix) {
        if (!unix) {
            return '';
        }

        var date = new Date(unix * 1000);
        try {
            return new Intl.DateTimeFormat(config.locale || 'fa-IR', {
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        } catch (error) {
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');
            return hours + ':' + minutes;
        }
    }

    function isMessagesListTarget(context) {
        if (!context || !context.length) {
            return false;
        }

        return context.is('.messages-list, .msg-body ul') || context.closest('.messages-list, .msg-body ul').length > 0;
    }

    function localizeContainerTimes(container) {
        if (!container || !container.querySelectorAll) {
            return;
        }

        var nodes = container.querySelectorAll('.bornado-bubble-time[data-unix]');
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            var unix = parseInt(node.getAttribute('data-unix') || '0', 10);
            var label = formatBubbleTime(unix);
            if (label) {
                node.textContent = label;
            }
        }
    }

    function markCardBubbles(scope) {
        if (!scope || !scope.querySelectorAll) {
            return;
        }

        var bubbles = scope.querySelectorAll('li.message-bubble');
        for (var i = 0; i < bubbles.length; i++) {
            var bubble = bubbles[i];
            var directCard = bubble.querySelector(':scope > .message-post-card');
            if (directCard) {
                bubble.classList.add('bornado-card-bubble');
            } else {
                bubble.classList.remove('bornado-card-bubble');
            }
        }
    }

    function localizeHtmlString(html) {
        if (typeof html !== 'string' || html.indexOf('bornado-bubble-time') === -1) {
            return html;
        }

        var wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        markCardBubbles(wrapper);
        localizeContainerTimes(wrapper);
        return wrapper.innerHTML;
    }

    function injectTempBubbleTime(html) {
        if (typeof html !== 'string') {
            return html;
        }

        if (html.indexOf('message-bubble new-message') === -1 || html.indexOf('bornado-bubble-time') !== -1) {
            return html;
        }

        var timeLabel = formatBubbleTime(Math.floor(Date.now() / 1000));
        if (!timeLabel) {
            return html;
        }

        return html.replace(
            /(<div class="message-text">\s*<p>)([\s\S]*?)(<\/p>\s*<\/div>)/i,
            '$1$2<span class="bornado-bubble-time" aria-hidden="true">' + timeLabel + '</span>$3'
        );
    }

    function transformInsertedHtml(context, html) {
        if (!isMessagesListTarget(context) || typeof html !== 'string') {
            return html;
        }

        html = injectTempBubbleTime(html);
        html = localizeHtmlString(html);
        return html;
    }

    $(function () {
        $.fn.append = function () {
            var args = Array.prototype.slice.call(arguments);
            if (args.length > 0 && typeof args[0] === 'string') {
                args[0] = transformInsertedHtml(this, args[0]);
            }
            return originalAppend.apply(this, args);
        };

        $.fn.html = function () {
            if (arguments.length > 0 && typeof arguments[0] === 'string') {
                arguments[0] = transformInsertedHtml(this, arguments[0]);
            }
            return originalHtml.apply(this, arguments);
        };

        markCardBubbles(document);
        localizeContainerTimes(document);
    });
})(jQuery);
