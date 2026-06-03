(function () {
    'use strict';

    if (window.__bornadoSbChatAdminProxyLoaded) {
        return;
    }
    window.__bornadoSbChatAdminProxyLoaded = true;

    function patchSource(source) {
        if (typeof source !== 'string' || source === '') {
            return source;
        }

        var scrollBlock = [
            "        var sbChatScrollState = { threshold: 80, detached: false };",
            "        function sbChatGetBody() {",
            "            return $('#sbModalBody');",
            "        }",
            "        function sbChatUpdateDetached() {",
            "            var $scrollBody = sbChatGetBody();",
            "            if (!$scrollBody.length) {",
            "                sbChatScrollState.detached = false;",
            "                return false;",
            "            }",
            "            var bodyEl = $scrollBody[0];",
            "            var distance = bodyEl.scrollHeight - bodyEl.clientHeight - bodyEl.scrollTop;",
            "            sbChatScrollState.detached = distance > sbChatScrollState.threshold;",
            "            return sbChatScrollState.detached;",
            "        }",
            "        function sbChatScrollToBottom(force) {",
            "            var $scrollBody = sbChatGetBody();",
            "            if (!$scrollBody.length) {",
            "                return;",
            "            }",
            "            if (force !== true) {",
            "                sbChatUpdateDetached();",
            "                if (sbChatScrollState.detached) {",
            "                    return;",
            "                }",
            "            }",
            "            $scrollBody.stop(true, false);",
            "            $scrollBody.scrollTop($scrollBody[0].scrollHeight);",
            "        }",
            "        $(document).on('scroll', '#sbModalBody', function () {",
            "            var distance = this.scrollHeight - this.clientHeight - this.scrollTop;",
            "            sbChatScrollState.detached = distance > sbChatScrollState.threshold;",
            "        });",
            "",
            "        const $body = sbChatGetBody();",
            "        if ($body.length) {",
            "            sbChatScrollToBottom(true);",
            "        }",
            "",
            "        const target = document.querySelector('#sbModalBody .messages-list');",
            "        if (target) {",
            "            const observer = new MutationObserver(function () {",
            "                sbChatScrollToBottom(false);",
            "            });",
            "",
            "            observer.observe(target, { childList: true });",
            "        }"
        ].join('\n');

        source = source.replace(
            /        const \$body = \$\('#sbModalBody'\);\s*        if \(\$body\.length\) \{\s*            \$body\.scrollTop\(\$body\[0\]\.scrollHeight\);\s*        \}\s*\s*        const target = document\.querySelector\('#sbModalBody \.messages-list'\);\s*        if \(target\) \{\s*            const observer = new MutationObserver\(\(\) => \{\s*                const \$body = \$\('#sbModalBody'\);\s*                if \(\$body\.length\) \{\s*                    \$body\.scrollTop\(\$body\[0\]\.scrollHeight\);\s*                \}\s*            \}\);\s*\s*            observer\.observe\(target, \{ childList: true \}\);\s*        \}/,
            scrollBlock
        );

        source = source.replace(
            /                    var scrollToTarget = \$\('#sbModalBody'\);\s*                    scrollToTarget\.animate\(\{scrollTop: 9999\}, 1000\);/,
            "                    sbChatScrollToBottom(true);"
        );

        source = source.replace(
            /                        \/\/ Scroll to bottom\s*                        var scrollToTarget = \$\('#sbModalBody'\);\s*                        if \(scrollToTarget\.length\) \{\s*                            scrollToTarget\.animate\(\{scrollTop: 9999\}, 500\);\s*                        \}/,
            "                        sbChatScrollToBottom(false);"
        );

        source = source.replace(
            /                    \/\/ Scroll to bottom after loading\s*                    setTimeout\(function\(\) \{\s*                        var scrollToTarget = \$\('#sbModalBody'\);\s*                        if \(scrollToTarget\.length\) \{\s*                            scrollToTarget\.animate\(\{scrollTop: 9999\}, 500\);\s*                        \}\s*                    \}, 300\);/,
            "                    setTimeout(function() {\n                        sbChatScrollToBottom(true);\n                    }, 300);"
        );

        return source;
    }

    function executePatchedSource(source) {
        var patched = patchSource(source);
        var blob = new Blob([patched], { type: 'text/javascript' });
        var blobUrl = URL.createObjectURL(blob);
        var script = document.createElement('script');
        script.src = blobUrl;
        script.onload = function () {
            URL.revokeObjectURL(blobUrl);
        };
        script.onerror = function () {
            URL.revokeObjectURL(blobUrl);
        };
        document.head.appendChild(script);
    }

    var sourceUrl = window.BornadoSbChatAdminSource || '';
    if (!sourceUrl) {
        return;
    }

    fetch(sourceUrl, { credentials: 'same-origin' })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Failed to load chat admin source');
            }
            return response.text();
        })
        .then(executePatchedSource)
        .catch(function (error) {
            console.error('Bornado chat proxy failed:', error);
        });
})();
