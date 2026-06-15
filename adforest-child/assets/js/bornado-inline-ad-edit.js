/**
 * Bornado — Inline Ad Edit (true per-element, in-place editing).
 *
 * The page IS the real single-ad view (pixel-identical at rest). Every editable
 * value is tagged in the template with `data-bornado-edit`. Clicking a value
 * turns THAT element, in its exact place, into its real AdForest form control
 * (borrowed from the hidden form). Confirming (✓ / picking an option) returns
 * the control to the form and previews the new value in place. The sticky
 * "Save" then runs AdForest's own untouched submit pipeline.
 *
 *  - Controls are never cloned (one live input → no value drift).
 *  - Only elements whose real control actually exists become editable, so we
 *    never advertise an affordance we cannot fulfil.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined' || typeof bornadoInlineEdit === 'undefined') {
        return;
    }

    var i18n = (bornadoInlineEdit && bornadoInlineEdit.i18n) ? bornadoInlineEdit.i18n : {};
    function t(key, fb) { return (i18n && i18n[key]) ? i18n[key] : fb; }
    function getAjaxEndpoint() {
        return (bornadoInlineEdit && bornadoInlineEdit.ajaxUrl) ? bornadoInlineEdit.ajaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
    }
    function debugLog(eventName, payload) {
        var ajaxEndpoint = getAjaxEndpoint();
        if (!ajaxEndpoint || !bornadoInlineEdit || !bornadoInlineEdit.adId) {
            return;
        }
        $.post(ajaxEndpoint, {
            action: 'bornado_inline_edit_debug_ping',
            ad_id: bornadoInlineEdit.adId,
            event_name: eventName,
            payload: JSON.stringify(payload || {})
        });
    }

    $(function () {
        var $section = $('.bornado-edit-mode').first();
        var $form    = $('#adforest-ad-post-form').first();
        var $bar     = $('#bornado-edit-bar').first();

        debugLog('boot', {
            build: (bornadoInlineEdit && bornadoInlineEdit.debugBuild) ? bornadoInlineEdit.debugBuild : '',
            href: window.location.href,
            hasSection: $section.length > 0,
            hasForm: $form.length > 0,
            hasBar: $bar.length > 0
        });

        if (!$section.length || !$form.length) {
            return;
        }

        var dirty      = false;
        var armed      = false;
        var saved      = false;
        var skipUnload = false;
        var saveState  = {
            pending: false,
            requestObserved: false,
            completed: false,
            watchdog: 0
        };
        var moved      = [];   // global: [{node, placeholder}]
        var editors    = [];   // open editors

        window.setTimeout(function () { armed = true; }, 2500);

        function norm(s) {
            return String(s || '').replace(/[\u200c\s]+/g, ' ').replace(/[:：*]/g, '').trim().toLowerCase();
        }

        function clearSaveWatchdog() {
            if (saveState.watchdog) {
                window.clearTimeout(saveState.watchdog);
                saveState.watchdog = 0;
            }
        }

        function resetSaveState() {
            clearSaveWatchdog();
            saveState.pending = false;
            saveState.requestObserved = false;
            saveState.completed = false;
        }

        function isSbAdPostingRequest(settings) {
            var data = settings && settings.data;
            if (typeof data === 'string') {
                return /(?:^|&)action=sb_ad_posting(?:&|$)/.test(data);
            }
            return !!(data && typeof data === 'object' && String(data.action || '') === 'sb_ad_posting');
        }

        function normalizeAdPostingResponse(responseText) {
            return $.trim(typeof responseText === 'string' ? responseText : String(responseText || ''));
        }

        function isAdPostingFailureResponse(responseText) {
            return [
                '',
                '0',
                '1',
                '2',
                '10',
                '11',
                'img_req',
                'no_product_pay_per_post',
                'no_ads'
            ].indexOf(normalizeAdPostingResponse(responseText)) !== -1;
        }

        /* ------------------------------------------------------------------ *
         * Detach / reattach (keep the hidden form serialisable for save).
         * ------------------------------------------------------------------ */
        function detach(node, editor) {
            if (!node || !node.parentNode) {
                return null;
            }
            if (node.getAttribute && node.getAttribute('data-bornado-borrowed') === '1') {
                return node;
            }
            var ph = document.createComment('bornado-slot');
            node.parentNode.insertBefore(ph, node);
            if (node.setAttribute) {
                node.setAttribute('data-bornado-borrowed', '1');
            }
            var entry = { node: node, placeholder: ph };
            moved.push(entry);
            if (editor) { editor.entries.push(entry); }
            return node;
        }

        function reattachEntry(entry) {
            if (entry.placeholder && entry.placeholder.parentNode) {
                entry.placeholder.parentNode.insertBefore(entry.node, entry.placeholder);
                entry.placeholder.parentNode.removeChild(entry.placeholder);
            } else if (entry.node && $form.get(0)) {
                // The original home was re-rendered (e.g. category cascade). Keep
                // the control alive by returning it to the form so it still saves
                // and can be borrowed again.
                $form.get(0).appendChild(entry.node);
            }
            if (entry.node && entry.node.removeAttribute) {
                entry.node.removeAttribute('data-bornado-borrowed');
            }
            for (var i = 0; i < moved.length; i++) {
                if (moved[i] === entry) { moved.splice(i, 1); break; }
            }
        }

        function reattachAll() {
            while (moved.length) {
                reattachEntry(moved[0]);
            }
        }

        /* ------------------------------------------------------------------ *
         * Resolve which real control(s) belong to a tagged element.
         * ------------------------------------------------------------------ */
        function pushNode(out, node) {
            if (!node) { return; }
            for (var i = 0; i < out.length; i++) {
                if (out[i] === node) { return; }
            }
            out.push(node);
        }

        function collect(list) {
            var out = [];
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var $found = $form.find(item.sel).first();
                if (!$found.length) {
                    continue;
                }
                var $wrap = $found;
                if (item.fb) {
                    var $fb = $found.closest('.field-box');
                    $wrap = $fb.length ? $fb : $found;
                } else if (item.closest) {
                    var $c = $found.closest(item.closest);
                    $wrap = $c.length ? $c : $found;
                }
                pushNode(out, $wrap.get(0));
                if ($wrap.is('select')) {
                    var $s2 = $wrap.next('.select2, .select2-container');
                    if ($s2.length) { pushNode(out, $s2.get(0)); }
                }
            }
            return out;
        }

        function oneOf(list) {
            for (var i = 0; i < list.length; i++) {
                var nodes = collect([list[i]]);
                if (nodes.length) { return nodes; }
            }
            return [];
        }

        function findCustomFieldByLabel(label) {
            var target = norm(label);
            if (!target) { return []; }
            var found = [];
            $form.find('.field-box, .form-group').each(function () {
                if (found.length) { return false; }
                var $fb = $(this);
                var $lbl = $fb.children('label').first();
                if (!$lbl.length) { $lbl = $fb.find('label').first(); }
                if (!$lbl.length) { return; }
                if (norm($lbl.text()) === target) {
                    found.push(this);
                    return false;
                }
            });
            return found;
        }

        var RESOLVERS = {
            title:       function () { return collect([{ sel: '#ad_title', fb: true }]); },
            tagline:     function () { return collect([{ sel: '#ad_tagline', fb: true }]); },
            description: function () { return collect([{ sel: '#ad_description', fb: true }]); },
            address:     function () { return collect([{ sel: '#ad_address', fb: true }]); },
            price:       function () {
                var out = [];
                var $priceType = $form.find('#ad_post_price_type').first();
                if ($priceType.length) {
                    var $priceRow = $priceType.closest('.row');
                    if ($priceRow.length) {
                        pushNode(out, $priceRow.get(0));
                    } else {
                        pushNode(out, $priceType.closest('.field-box').get(0) || $priceType.get(0));
                    }
                }

                var $currency = $form.find('#ad_currency').first();
                if ($currency.length) {
                    var $currencyBox = $currency.closest('.field-box');
                    pushNode(out, ($currencyBox.length ? $currencyBox : $currency).get(0));
                }

                return out;
            },
            currency:    function () { return collect([{ sel: '#ad_currency', fb: true }]); },
            adtype:      function () { return oneOf([{ sel: '.ad_type_container' }, { sel: '#ad_type', fb: true }]); },
            condition:   function () { return oneOf([{ sel: '.ad_condition_container' }, { sel: '#condition', fb: true }]); },
            warranty:    function () { return oneOf([{ sel: '.ad_warranty_container' }, { sel: '#warranty', fb: true }]); },
            category:    function () {
                return collect([
                    { sel: '#ad_post_category_select', self: true },
                    { sel: '#select-error', self: true },
                    { sel: '#child-category-container', self: true }
                ]);
            },
            location:    function () {
                // Keep location lightweight: only the first two levels (country
                // + city/state) participate in inline edit. Deeper empty levels
                // are what caused the laggy 4-field flash here.
                return collect([
                    { sel: '#ad_country', closest: '.col-md-6, .col-lg-6, .col-sm-6, .col-xs-12' },
                    { sel: '#ad_country_states', closest: '#ad_country_sub_div' }
                ]);
            },
            contact:     function () {
                // The real method-toggle UI (enable/disable + verification help),
                // built by the ad-post guard. Phone text itself is not editable.
                return collect([{ sel: '.bornado-contact-methods-col', self: true }]);
            },
            images:      function () { return collect([{ sel: '.ad_post_image_container', self: true }]); },
            cf:          function (key, label) { return findCustomFieldByLabel(label); }
        };

        function resolveNodes(key, label) {
            var fn = RESOLVERS[key];
            return fn ? fn(key, label) : [];
        }

        /* ------------------------------------------------------------------ *
         * Discoverability: only flag elements whose control truly exists.
         * (Re-runs as category-template fields load asynchronously.)
         * ------------------------------------------------------------------ */
        var ALWAYS_ON = [];

        function refreshAvailability() {
            $section.find('[data-bornado-edit]').each(function () {
                var $el = $(this);
                var key = $el.attr('data-bornado-edit');

                if (key === 'contact') {
                    mountContactBridge($el);
                    return;
                }
                // Always-on bridges/regions.
                if (ALWAYS_ON.indexOf(key) !== -1) {
                    mountPersistent(key, $el);
                    return;
                }
                if (key === 'images') {
                    mountImageBridge($el);
                    return;
                }

                if ($el.hasClass('bornado-editing')) { return; }
                var nodes = resolveNodes(key, $el.attr('data-bornado-cf-label') || '');
                $el.toggleClass('bornado-can-edit', nodes.length > 0);
            });
        }

        /* ------------------------------------------------------------------ *
         * Always-on regions (images, contact methods): the real control lives
         * permanently in place — no click to enter, no ✓ to leave.
         * ------------------------------------------------------------------ */
        var persistents = [];

        function fillBucket(bucket, host) {
            var nodes = resolveNodes(bucket.key, '');
            if (!nodes.length) { return false; }
            for (var i = 0; i < nodes.length; i++) {
                detach(nodes[i], bucket);
                host.appendChild(nodes[i]);
            }
            return true;
        }

        function removeBucket(bucket) {
            for (var i = 0; i < persistents.length; i++) {
                if (persistents[i] === bucket) { persistents.splice(i, 1); break; }
            }
        }

        function mountPersistent(key, $el) {
            var existing = $el.data('bornadoBucket');
            if (existing) {
                // Still healthy? (AdForest can destroy/recreate the image
                // container during a category change — detect & re-mount.)
                var $kids = existing.$fields.children();
                if ($kids.length && document.body.contains($kids.get(0))) {
                    return;
                }
                if (existing.$slot) { existing.$slot.remove(); }
                removeBucket(existing);
                $el.removeData('bornadoBucket');
            }

            var probe = resolveNodes(key, '');
            if (!probe.length) { return; }

            var $slot = $('<div class="bornado-inline-slot bornado-persistent-slot"><div class="bornado-slot-fields"></div></div>');
            var bucket = { key: key, $el: $el, $slot: $slot, $fields: $slot.find('.bornado-slot-fields'), entries: [] };

            $el.append($slot).addClass('bornado-mounted bornado-persistent');
            fillBucket(bucket, bucket.$fields.get(0));
            $el.data('bornadoBucket', bucket);
            persistents.push(bucket);
            refreshWidgets();
        }

        function remountPersistent() {
            for (var i = 0; i < persistents.length; i++) {
                var b = persistents[i];
                if (b.$fields && !b.$fields.children().length) {
                    fillBucket(b, b.$fields.get(0));
                }
            }
        }

        /* ------------------------------------------------------------------ *
         * Contact methods: keep the PUBLIC card intact and drive add/remove
         * through the real hidden method checkboxes in the form.
         * ------------------------------------------------------------------ */
        function getContactMethodsConfig() {
            return (window.bornadoAdPostGuard && window.bornadoAdPostGuard.contactMethods &&
                typeof window.bornadoAdPostGuard.contactMethods === 'object')
                ? window.bornadoAdPostGuard.contactMethods
                : null;
        }

        function getContactMethodOrder(config) {
            var map = config && config.statusMap ? config.statusMap : {};
            return Object.keys(map);
        }

        function getContactMethodByKey(key) {
            var config = getContactMethodsConfig();
            var map = config && config.statusMap ? config.statusMap : {};
            return (key && map[key]) ? map[key] : null;
        }

        function getContactMethodCheckbox(key) {
            return $form.find('input[name="bornado_contact_methods[]"][value="' + String(key || '') + '"]').first();
        }

        function getSelectedContactMethodKeys() {
            var keys = [];
            $form.find('input[name="bornado_contact_methods[]"]:checked').each(function () {
                var key = String($(this).val() || '');
                if (key && keys.indexOf(key) === -1) {
                    keys.push(key);
                }
            });
            return keys;
        }

        function setContactMethodSelected(key, selected) {
            var $checkbox = getContactMethodCheckbox(key);
            if (!$checkbox.length || $checkbox.is(':disabled')) {
                return false;
            }

            $checkbox.prop('checked', !!selected).trigger('change').trigger('input');
            if (armed) {
                dirty = true;
            }
            return true;
        }

        function getContactMethodIconSvg(type, label) {
            var icons = {
                chat: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16a2 2 0 012 2v9a2 2 0 01-2 2H8l-5 4v-4H4a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>',
                phone: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 01.99-.24c1.08.36 2.24.56 3.43.56a1 1 0 011 1V20a1 1 0 01-1 1C10.3 21 3 13.7 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.19.19 2.35.56 3.43a1 1 0 01-.25 1z"/></svg>',
                email: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6zm2 .5v.38l7 5.25 7-5.25V6.5H5zm14 2.88l-6.4 4.8a1 1 0 01-1.2 0L5 9.38V18h14V9.38z"/></svg>',
                whatsapp: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.05 4.94A9.9 9.9 0 0012.02 2a10 10 0 00-8.66 15l-1.3 4.75 4.87-1.28A10 10 0 1019.05 4.94zm-7.03 15.37a8.3 8.3 0 01-4.22-1.15l-.3-.18-2.88.76.77-2.81-.2-.3a8.3 8.3 0 1110.83 2.37 8.23 8.23 0 01-4 1.31zm4.54-6.2c-.25-.13-1.47-.72-1.7-.8-.23-.08-.39-.13-.56.13-.17.25-.64.8-.79.97-.15.17-.29.19-.54.06a6.7 6.7 0 01-1.97-1.21 7.4 7.4 0 01-1.36-1.7c-.14-.25-.02-.38.1-.51.11-.11.25-.29.37-.43.12-.14.16-.24.25-.4.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.76 2.68 4.27 3.76.6.26 1.08.42 1.45.53.61.2 1.16.17 1.59.1.49-.07 1.47-.6 1.67-1.18.21-.58.21-1.08.14-1.18-.06-.1-.23-.16-.48-.29z"/></svg>'
            };
            return '<span class="adf-mbn__icon" role="img" aria-label="' + escapeHtml(label || '') + '">' +
                (icons[type] || icons.chat) + '</span>';
        }

        function getContactChoiceStateUi(state) {
            if (state === 'selected') {
                return {
                    icon: '✓',
                    text: t('contactStateActive', 'فعال')
                };
            }

            if (state === 'disabled') {
                return {
                    icon: '🔒',
                    text: t('contactStateVerify', 'نیاز به تایید')
                };
            }

            return {
                icon: '+',
                text: t('contactStateAdd', 'غیرفعال')
            };
        }

        function getContactMethodClassSlug(key) {
            return String(key || '').replace(/_/g, '-');
        }

        function buildContactChoiceItemHtml(method, state) {
            var key = String((method && method.key) || '');
            var label = String((method && method.label) || '');
            var safeState = (state === 'selected' || state === 'disabled') ? state : 'inactive';
            var itemClass = 'adf-mbn__item adf-mbn__item--contact adf-mbn__item--contact-' + getContactMethodClassSlug(key) +
                ' bornado-contact-choice bornado-contact-choice--' + safeState;
            var stateUi = getContactChoiceStateUi(safeState);

            return '<li class="' + itemClass + '" data-bornado-contact-item="' + key + '">' +
                '<button type="button" class="adf-mbn__link adf-mbn__link--contact bornado-contact-choice-btn" data-method-key="' + escapeHtml(key) + '"' +
                ' data-contact-state="' + escapeHtml(safeState) + '" aria-pressed="' + (safeState === 'selected' ? 'true' : 'false') + '"' +
                ' aria-label="' + escapeHtml(label) + '">' +
                    '<span class="bornado-contact-choice-badge" aria-hidden="true">' + escapeHtml(stateUi.icon) + '</span>' +
                    getContactMethodIconSvg(key, label) +
                    '<span class="adf-mbn__label">' + escapeHtml(label) + '</span>' +
                    '<span class="bornado-contact-choice-state">' + escapeHtml(stateUi.text) + '</span>' +
                '</button>' +
            '</li>';
        }

        function ensureContactBridgeNav($el) {
            var $nav = $el.find('.adf-mobile-bottom-nav--contact').first();
            if ($nav.length) {
                return $nav;
            }

            $el.html('<nav class="adf-mobile-bottom-nav adf-mobile-bottom-nav--contact bornad-desktop-contact-nav" role="navigation" aria-label="روش های ارتباطی آگهی"><ul class="adf-mbn__list adf-mbn__list--contact"></ul></nav>');
            return $el.find('.adf-mobile-bottom-nav--contact').first();
        }

        function ensureContactBridgeNotice($el) {
            var $note = $el.find('.bornado-contact-inline-note').first();
            if ($note.length) {
                return $note;
            }

            $note = $('<div class="bornado-contact-inline-note" hidden></div>');
            $el.append($note);
            return $note;
        }

        function hideContactMethodNotice($el) {
            var $note = ($el && $el.length) ? $el.find('.bornado-contact-inline-note').first() : $();
            if ($note.length) {
                $note.prop('hidden', true).empty();
            }
        }

        function getContactMethodHelpHtml(method) {
            var config = getContactMethodsConfig();
            var label = method && method.label ? String(method.label) : '';
            var profileUrl = config && config.profileUrl ? String(config.profileUrl) : '';

            if (method && method.help_html) {
                return method.help_html;
            }

            return 'این روش ارتباطی (' + escapeHtml(label) + ') در پروفایل شما تایید نشده است. ' +
                (profileUrl ? 'برای تایید آن از <a href="' + escapeHtml(profileUrl) + '">ویرایش پروفایل</a> استفاده کنید.' : '');
        }

        function showContactMethodNotice($el, key) {
            if (!$el || !$el.length || !key) {
                return;
            }

            var method = getContactMethodByKey(key);
            if (!method) {
                return;
            }

            var $note = ensureContactBridgeNotice($el);
            $note.html(
                '<div class="bornado-contact-inline-note__body">' +
                    '<strong class="bornado-contact-inline-note__title">' + escapeHtml(method.label || '') + '</strong>' +
                    '<div class="bornado-contact-inline-note__text">' + getContactMethodHelpHtml(method) + '</div>' +
                '</div>' +
                '<button type="button" class="bornado-contact-inline-note__close" aria-label="' + escapeHtml(t('closeNote', 'بستن')) + '">&times;</button>'
            ).prop('hidden', false);
        }

        function syncContactBridge($el) {
            var config = getContactMethodsConfig();
            if (!$el || !$el.length || !config || !config.statusMap) {
                return;
            }

            var order = getContactMethodOrder(config);
            var selected = getSelectedContactMethodKeys();
            var selectedLookup = {};
            var itemsHtml = [];
            var i;
            var key;
            var method;
            var $nav = ensureContactBridgeNav($el);
            var $list = $nav.find('.adf-mbn__list').first();

            for (i = 0; i < selected.length; i++) {
                selectedLookup[selected[i]] = true;
            }

            for (i = 0; i < order.length; i++) {
                key = order[i];
                method = config.statusMap[key];
                if (!method) {
                    continue;
                }

                if (selectedLookup[key]) {
                    itemsHtml.push(buildContactChoiceItemHtml(method, 'selected'));
                } else if (method.enabled) {
                    itemsHtml.push(buildContactChoiceItemHtml(method, 'inactive'));
                } else {
                    itemsHtml.push(buildContactChoiceItemHtml(method, 'disabled'));
                }
            }

            $list.html(itemsHtml.join(''));
            $el.removeClass('bornado-can-edit bornado-editing').addClass('bornado-contact-bridge-ready');
        }

        function mountContactBridge($el) {
            if (!$el || !$el.length) {
                return;
            }

            var config = getContactMethodsConfig();
            if (!config || !config.enabled) {
                return;
            }

            if (!$form.find('input[name="bornado_contact_methods_version"]').length || !$form.find('input[name="bornado_contact_methods[]"]').length) {
                return;
            }

            syncContactBridge($el);
        }

        /* ------------------------------------------------------------------ *
         * Mobile choice sheet: app-like select UI for inline-edit dropdowns.
         * Reuses the shared bornado-mobile-choice styling, but only activates on
         * mobile and only inside the inline editor.
         * ------------------------------------------------------------------ */
        var mobileChoiceSheet = null;
        var mobileChoiceActiveSelect = null;

        function isMobileChoiceViewport() {
            if (window.matchMedia) {
                return window.matchMedia('(max-width: 768px)').matches;
            }
            return window.innerWidth <= 768;
        }

        function shouldUseMobileChoiceForSelect(selectEl) {
            var $select = $(selectEl);
            return !!(
                selectEl &&
                isMobileChoiceViewport() &&
                $select.length &&
                !$select.prop('multiple') &&
                $select.closest('.bornado-inline-slot').length
            );
        }

        function ensureMobileChoiceSheet() {
            if (mobileChoiceSheet && mobileChoiceSheet.length) {
                return mobileChoiceSheet;
            }

            mobileChoiceSheet = $(
                '<div class="bornado-mobile-choice__sheet bornado-inline-mobile-choice-sheet" hidden>' +
                    '<button type="button" class="bornado-mobile-choice__backdrop" aria-label="' + escapeHtml(t('close', 'بستن')) + '"></button>' +
                    '<div class="bornado-mobile-choice__panel" role="dialog" aria-modal="true" aria-label="' + escapeHtml(t('selectOption', 'انتخاب گزینه')) + '">' +
                        '<div class="bornado-mobile-choice__handle"></div>' +
                        '<div class="bornado-mobile-choice__panel-head">' +
                            '<h4 class="bornado-mobile-choice__panel-title"></h4>' +
                            '<button type="button" class="bornado-mobile-choice__close" aria-label="' + escapeHtml(t('close', 'بستن')) + '"><span aria-hidden="true">&times;</span></button>' +
                        '</div>' +
                        '<input type="search" class="bornado-mobile-choice__search" placeholder="' + escapeHtml(t('searchOptions', 'جستجو در گزینه‌ها')) + '">' +
                        '<div class="bornado-mobile-choice__empty" hidden>' + escapeHtml(t('noOptions', 'گزینه‌ای یافت نشد')) + '</div>' +
                        '<div class="bornado-mobile-choice__list" role="listbox"></div>' +
                    '</div>' +
                '</div>'
            );

            $('body').append(mobileChoiceSheet);
            return mobileChoiceSheet;
        }

        function getMobileChoiceTitle(selectEl) {
            var $select = $(selectEl);
            var $box = $select.closest('.field-box, .form-group');
            return getDynamicFieldLabel($box) || t('selectOption', 'انتخاب گزینه');
        }

        function buildMobileChoiceItemHtml(option, isSelected) {
            var value = String(option && option.value || '');
            var label = $.trim(option && option.text ? option.text : '');
            var disabled = !!(option && option.disabled);
            var classes = 'bornado-mobile-choice__item' + (isSelected ? ' is-selected' : '') + (disabled ? ' is-disabled' : '');

            return '<button type="button" class="' + classes + '" data-value="' + escapeHtml(value) + '"' +
                (disabled ? ' disabled aria-disabled="true"' : '') + '>' +
                    '<span class="bornado-mobile-choice__item-copy">' +
                        '<span class="bornado-mobile-choice__item-label">' + escapeHtml(label) + '</span>' +
                    '</span>' +
                    '<span class="bornado-mobile-choice__check" aria-hidden="true"></span>' +
                '</button>';
        }

        function renderMobileChoiceSheet(selectEl) {
            var $sheet = ensureMobileChoiceSheet();
            var $title = $sheet.find('.bornado-mobile-choice__panel-title');
            var $search = $sheet.find('.bornado-mobile-choice__search');
            var $empty = $sheet.find('.bornado-mobile-choice__empty');
            var $list = $sheet.find('.bornado-mobile-choice__list');
            var options = Array.prototype.slice.call(selectEl && selectEl.options ? selectEl.options : []);
            var selectedValue = String($(selectEl).val() || '');
            var html = [];
            var searchableCount = 0;

            $title.text(getMobileChoiceTitle(selectEl));

            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                var label = $.trim(option && option.text ? option.text : '');
                if (!label) {
                    continue;
                }
                html.push(buildMobileChoiceItemHtml(option, String(option.value || '') === selectedValue));
                if (!option.disabled) {
                    searchableCount++;
                }
            }

            $list.html(html.join(''));
            $search.val('');
            $search.toggle(searchableCount >= 8);
            $empty.prop('hidden', html.length > 0);
        }

        function openMobileChoiceSheet(selectEl) {
            if (!shouldUseMobileChoiceForSelect(selectEl)) {
                return;
            }
            if (mobileChoiceActiveSelect === selectEl && mobileChoiceSheet && mobileChoiceSheet.length && !mobileChoiceSheet.prop('hidden')) {
                return;
            }

            mobileChoiceActiveSelect = selectEl;
            renderMobileChoiceSheet(selectEl);
            ensureMobileChoiceSheet().prop('hidden', false);
            $('body').addClass('bornado-mobile-choice-open');

            window.setTimeout(function () {
                var $search = ensureMobileChoiceSheet().find('.bornado-mobile-choice__search:visible').first();
                if ($search.length) {
                    $search.trigger('focus');
                }
            }, 20);
        }

        function closeMobileChoiceSheet(options) {
            options = options || {};

            if (!mobileChoiceSheet || !mobileChoiceSheet.length) {
                mobileChoiceActiveSelect = null;
                return;
            }

            mobileChoiceSheet.prop('hidden', true);
            $('body').removeClass('bornado-mobile-choice-open');
            mobileChoiceActiveSelect = null;
        }

        function applyMobileChoiceSelection(value) {
            if (!mobileChoiceActiveSelect) {
                return;
            }

            var $select = $(mobileChoiceActiveSelect);
            $select.val(String(value || '')).trigger('change').trigger('input');
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change.select2');
            }
            closeMobileChoiceSheet({ keepEditor: true });
        }

        function resolveSelectFromContainerTarget(target) {
            var $container = $(target).closest('.select2-container');
            if (!$container.length) {
                return null;
            }

            var $select = $container.prevAll('select').first();
            return $select.length ? $select.get(0) : null;
        }

        function getEditorForNode(node) {
            if (!node) {
                return null;
            }

            for (var i = 0; i < editors.length; i++) {
                if (editors[i] && editors[i].$slot && editors[i].$slot.length && editors[i].$slot.get(0).contains(node)) {
                    return editors[i];
                }
            }

            return null;
        }

        function getEditorSelectSnapshot(editor) {
            if (!editor || !editor.$fields) {
                return '';
            }

            var values = [];
            editor.$fields.find('select').filter(':visible').each(function () {
                values.push(String($(this).val() || ''));
            });
            return values.join('||');
        }

        function getEditorMobileChoiceSelect(editor) {
            if (!editor || !editor.$fields) {
                return $();
            }

            if (editor.key === 'category') {
                var $categorySelects = getCategoryEditorSelects(editor);
                var $pending = $();
                $categorySelects.each(function () {
                    var value = $(this).val();
                    if (!value || !String(value).trim()) {
                        $pending = $(this);
                    }
                });
                return $pending.length ? $pending : $categorySelects.last();
            }
            if (editor.key === 'location') {
                var $locationSelects = getLocationEditorSelects(editor);
                var $locationPending = $();
                $locationSelects.each(function () {
                    var value = $(this).val();
                    if (!value || !String(value).trim()) {
                        $locationPending = $(this);
                    }
                });
                return $locationPending.length ? $locationPending : $locationSelects.last();
            }

            return editor.$fields.find('select').filter(':visible').first();
        }

        function getLocationEditorSelects(editor) {
            if (!editor || editor.key !== 'location' || !editor.$fields) {
                return $();
            }

            return editor.$fields.find('select[name="ad_country"], select[name="ad_country_states"]').filter(':visible');
        }

        function locationEditorHasPendingChild(editor) {
            var $selects = getLocationEditorSelects(editor);
            if (!$selects.length) {
                return false;
            }

            var hasEmpty = false;
            $selects.each(function () {
                var value = $(this).val();
                if (!value || !String(value).trim()) {
                    hasEmpty = true;
                    return false;
                }
            });

            return hasEmpty;
        }

        function focusDeepestLocationSelect(editor) {
            var $selects = getLocationEditorSelects(editor);
            if (!$selects.length) {
                return;
            }

            var $target = $();
            $selects.each(function () {
                var $sel = $(this);
                var value = $sel.val();
                if (!value || !String(value).trim()) {
                    $target = $sel;
                }
            });

            if (!$target.length) {
                $target = $selects.last();
            }

            if ($target.length) {
                try { $target.trigger('focus'); } catch (e) {}
            }
        }

        function scheduleLocationEditorClose(editor, delay) {
            if (!editor || editor.key !== 'location') {
                return;
            }

            window.clearTimeout(editor._locationCloseTimer);
            editor._locationCloseTimer = window.setTimeout(function () {
                if (!isEditorAlive(editor)) {
                    return;
                }

                if (locationEditorHasPendingChild(editor)) {
                    focusDeepestLocationSelect(editor);
                    return;
                }

                closeEditor(editor, true);
            }, delay || 320);
        }

        function maybeAutoOpenMobileChoice(editor, delay) {
            // For location, auto-opening the mobile sheet jumps straight into the
            // dependent city selector and hides the country field from view. Let
            // the stacked location controls render first so the user can choose
            // country or city explicitly.
            //
            // Price is also better without eager auto-open on mobile: selecting a
            // price type triggers AdForest hide/show mutations for the adjacent
            // price inputs, and opening the mobile sheet immediately makes focus
            // handoff to the text input fragile on touch devices.
            if (editor && (editor.key === 'location' || editor.key === 'price')) {
                return false;
            }

            var $initialSelect = getEditorMobileChoiceSelect(editor);
            if (!$initialSelect.length || !shouldUseMobileChoiceForSelect($initialSelect.get(0))) {
                return false;
            }

            window.setTimeout(function () {
                if (!isEditorAlive(editor)) {
                    return;
                }
                var $select = getEditorMobileChoiceSelect(editor);
                if ($select.length && shouldUseMobileChoiceForSelect($select.get(0))) {
                    openMobileChoiceSheet($select.get(0));
                }
            }, delay || 0);

            return true;
        }

        /* ------------------------------------------------------------------ *
         * Images: keep the PUBLIC gallery visible and drive uploads/removals
         * through the real hidden Dropzone in the background.
         * ------------------------------------------------------------------ */
        function getRealCarouselSlides($carousel) {
            if (!$carousel || !$carousel.length) {
                return $();
            }
            if ($carousel.hasClass('owl-loaded')) {
                return $carousel.find('> .owl-stage-outer > .owl-stage > .owl-item').not('.cloned');
            }
            return $carousel.children('.item');
        }

        function getSlideContentNode($slide) {
            if (!$slide || !$slide.length) {
                return $();
            }
            return $slide.hasClass('owl-item') ? $slide.children().first() : $slide;
        }

        function isVideoGallerySlide($slide) {
            return getSlideContentNode($slide).find('.video-box').length > 0;
        }

        function getGalleryVideoCount(bucket) {
            var count = 0;
            getRealCarouselSlides(bucket.$sync1).each(function () {
                if (isVideoGallerySlide($(this))) {
                    count++;
                }
            });
            return count;
        }

        function getImagePreviewNodes(bucket) {
            return bucket.$fields.find('.dz-preview');
        }

        function getVisibleImageSlideCount(bucket) {
            var total = getRealCarouselSlides(bucket.$sync1).length;
            return Math.max(0, total - getGalleryVideoCount(bucket));
        }

        function galleryThumbHasAddTile($thumbSlide) {
            return getSlideContentNode($thumbSlide).find('.bornado-gallery-add-btn').length > 0;
        }

        function galleryEscapeAttr(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function buildMainGallerySlide(fullSrc, imgSrc, alt) {
            var safeHref = galleryEscapeAttr(fullSrc || imgSrc);
            var safeSrc = galleryEscapeAttr(imgSrc);
            var safeAlt = galleryEscapeAttr(alt || '');
            return '<div class="item"><div class="img-box"><a href="' + safeHref +
                '" data-fancybox="gallery" class="lightbox"><img src="' + safeSrc +
                '" alt="' + safeAlt + '"></a></div></div>';
        }

        function imageIdentityFromUrl(url) {
            var clean = String(url || '').split('#')[0].split('?')[0];
            var parts = clean.split('/');
            return parts.length ? String(parts[parts.length - 1] || '').toLowerCase() : '';
        }

        function getSlideImageIdentity($slide) {
            var $node = getSlideContentNode($slide);
            var href = $node.find('a.lightbox').attr('href') || '';
            var src = $node.find('img').first().attr('src') || '';
            return imageIdentityFromUrl(href || src);
        }

        function buildThumbGallerySlide(src, alt) {
            var safeSrc = galleryEscapeAttr(src);
            var safeAlt = galleryEscapeAttr(alt || '');
            return '<div class="item"><div class="img-box"><img src="' + safeSrc +
                '" alt="' + safeAlt + '"></div></div>';
        }

        function buildGalleryAddTile() {
            return '<div class="item bornado-gallery-add-item"><div class="img-box bornado-gallery-add-box">' +
                '<span class="bornado-gallery-add-btn" role="button" tabindex="0" draggable="false" aria-label="' +
                galleryEscapeAttr(t('addField', 'افزودن')) + '"><span class="bornado-gallery-add-icon" aria-hidden="true">+</span></span>' +
                '</div></div>';
        }

        function buildExistingImageState(bucket) {
            var items = [];
            getRealCarouselSlides(bucket.$sync1).each(function () {
                var $slide = $(this);
                if (isVideoGallerySlide($slide)) {
                    return;
                }
                var $node = getSlideContentNode($slide);
                var $img = $node.find('img').first();
                if (!$img.length) {
                    return;
                }
                // The full-size lightbox URL is the stable identity source. We
                // keep it on the item so re-derived identities never drift to a
                // resized thumbnail filename (which would break server-id
                // hydration and silently keep removed images on save).
                var fullSrc = $node.find('a.lightbox').attr('href') || $img.attr('src') || '';
                items.push({
                    kind: 'existing',
                    originalIndex: items.length,
                    serverId: 0,
                    src: $img.attr('src') || '',
                    fullSrc: fullSrc,
                    alt: $img.attr('alt') || bucket.galleryAlt || '',
                    identity: imageIdentityFromUrl(fullSrc),
                    removed: false
                });
            });
            return items;
        }

        function getActiveImageStateItems(bucket) {
            var items = [];
            if (!bucket || !bucket.imageState) {
                return items;
            }

            for (var i = 0; i < bucket.imageState.existing.length; i++) {
                if (!bucket.imageState.existing[i].removed) {
                    items.push(bucket.imageState.existing[i]);
                }
            }
            for (var j = 0; j < bucket.imageState.pendingUploads.length; j++) {
                items.push(bucket.imageState.pendingUploads[j]);
            }
            return items;
        }

        function appendVisibleGalleryImage(bucket, item) {
            var imgSrc = (item && (item.src || item.fullSrc)) || '';
            var fullSrc = (item && (item.fullSrc || item.src)) || '';
            var alt = (item && item.alt) || bucket.galleryAlt || '';
            if (!bucket.$sync1.length || !imgSrc) {
                return;
            }

            var mainHtml = buildMainGallerySlide(fullSrc, imgSrc, alt);
            var thumbHtml = buildThumbGallerySlide(imgSrc, alt);

            if (bucket.$sync1.hasClass('owl-loaded')) {
                bucket.$sync1.trigger('add.owl.carousel', [$(mainHtml)]);
            } else {
                bucket.$sync1.append(mainHtml);
            }

            if (bucket.$sync2.length) {
                var insertAt = getRealCarouselSlides(bucket.$sync2).filter(function () {
                    return !galleryThumbHasAddTile($(this));
                }).length;

                if (bucket.$sync2.hasClass('owl-loaded')) {
                    bucket.$sync2.trigger('add.owl.carousel', [$(thumbHtml), insertAt]);
                } else if (bucket.$sync2.find('.bornado-gallery-add-item').length) {
                    bucket.$sync2.find('.bornado-gallery-add-item').first().before(thumbHtml);
                } else {
                    bucket.$sync2.append(thumbHtml);
                }
            }
        }

        function rebuildVisibleGalleryFromState(bucket) {
            if (!bucket || !bucket.imageState) {
                return;
            }

            clearVisibleImageSlides(bucket);

            var activeItems = getActiveImageStateItems(bucket);
            for (var i = 0; i < activeItems.length; i++) {
                appendVisibleGalleryImage(bucket, activeItems[i]);
            }

            if (bucket.$sync1.hasClass('owl-loaded')) {
                bucket.$sync1.trigger('refresh.owl.carousel');
            }
            if (bucket.$sync2.length && bucket.$sync2.hasClass('owl-loaded')) {
                bucket.$sync2.trigger('refresh.owl.carousel');
            }

            refreshGalleryControls(bucket);
        }

        function hydrateExistingImagesFromServerResponse(bucket, response) {
            var orderedIds = [];
            var i;
            var item;
            var existing;
            var visibleIdx = 0;

            if (!bucket || !bucket.imageState || !$.isArray(response)) {
                return 0;
            }

            existing = bucket.imageState.existing || [];
            for (i = 0; i < response.length; i++) {
                item = response[i];
                if (item && item.id) {
                    orderedIds.push(parseInt(item.id, 10) || 0);
                }
            }

            // At the start of every edit session, AdForest returns the gallery in
            // the same order the user sees on the page. Persist that exact order
            // onto our in-memory items so later save-time deletions are based on
            // stable attachment ids, including images that were uploaded in a
            // previous edit session and are now "existing".
            for (i = 0; i < existing.length; i++) {
                if (existing[i].removed) {
                    continue;
                }
                existing[i].serverId = orderedIds[visibleIdx] || 0;
                visibleIdx++;
            }

            // Fallback: if response/order changed unexpectedly, keep any ids we
            // can still recover by filename identity.
            if (visibleIdx !== orderedIds.length) {
                var map = {};
                var used = {};
                var key;

                for (i = 0; i < response.length; i++) {
                    item = response[i];
                    key = String((item && item.dispaly_name) || imageIdentityFromUrl(item && item.name) || '').toLowerCase();
                    if (key) {
                        map[key] = item;
                    }
                }

                for (i = 0; i < existing.length; i++) {
                    if (existing[i].removed || (existing[i].serverId || 0) > 0) {
                        continue;
                    }
                    key = String(existing[i].identity || '').toLowerCase();
                    if (key && map[key] && map[key].id) {
                        existing[i].serverId = parseInt(map[key].id, 10) || 0;
                        if (existing[i].serverId > 0) {
                            used[existing[i].serverId] = true;
                        }
                    }
                }

                var qi = 0;
                for (i = 0; i < existing.length; i++) {
                    if (existing[i].removed || (existing[i].serverId || 0) >= 1) {
                        continue;
                    }
                    while (qi < orderedIds.length && (!orderedIds[qi] || used[orderedIds[qi]])) {
                        qi++;
                    }
                    if (qi < orderedIds.length) {
                        existing[i].serverId = orderedIds[qi];
                        used[orderedIds[qi]] = true;
                        qi++;
                    }
                }
            }

            return orderedIds.length;
        }

        function countHydratedExistingImageIds(bucket) {
            var count = 0;
            var i;

            if (!bucket || !bucket.imageState) {
                return 0;
            }

            for (i = 0; i < bucket.imageState.existing.length; i++) {
                if ((bucket.imageState.existing[i].serverId || 0) > 0) {
                    count++;
                }
            }

            return count;
        }

        function syncExistingImageIds(bucket) {
            var done = $.Deferred();
            var ajaxEndpoint = getAjaxEndpoint();
            var nonce = $('#adforest_get_uploaded_ad_images_nonce').val();

            if (!bucket || !bucket.imageState) {
                done.resolve();
                return done.promise();
            }

            if (!bucket.imageState.existing.length) {
                done.resolve();
                return done.promise();
            }

            if (!nonce || !ajaxEndpoint) {
                done.resolve();
                return done.promise();
            }

            $.post(ajaxEndpoint, {
                action: 'get_uploaded_ad_images',
                is_update: bornadoInlineEdit.adId,
                security: nonce
            }).done(function (response) {
                hydrateExistingImagesFromServerResponse(bucket, response);
                done.resolve();
            }).fail(function () {
                done.resolve();
            });

            return done.promise();
        }

        function createImageQueueInput(bucket) {
            if (!bucket || bucket.$fileInput) {
                return;
            }

            bucket.$fileInput = $('<input type="file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple tabindex="-1" aria-hidden="true" style="display:none;">');
            bucket.$fileInput.on('change', function () {
                queuePendingImageFiles(bucket, this.files);
                this.value = '';
            });
            bucket.$slot.append(bucket.$fileInput);
        }

        function queuePendingImageFiles(bucket, files) {
            if (!bucket || !bucket.imageState || !files || !files.length) {
                return;
            }

            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                if (!file || !/^image\//i.test(file.type || '')) {
                    continue;
                }
                bucket.imageState.pendingUploads.push({
                    kind: 'pending',
                    tempId: 'new-' + bucket.imageState.nextTempId++,
                    file: file,
                    src: window.URL && window.URL.createObjectURL ? window.URL.createObjectURL(file) : '',
                    alt: file.name || bucket.galleryAlt || ''
                });
            }

            rebuildVisibleGalleryFromState(bucket);
            if (armed) {
                dirty = true;
            }
        }

        function removeQueuedImageAtIndex(bucket, imageIndex) {
            var activeItems = getActiveImageStateItems(bucket);
            var target = activeItems[imageIndex];
            if (!bucket || !bucket.imageState || !target) {
                return;
            }

            if (target.kind === 'pending') {
                for (var i = 0; i < bucket.imageState.pendingUploads.length; i++) {
                    if (bucket.imageState.pendingUploads[i].tempId === target.tempId) {
                        if (bucket.imageState.pendingUploads[i].src && window.URL && window.URL.revokeObjectURL) {
                            window.URL.revokeObjectURL(bucket.imageState.pendingUploads[i].src);
                        }
                        bucket.imageState.pendingUploads.splice(i, 1);
                        break;
                    }
                }
            } else {
                target.removed = true;
            }

            rebuildVisibleGalleryFromState(bucket);
            if (armed) {
                dirty = true;
            }
        }

        function ensureGalleryAddTile(bucket) {
            if (!bucket.$sync2.length || bucket.$sync2.find('.bornado-gallery-add-btn').length) {
                return;
            }
            var tileHtml = buildGalleryAddTile();
            if (bucket.$sync2.hasClass('owl-loaded')) {
                bucket.$sync2.trigger('add.owl.carousel', [$(tileHtml)]);
                bucket.$sync2.trigger('refresh.owl.carousel');
            } else {
                bucket.$sync2.append(tileHtml);
            }
        }

        function refreshGalleryControls(bucket) {
            if (!bucket.$sync2.length) {
                return;
            }

            ensureGalleryAddTile(bucket);

            var videoCount = getGalleryVideoCount(bucket);
            var imageCount = getVisibleImageSlideCount(bucket);
            var imageIndex = 0;

            getRealCarouselSlides(bucket.$sync2).each(function (slideIndex) {
                var $slide = $(this);
                var $node = getSlideContentNode($slide);
                var $existing = $node.find('.bornado-gallery-thumb-remove');
                var isImageSlide = !galleryThumbHasAddTile($slide) &&
                    slideIndex >= videoCount &&
                    imageIndex < imageCount;

                // Idempotent: never tear down a button that is already correct.
                // Rebuilding the ✕ on every availability refresh is what made the
                // first one or two clicks miss (the button vanished mid-click).
                if (!isImageSlide) {
                    if ($existing.length) {
                        $existing.remove();
                    }
                    if (slideIndex >= videoCount && !galleryThumbHasAddTile($slide)) {
                        imageIndex++;
                    }
                    return;
                }

                var $imgBox = $node.find('.img-box').first();
                if (!$imgBox.length) {
                    imageIndex++;
                    return;
                }

                if ($existing.length) {
                    if ($existing.attr('data-bornado-image-index') !== String(imageIndex)) {
                        $existing.attr('data-bornado-image-index', imageIndex);
                    }
                } else {
                    $imgBox.append(
                        $('<span class="bornado-gallery-thumb-remove" role="button" tabindex="0" draggable="false" aria-label="حذف تصویر"><span class="bornado-gallery-thumb-remove__icon" aria-hidden="true">&times;</span></span>')
                            .attr('data-bornado-image-index', imageIndex)
                    );
                }
                imageIndex++;
            });
        }

        function clearVisibleImageSlides(bucket) {
            if (!bucket.$sync1.length) {
                return;
            }

            var videoCount = getGalleryVideoCount(bucket);
            var imageCount = getVisibleImageSlideCount(bucket);

            for (var idx = imageCount - 1; idx >= 0; idx--) {
                var pos = videoCount + idx;
                if (bucket.$sync1.hasClass('owl-loaded')) {
                    bucket.$sync1.trigger('remove.owl.carousel', [pos]);
                } else {
                    getRealCarouselSlides(bucket.$sync1).eq(pos).remove();
                }

                if (bucket.$sync2.length) {
                    if (bucket.$sync2.hasClass('owl-loaded')) {
                        bucket.$sync2.trigger('remove.owl.carousel', [pos]);
                    } else {
                        getRealCarouselSlides(bucket.$sync2).eq(pos).remove();
                    }
                }
            }

            if (bucket.$sync1.hasClass('owl-loaded')) {
                bucket.$sync1.trigger('refresh.owl.carousel');
            }
            if (bucket.$sync2.length && bucket.$sync2.hasClass('owl-loaded')) {
                bucket.$sync2.trigger('refresh.owl.carousel');
            }
        }

        function getImageDropzone(bucket) {
            var el = bucket && bucket.$fields ? bucket.$fields.find('#img_dropzone').get(0) : null;
            if (!el) {
                el = $('#img_dropzone').get(0);
            }
            if (el && el.dropzone) {
                return el.dropzone;
            }
            return null;
        }

        function bindImageDropzone(bucket, dz) {
            if (!dz || bucket.dropzoneBound) {
                return;
            }

            bucket.dropzoneBound = true;
        }

        // AdForest's own hidden Dropzone stamps every preview's remove link with
        // the real attachment id (`data-dz-remove`) in gallery order. That DOM
        // is the authoritative, synchronous source of attachment ids — far more
        // reliable than the async filename-matching hydration. Reading it right
        // before we commit guarantees `keep_ids` carries the correct ids (and
        // never accidentally keeps a removed image).
        function getDropzoneIdEntries(bucket) {
            var entries = [];
            var $links = (bucket && bucket.$fields) ? bucket.$fields.find('a.dz-remove[data-dz-remove]') : $();
            if (!$links.length) {
                $links = $('#img_dropzone').find('a.dz-remove[data-dz-remove]');
            }
            $links.each(function () {
                var $link = $(this);
                var id = parseInt($link.attr('data-dz-remove'), 10) || 0;
                if (id < 1) {
                    return;
                }
                var name = $.trim($link.closest('.dz-preview').find('[data-dz-name]').first().text() || '');
                entries.push({ id: id, identity: imageIdentityFromUrl(name) });
            });
            return entries;
        }

        function resolveServerIdsFromDropzone(bucket) {
            if (!bucket || !bucket.imageState) {
                return;
            }
            var entries = getDropzoneIdEntries(bucket);
            if (!entries.length) {
                return;
            }

            var existing = bucket.imageState.existing;
            var byIdentity = {};
            var used = {};
            var i;
            var key;

            for (i = 0; i < entries.length; i++) {
                if (entries[i].identity && !byIdentity[entries[i].identity]) {
                    byIdentity[entries[i].identity] = entries[i].id;
                }
            }

            for (i = 0; i < existing.length; i++) {
                if (existing[i].removed) {
                    continue;
                }
                key = String(existing[i].identity || '').toLowerCase();
                if (key && byIdentity[key]) {
                    existing[i].serverId = byIdentity[key];
                    used[existing[i].serverId] = true;
                }
            }

            // After save-time deletions, the hidden dropzone only contains the
            // kept previews. Fallback alignment must therefore skip entries
            // already marked removed and consume the remaining ids in visible
            // order only.
            var qi = 0;
            for (i = 0; i < existing.length; i++) {
                if (existing[i].removed || (existing[i].serverId || 0) >= 1) {
                    continue;
                }
                while (qi < entries.length && used[entries[qi].id]) {
                    qi++;
                }
                if (qi < entries.length) {
                    existing[i].serverId = entries[qi].id;
                    used[entries[qi].id] = true;
                    qi++;
                }
            }
        }

        function callCapturedDeleteRequest(fn) {
            var originalPost = $.post;
            var captured = null;

            $.post = function () {
                var args = Array.prototype.slice.call(arguments);
                var data = args[1];
                if (data && data.action === 'delete_ad_image') {
                    captured = originalPost.apply($, args);
                    return captured;
                }
                return originalPost.apply($, args);
            };

            try {
                fn();
            } finally {
                $.post = originalPost;
            }

            return captured;
        }

        function flushRemovedImages(bucket, dz) {
            var outer = $.Deferred();
            outer.resolve();
            return outer.promise();
        }
        function flushQueuedUploads(bucket, dz) {
            var uploads = bucket.imageState.pendingUploads.slice();
            var outer = $.Deferred();

            function next(idx) {
                if (idx >= uploads.length) {
                    outer.resolve();
                    return;
                }

                var entry = uploads[idx];
                var handled = false;

                function cleanup() {
                    dz.off('success', onSuccess);
                    dz.off('error', onError);
                    dz.off('canceled', onError);
                }

                function onSuccess(file, responseText) {
                    if (file !== entry.file || handled) {
                        return;
                    }
                    handled = true;
                    cleanup();
                    entry.serverId = parseInt(String(responseText || '').split('|')[0], 10) || 0;
                    next(idx + 1);
                }

                function onError(file) {
                    if (file !== entry.file || handled) {
                        return;
                    }
                    handled = true;
                    cleanup();
                    outer.reject(t('imageUploadError', 'آپلود یکی از تصاویر انجام نشد.'));
                }

                dz.on('success', onSuccess);
                dz.on('error', onError);
                dz.on('canceled', onError);
                dz.addFile(entry.file);
            }

            next(0);
            return outer.promise();
        }

        function writeFinalImageArrangement(bucket) {
            var outer = $.Deferred();
            var ajaxEndpoint = getAjaxEndpoint();
            var nonce = bornadoInlineEdit && bornadoInlineEdit.syncImagesNonce ? bornadoInlineEdit.syncImagesNonce : '';
            var ids = [];
            var activeItems;
            var i;

            if (!bucket || !bucket.imageState) {
                debugLog('sync_skip_missing_bucket', { hasBucket: !!bucket, hasImageState: !!(bucket && bucket.imageState) });
                outer.resolve();
                return outer.promise();
            }

            if (!nonce || !ajaxEndpoint) {
                debugLog('sync_skip_missing_nonce', {
                    hasNonce: !!nonce,
                    hasAjaxUrl: !!ajaxEndpoint
                });
                outer.resolve();
                return outer.promise();
            }

            activeItems = getActiveImageStateItems(bucket);
            debugLog('sync_prepare', {
                activeItems: activeItems.map(function (item, index) {
                    return {
                        pos: index,
                        kind: item.kind || '',
                        serverId: item.serverId || 0,
                        originalIndex: item.originalIndex || 0,
                        identity: item.identity || '',
                        removed: !!item.removed
                    };
                }),
                noncePresent: !!nonce
            });
            for (i = 0; i < activeItems.length; i++) {
                if ((activeItems[i].serverId || 0) > 0) {
                    ids.push(activeItems[i].serverId);
                }
            }

            // Every visible image, old or newly uploaded, must now have a real
            // attachment id. If not, saving the gallery would be unsafe.
            for (i = 0; i < activeItems.length; i++) {
                if ((activeItems[i].serverId || 0) < 1) {
                    debugLog('sync_abort_missing_server_id', {
                        failedItem: {
                            pos: i,
                            kind: activeItems[i].kind || '',
                            identity: activeItems[i].identity || '',
                            serverId: activeItems[i].serverId || 0
                        }
                    });
                    outer.reject(t('imageEditorMissing', 'ویرایشگر تصاویر آماده نیست. صفحه را تازه‌سازی کنید.'));
                    return outer.promise();
                }
            }

            debugLog('sync_post', { keepIds: ids });
            $.post(ajaxEndpoint, {
                action: 'bornado_sync_ad_images',
                ad_id: bornadoInlineEdit.adId,
                keep_ids: ids.join(','),
                client_state: JSON.stringify(activeItems.map(function (item, index) {
                    return {
                        pos: index,
                        kind: item.kind || '',
                        serverId: item.serverId || 0,
                        originalIndex: item.originalIndex || 0,
                        identity: item.identity || '',
                        src: item.src || '',
                        fullSrc: item.fullSrc || ''
                    };
                })),
                security: nonce
            }).done(function (response) {
                debugLog('sync_done', { response: response });
                if (!response || response.success !== true) {
                    outer.reject((response && response.data && response.data.message) ? response.data.message : t('imageOrderError', 'ترتیب نهایی تصاویر ذخیره نشد.'));
                    return;
                }
                outer.resolve();
            }).fail(function () {
                debugLog('sync_fail', {});
                outer.reject(t('imageOrderError', 'ترتیب نهایی تصاویر ذخیره نشد.'));
            });

            return outer.promise();
        }

        function flushPendingImageChanges(bucket) {
            var outer = $.Deferred();

            if (!bucket || !bucket.imageState) {
                outer.resolve();
                return outer.promise();
            }

            $.when(bucket.imageHydration || $.Deferred().resolve()).done(function () {
                var dz = getImageDropzone(bucket);
                if (!dz) {
                    outer.reject(t('imageEditorMissing', 'ویرایشگر تصاویر آماده نیست. صفحه را تازه‌سازی کنید.'));
                    return;
                }

                flushRemovedImages(bucket, dz).done(function () {
                    flushQueuedUploads(bucket, dz).done(function () {
                        writeFinalImageArrangement(bucket).done(function () {
                            bucket.imageState.existing = getActiveImageStateItems(bucket).map(function (item) {
                                return {
                                    kind: 'existing',
                                    originalIndex: 0,
                                    serverId: item.serverId || 0,
                                    src: item.src,
                                    fullSrc: item.fullSrc || item.src || '',
                                    alt: item.alt || '',
                                    identity: item.identity || imageIdentityFromUrl(item.fullSrc || item.src || ''),
                                    removed: false
                                };
                            });
                            for (var k = 0; k < bucket.imageState.existing.length; k++) {
                                bucket.imageState.existing[k].originalIndex = k;
                            }
                            bucket.imageState.pendingUploads = [];
                            bucket.imageHydration = syncExistingImageIds(bucket);
                            outer.resolve();
                        }).fail(function (msg) {
                            outer.reject(msg);
                        });
                    }).fail(function (msg) {
                        outer.reject(msg);
                    });
                }).fail(function (msg) {
                    outer.reject(msg);
                });
            });

            return outer.promise();
        }

        function armImageBridge(bucket) {
            var dz = getImageDropzone(bucket);
            if (!dz) {
                window.setTimeout(function () {
                    armImageBridge(bucket);
                }, 250);
                return;
            }

            bindImageDropzone(bucket, dz);
            // Only build a fresh state (and hydrate server ids) when we don't
            // already carry one. Re-mounts preserve the existing state so the
            // correctly-hydrated server ids — and any queued removals — survive
            // instead of being recomputed from a possibly-rebuilt gallery.
            if (!bucket.imageState) {
                bucket.imageState = {
                    existing: buildExistingImageState(bucket),
                    pendingUploads: [],
                    nextTempId: 1
                };
                bucket.imageHydration = syncExistingImageIds(bucket);
            }
            createImageQueueInput(bucket);
            rebuildVisibleGalleryFromState(bucket);
        }

        function mountImageBridge($el) {
            var preservedState = null;
            var preservedHydration = null;
            var existing = $el.data('bornadoBucket');
            if (existing) {
                var $kids = existing.$fields.children();
                if ($kids.length && document.body.contains($kids.get(0))) {
                    // Don't tear the gallery down on every availability pass.
                    // Only do a full rebuild when the visible gallery no longer
                    // matches the tracked state (e.g. AdForest re-rendered the
                    // carousel); otherwise just make sure the controls exist.
                    if (existing.imageState) {
                        var visibleCount = getVisibleImageSlideCount(existing);
                        var activeCount = getActiveImageStateItems(existing).length;
                        if (visibleCount !== activeCount) {
                            rebuildVisibleGalleryFromState(existing);
                        } else {
                            refreshGalleryControls(existing);
                        }
                    } else {
                        refreshGalleryControls(existing);
                    }
                    return;
                }
                preservedState = existing.imageState || null;
                preservedHydration = existing.imageHydration || null;
                if (existing.$slot) {
                    existing.$slot.remove();
                }
                removeBucket(existing);
                $el.removeData('bornadoBucket');
            }

            var probe = resolveNodes('images', '');
            if (!probe.length) {
                return;
            }

            var $slot = $('<div class="bornado-inline-slot bornado-persistent-slot bornado-gallery-edit-slot" aria-hidden="true"><div class="bornado-slot-fields"></div></div>');
            var bucket = {
                key: 'images',
                $el: $el,
                $slot: $slot,
                $fields: $slot.find('.bornado-slot-fields'),
                $sync1: $el.find('#sync1').first(),
                $sync2: $el.find('#sync2').first(),
                galleryAlt: $el.find('#sync1 img').first().attr('alt') || '',
                entries: []
            };

            if (preservedState) {
                bucket.imageState = preservedState;
                bucket.imageHydration = preservedHydration;
            }

            $el.append($slot).addClass('bornado-mounted bornado-gallery-editing');
            fillBucket(bucket, bucket.$fields.get(0));
            $el.data('bornadoBucket', bucket);
            persistents.push(bucket);
            refreshWidgets();
            armImageBridge(bucket);
        }

        /* ------------------------------------------------------------------ *
         * Open / confirm an in-place editor for one tagged element.
         * ------------------------------------------------------------------ */
        function isTextControl($c) {
            if ($c.is('textarea')) { return true; }
            if (!$c.is('input')) { return false; }
            var ty = ($c.attr('type') || 'text').toLowerCase();
            return ['text', 'number', 'tel', 'url', 'email', 'search', ''].indexOf(ty) !== -1;
        }

        function fillSlot(editor) {
            var nodes = resolveNodes(editor.key, editor.label);
            if (!nodes.length) {
                return false;
            }
            var host = editor.$fields.get(0);
            for (var i = 0; i < nodes.length; i++) {
                detach(nodes[i], editor);
                host.appendChild(nodes[i]);
            }
            return true;
        }

        function isEditorAlive(editor) {
            return !!(editor && editor.$slot && editor.$slot.length && document.body.contains(editor.$slot.get(0)));
        }

        function getCategoryEditorSelects(editor) {
            if (!editor || editor.key !== 'category' || !editor.$fields) {
                return $();
            }
            return editor.$fields.find('select').filter(':visible');
        }

        function categoryEditorHasPendingChild(editor) {
            var $selects = getCategoryEditorSelects(editor);
            if (!$selects.length) {
                return false;
            }

            var hasEmpty = false;
            $selects.each(function () {
                var value = $(this).val();
                if (!value || !String(value).trim()) {
                    hasEmpty = true;
                    return false;
                }
            });

            return hasEmpty;
        }

        function focusDeepestCategorySelect(editor) {
            var $selects = getCategoryEditorSelects(editor);
            if (!$selects.length) {
                return;
            }

            var $target = $();
            $selects.each(function () {
                var $sel = $(this);
                var value = $sel.val();
                if (!value || !String(value).trim()) {
                    $target = $sel;
                }
            });

            if (!$target.length) {
                $target = $selects.last();
            }

            if ($target.length) {
                try { $target.trigger('focus'); } catch (e) {}
            }
        }

        function scheduleCategoryEditorClose(editor, delay) {
            if (!editor || editor.key !== 'category') {
                return;
            }

            window.clearTimeout(editor._categoryCloseTimer);
            editor._categoryCloseTimer = window.setTimeout(function () {
                if (!isEditorAlive(editor)) {
                    return;
                }

                if (categoryEditorHasPendingChild(editor)) {
                    focusDeepestCategorySelect(editor);
                    return;
                }

                closeEditor(editor, true);
            }, delay || 320);
        }

        function editorNeedsRefill(editor) {
            if (!isEditorAlive(editor)) {
                return false;
            }

            // Price does not have async dependent children like category/location.
            // Refilling it during AdForest's price-type mutations can steal focus
            // from the numeric input on mobile.
            if (editor.key === 'price') {
                return false;
            }

            var nodes = resolveNodes(editor.key, editor.label);
            if (!nodes.length) {
                return false;
            }

            var host = editor.$fields.get(0);
            for (var i = 0; i < nodes.length; i++) {
                if (!host.contains(nodes[i])) {
                    return true;
                }
            }

            return false;
        }

        function refillOpenEditor(editor) {
            if (!isEditorAlive(editor)) {
                return;
            }

            while (editor.entries.length) {
                reattachEntry(editor.entries.pop());
            }
            editor.$fields.empty();

            if (!fillSlot(editor)) {
                closeEditor(editor, false);
                return;
            }

            refreshWidgets();

            if (editor.key === 'category') {
                focusDeepestCategorySelect(editor);
                maybeAutoOpenMobileChoice(editor, 30);
                scheduleCategoryEditorClose(editor, 420);
            } else if (editor.key === 'location') {
                focusDeepestLocationSelect(editor);
                maybeAutoOpenMobileChoice(editor, 30);
                scheduleLocationEditorClose(editor, 420);
            }
        }

        function syncOpenEditors() {
            for (var i = 0; i < editors.length; i++) {
                if (editorNeedsRefill(editors[i])) {
                    refillOpenEditor(editors[i]);
                }
            }
        }

        function escapeHtml(value) {
            return $('<div/>').text(String(value || '')).html();
        }

        function cleanDynamicFieldLabel(value) {
            return $.trim(String(value || '').replace(/[\*:\u200c]+/g, ' ').replace(/\s+/g, ' '));
        }

        function getDynamicFieldLabel($box) {
            var $label = $box.children('label').first();
            if (!$label.length) {
                $label = $box.find('label').first();
            }
            if (!$label.length) {
                return '';
            }

            var $clone = $label.clone();
            $clone.find('.required, .fa, i, small').remove();
            return cleanDynamicFieldLabel($clone.text());
        }

        function isPlaceholderChoice(text) {
            var value = $.trim(String(text || ''));
            if (!value) {
                return true;
            }
            return ['---', 'select option', 'choose option', 'انتخاب کنید', 'انتخاب گزینه'].indexOf(value.toLowerCase()) !== -1;
        }

        function getChoiceLabelForControl($control) {
            if (!$control.length) {
                return '';
            }

            var id = $control.attr('id') || '';
            var $label = id ? $('label[for="' + id + '"]').first() : $();
            var text = $.trim($label.length ? $label.text() : String($control.val() || ''));
            if (text.indexOf('|') !== -1) {
                text = text.split('|').pop();
            }
            return cleanDynamicFieldLabel(text);
        }

        function shouldSkipDynamicFieldBox($box) {
            if (!$box || !$box.length) {
                return true;
            }

            if ($box.hasClass('bornado-hide-ad-currency') || $box.closest('.bornado-hide-ad-currency').length) {
                return true;
            }

            if ($box.find('.ad_post_image_container, #img_dropzone, #dropzone_video').length) {
                return true;
            }

            if ($box.find('#ad_post_category_select, #child-category-container, .country-heading, #child-country-container').length) {
                return true;
            }

            if ($box.find('#ad_price, #ad_price_from, #ad_price_to, #ad_post_price_type, #ad_currency').length) {
                return true;
            }

            if ($box.find('#ad_type, #condition, #warranty').length) {
                return true;
            }

            if ($box.find('input[name="ad_type"], input[name="ad_condition"], input[name="ad_warranty"]').length) {
                return true;
            }

            return !getDynamicFieldLabel($box);
        }

        function getSystemFieldItem(config) {
            if (!config || !config.inputSelector) {
                return null;
            }

            var $inputs = $form.find(config.inputSelector);
            if (!$inputs.length) {
                return null;
            }

            var $checked = $inputs.filter(':checked').first();
            var $group = $();

            if (config.rootSelector) {
                $group = $form.find(config.rootSelector).first();
            }
            if (!$group.length) {
                $group = $inputs.first().closest('.field-box, .form-group, .switch-btns-box');
            }

            var $labelHost = $group.closest('.field-box, .form-group');
            if (!$labelHost.length) {
                $labelHost = $group;
            }

            var label = getPreferredDisplayLabel(config.editKey, getDynamicFieldLabel($labelHost) || config.label || '');
            if (!label) {
                return null;
            }

            return {
                label: label,
                value: $checked.length ? getChoiceLabelForControl($checked) : '',
                editKey: config.editKey || ''
            };
        }

        function getDynamicSystemFieldItems() {
            var configs = [
                { editKey: 'adtype', rootSelector: '#ad_type', inputSelector: 'input[name="ad_type"]', label: 'نوع آگهی' },
                { editKey: 'condition', rootSelector: '#condition', inputSelector: 'input[name="ad_condition"]', label: 'وضعیت' },
                { editKey: 'warranty', rootSelector: '#warranty', inputSelector: 'input[name="ad_warranty"]', label: 'گارانتی' }
            ];
            var items = [];

            for (var i = 0; i < configs.length; i++) {
                var item = getSystemFieldItem(configs[i]);
                if (item) {
                    items.push(item);
                }
            }

            var locationSelectors = [
                'select[name="ad_country_towns"]',
                'select[name="ad_country_cities"]',
                'select[name="ad_country_states"]',
                'select[name="ad_country"]'
            ];
            var locationValue = '';
            for (var idx = 0; idx < locationSelectors.length; idx++) {
                // Read from the hidden real form too. The inline-edit shell keeps
                // the AdForest form off-screen, so `:visible` would incorrectly
                // hide valid location selects from the summary pipeline.
                var $select = $form.find(locationSelectors[idx]).first();
                if (!$select.length) {
                    continue;
                }
                var selectedText = $.trim($select.find('option:selected').text() || '');
                if (!isPlaceholderChoice(selectedText)) {
                    locationValue = selectedText;
                    break;
                }
            }

            if (locationValue) {
                items.push({
                    label: getPreferredDisplayLabel('location', 'موقعیت'),
                    value: locationValue,
                    editKey: 'location'
                });
            }

            return items;
        }

        function getPreferredDisplayLabel(editKey, fallback) {
            var key = $.trim(String(editKey || ''));
            if (!key) {
                return cleanDynamicFieldLabel(fallback);
            }

            var $value = $section.find('[data-bornado-edit="' + key + '"]').first();
            if ($value.length) {
                var $summaryItem = $value.closest('.bornad-summary-item');
                if ($summaryItem.length) {
                    var summaryLabel = cleanDynamicFieldLabel($summaryItem.find('.bornad-summary-label').first().text());
                    if (summaryLabel) {
                        return summaryLabel;
                    }
                }

                var $detailItem = $value.closest('li');
                if ($detailItem.length) {
                    var detailLabel = cleanDynamicFieldLabel($detailItem.children('span').first().text());
                    if (detailLabel) {
                        return detailLabel;
                    }
                }
            }

            return cleanDynamicFieldLabel(fallback);
        }

        function getDynamicFieldValue($box) {
            var $checkedRadios = $box.find('input[type="radio"]:checked');
            if ($checkedRadios.length) {
                return getChoiceLabelForControl($checkedRadios.first());
            }

            var $checkedCheckboxes = $box.find('input[type="checkbox"]:checked').not('#minimal-checkbox-1');
            if ($checkedCheckboxes.length) {
                var checkedValues = [];
                $checkedCheckboxes.each(function () {
                    var label = getChoiceLabelForControl($(this));
                    if (label) {
                        checkedValues.push(label);
                    }
                });
                return checkedValues.join('، ');
            }

            var $select = $box.find('select').first();
            if ($select.length) {
                var selectedText = $.trim($select.find('option:selected').text() || '');
                return isPlaceholderChoice(selectedText) ? '' : selectedText;
            }

            var $text = $box.find('textarea, input[type="text"], input[type="number"], input[type="tel"], input[type="url"], input[type="email"], input[type="date"], input[type="search"]').first();
            if ($text.length) {
                return $.trim(String($text.val() || ''));
            }

            return '';
        }

        function getDynamicCustomFieldItems() {
            var items = [];
            var seen = {};

            $form.find('#cat_template_html .field-box, #cat_template_html .form-group, #custom_field_container .field-box, #custom_field_container .form-group').each(function () {
                var $box = $(this);
                if (shouldSkipDynamicFieldBox($box)) {
                    return;
                }

                var label = getDynamicFieldLabel($box);
                var key = String(label || '').toLowerCase();
                if (!label || seen[key]) {
                    return;
                }

                seen[key] = true;
                items.push({
                    label: label,
                    value: getDynamicFieldValue($box),
                    editKey: ''
                });
            });

            return items;
        }

        function buildDynamicFieldValueHtml(item) {
            if (item && item.value) {
                return escapeHtml(item.value);
            }
            return '<span class="bornado-edit-empty">' + escapeHtml(t('emptyValue', '— تکمیل نشده —')) + '</span>';
        }

        function isDynamicEditorOpen() {
            for (var i = 0; i < editors.length; i++) {
                if (['cf', 'adtype', 'condition', 'warranty', 'location', 'price'].indexOf(editors[i].key) !== -1) {
                    return true;
                }
            }
            return false;
        }

        function syncDynamicCustomFieldDisplay() {
            var $summaryHost = $('#bornado-summary-dynamic-fields');

            if (!$summaryHost.length || !$form.length) {
                return;
            }

            if (isDynamicEditorOpen()) {
                return;
            }

            var systemItems = getDynamicSystemFieldItems();
            var customItems = getDynamicCustomFieldItems();
            var summaryItems = [];
            var summaryLookup = {};
            var i;

            for (i = 0; i < systemItems.length; i++) {
                if (summaryItems.length >= 4) {
                    break;
                }
                summaryItems.push(systemItems[i]);
                summaryLookup[String(systemItems[i].label || '').toLowerCase()] = true;
            }

            for (i = 0; i < customItems.length; i++) {
                if (customItems[i].value && summaryItems.length < 4) {
                    summaryItems.push(customItems[i]);
                    summaryLookup[String(customItems[i].label || '').toLowerCase()] = true;
                }
            }

            if ($summaryHost.length) {
                $summaryHost.empty();
                for (i = 0; i < summaryItems.length; i++) {
                    var editKey = summaryItems[i].editKey || 'cf';
                    var valueAttr = '';
                    if (editKey === 'cf') {
                        valueAttr = ' data-bornado-edit="cf" data-bornado-cf-label="' + escapeHtml(summaryItems[i].label) + '"';
                    } else {
                        valueAttr = ' data-bornado-edit="' + escapeHtml(editKey) + '"';
                    }
                    $summaryHost.append(
                        '<div class="bornad-summary-item" data-bornado-dynamic-cf-item="1">' +
                            '<span class="bornad-summary-label">' + escapeHtml(summaryItems[i].label) + '</span>' +
                            '<strong class="bornad-summary-value"' + valueAttr + '>' +
                                buildDynamicFieldValueHtml(summaryItems[i]) +
                            '</strong>' +
                        '</div>'
                    );
                }
            }
        }

        function openEditor($el) {
            if ($el.hasClass('bornado-editing')) {
                return;
            }
            var editor = {
                $el: $el,
                key: $el.attr('data-bornado-edit'),
                label: $el.attr('data-bornado-cf-label') || '',
                originalHtml: $el.html(),
                entries: []
            };

            var $slot = $('<div class="bornado-inline-slot"><div class="bornado-slot-fields"></div></div>');
            editor.$slot = $slot;
            editor.$fields = $slot.find('.bornado-slot-fields');

            var placed = false;
            if (editor.key === 'price') {
                $slot.addClass('bornado-inline-slot--price');
            }
            if (editor.key === 'location') {
                $slot.addClass('bornado-inline-slot--location');
                if (!isMobileChoiceViewport()) {
                    var $card = $section.find('.bornad-map-card').first();
                    if ($card.length) {
                        var $body = $card.find('.bornad-card-body').first();
                        if ($body.length) {
                            editor.$lockCard = $card.addClass('bornado-loc-editing');
                            $body.append($slot);
                            placed = true;
                        }
                    }
                }
            }
            if (!placed) {
                $el.after($slot);
            }

            if (!fillSlot(editor)) {
                $slot.remove();
                if (editor.$lockCard) { editor.$lockCard.removeClass('bornado-loc-editing'); }
                if (editor.key === 'location') { editor.$el.removeClass('bornado-keep-visible-while-editing'); }
                $el.removeClass('bornado-can-edit');
                return;
            }

            if (editor.key === 'location' && placed) {
                editor.$el.addClass('bornado-keep-visible-while-editing');
            }
            $el.addClass('bornado-editing').removeClass('bornado-can-edit');
            editor._openedAt = Date.now();
            editors.push(editor);

            var $texts = editor.$fields.find('input, textarea').filter(function () { return isTextControl($(this)); });
            var $choices = editor.$fields.find('select, input[type="radio"]');

            // A ✓ button is only for free text/number fields. Choice fields
            // (dropdowns, radio groups) confirm on selection; grouped fields
            // (category / location / contact / images) confirm on click-away.
            var hasFreeText = $texts.length > 0;
            var AUTO_CLOSE = ['cf', 'currency', 'adtype', 'condition', 'warranty'];

            if (hasFreeText) {
                var $confirm = $('<button type="button" class="bornado-slot-confirm" aria-label="' +
                    t('done', 'تمام') + '"><i class="fas fa-check" aria-hidden="true"></i></button>');
                $confirm.on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeEditor(editor, true);
                });
                $slot.append($confirm);

                $texts.filter('input').on('keydown.bornadoclose', function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        closeEditor(editor, true);
                    }
                });
            } else if (AUTO_CLOSE.indexOf(editor.key) !== -1) {
                $choices.on('change.bornadoclose', function () {
                    window.setTimeout(function () { closeEditor(editor, true); }, 80);
                });
            }

            if (editor.key === 'category') {
                editor.$fields.on('change.bornadocategory', 'select', function () {
                    scheduleCategoryEditorClose(editor, 320);
                });
            }
            if (editor.key === 'location') {
                editor.$fields.on('change.bornadolocation', 'select', function () {
                    scheduleLocationEditorClose(editor, 320);
                });
            }

            refreshWidgets();
            editor._mobileChoiceBaseline = getEditorSelectSnapshot(editor);

            var autoOpenedMobileChoice = maybeAutoOpenMobileChoice(editor, 0);

            var $first = editor.$fields.find('input, textarea, select').filter(':visible').first();
            if (!autoOpenedMobileChoice && $first.length && $first.attr('type') !== 'file') {
                try { $first.trigger('focus'); } catch (e2) {}
            }
            if (editor.key === 'category' && !autoOpenedMobileChoice) {
                focusDeepestCategorySelect(editor);
            } else if (editor.key === 'location' && !autoOpenedMobileChoice) {
                focusDeepestLocationSelect(editor);
            }
        }

        function applyPreview(editor) {
            var $f = editor.$fields;
            function txt(sel) { var $c = $f.find(sel).first(); return $c.length ? $.trim(String($c.val() || '')) : ''; }
            function selText(sel) {
                var $c = $f.find(sel).first();
                if (!$c.length) { return ''; }
                return $.trim($c.find('option:selected').text() || '');
            }

            switch (editor.key) {
                case 'title':
                    editor.$el.text(txt('#ad_title') || $.trim(editor.$el.text()));
                    break;
                case 'tagline':
                    setValueOrPlaceholder(editor, txt('#ad_tagline'));
                    break;
                case 'description':
                    setValueOrPlaceholder(editor, txt('#ad_description'));
                    break;
                case 'address':
                    setValueOrPlaceholder(editor, txt('#ad_address'));
                    break;
                case 'price': {
                    var amt = txt('#ad_price');
                    var amtFrom = txt('#ad_price_from');
                    var amtTo = txt('#ad_price_to');
                    var priceTypeValue = $.trim(String($f.find('#ad_post_price_type').val() || ''));
                    var priceTypeLabel = $.trim($f.find('#ad_post_price_type option:selected').text() || '');
                    var currencyLabel = $.trim($f.find('#ad_currency option:selected').text() || $f.find('#ad_currency').val() || '');
                    var $type = editor.$el.find('.bornad-price-type');
                    var $amount = editor.$el.find('.bornad-price-amount');
                    if (!$amount.length) { $amount = editor.$el; }
                    if (!$type.length && editor.$el.find('.bornad-price-amount').length) {
                        $type = $('<span class="bornad-price-type"></span>');
                        editor.$el.prepend($type);
                    }
                    if ($type.length) {
                        if (priceTypeLabel && !isPlaceholderChoice(priceTypeLabel) && priceTypeValue !== 'no_price') {
                            $type.html('<span class="negotiable-single">(' + escapeHtml(priceTypeLabel) + ')</span>').show();
                        } else {
                            $type.empty().hide();
                        }
                    }

                    if (priceTypeValue === 'free' && !amt) {
                        $amount.text(priceTypeLabel || 'Free');
                    } else if (priceTypeValue === 'on_call' && !amt) {
                        $amount.text(priceTypeLabel || 'Price On Call');
                    } else if (priceTypeValue === 'range' && (amtFrom || amtTo)) {
                        var rangeParts = [];
                        if (amtFrom) { rangeParts.push(amtFrom); }
                        if (amtTo) { rangeParts.push(amtTo); }
                        $amount.text($.trim(rangeParts.join(' - ')));
                    } else if (amt) {
                        $amount.text($.trim(amt + ' ' + currencyLabel));
                    } else {
                        $amount.html('<span class="bornado-edit-empty">افزودن قیمت</span>');
                    }
                    break;
                }
                case 'category': {
                    // Rebuild the breadcrumb from every chosen level (parent →
                    // child → …) so the read-only view reflects the new choice.
                    var parts = [];
                    $f.find('select').each(function () {
                        var $sel = $(this);
                        if (!$sel.val()) { return; }
                        var label = $.trim($sel.find('option:selected').text() || '');
                        if (label && label !== '---' && parts.indexOf(label) === -1) {
                            parts.push(label);
                        }
                    });
                    if (parts.length) {
                        editor.$el.html(parts.join('<span class="bornad-breadcrumb-sep">/</span>'));
                    }
                    break;
                }
                case 'adtype':
                case 'condition':
                case 'warranty': {
                    var $radio = $f.find('input[type="radio"]:checked').first();
                    var rv = '';
                    if ($radio.length) {
                        var $rlbl = $f.find('label[for="' + $radio.attr('id') + '"]').first();
                        rv = $.trim($rlbl.length ? $rlbl.text() : String($radio.val() || ''));
                        // AdForest radio values are often "id|name" — keep the name.
                        if (rv.indexOf('|') !== -1) { rv = rv.split('|').pop(); }
                    }
                    if (rv) { editor.$el.text(rv); }
                    break;
                }
                case 'cf':
                case 'currency': {
                    var $ctrl = $f.find('select, input, textarea').first();
                    var v = '';
                    if ($ctrl.is('select')) { v = $.trim($ctrl.find('option:selected').text() || ''); }
                    else { v = $.trim(String($ctrl.val() || '')); }
                    if (v) { editor.$el.text(v); }
                    break;
                }
                case 'location': {
                    var locationParts = [];
                    getLocationEditorSelects(editor).each(function () {
                        var label = $.trim($(this).find('option:selected').text() || '');
                        if (!label || isPlaceholderChoice(label)) {
                            return;
                        }
                        if (locationParts.indexOf(label) === -1) {
                            locationParts.push(label);
                        }
                    });
                    setValueOrPlaceholder(editor, locationParts.length ? locationParts[locationParts.length - 1] : '');
                    break;
                }
                default:
                    // Multi-part areas (location / contact / images) keep their
                    // existing display; the change persists on final Save.
                    break;
            }
        }

        function setValueOrPlaceholder(editor, value) {
            if (value) {
                editor.$el.text(value);
            } else if (/bornado-edit-empty/.test(editor.originalHtml || '')) {
                editor.$el.html(editor.originalHtml);
            } else {
                editor.$el.text('');
            }
        }

        function closeEditor(editor, apply) {
            window.clearTimeout(editor && editor._categoryCloseTimer);
            window.clearTimeout(editor && editor._locationCloseTimer);
            if (apply) {
                applyPreview(editor);
            }
            while (editor.entries.length) {
                reattachEntry(editor.entries.pop());
            }
            if (editor.$slot) { editor.$slot.remove(); }
            if (editor.$lockCard) { editor.$lockCard.removeClass('bornado-loc-editing'); }
            editor.$el.removeClass('bornado-editing bornado-keep-visible-while-editing').addClass('bornado-can-edit');

            for (var i = 0; i < editors.length; i++) {
                if (editors[i] === editor) { editors.splice(i, 1); break; }
            }
            if (armed) { dirty = true; }
        }

        /* ------------------------------------------------------------------ *
         * Form helpers (mirror the wizard's own behaviour).
         * ------------------------------------------------------------------ */
        function neutralizeTerms() {
            var $terms = $form.find('#minimal-checkbox-1');
            if ($terms.length) {
                $terms.prop('checked', true).removeAttr('required').removeAttr('data-parsley-required');
            }
        }

        function relaxHiddenRequired() {
            $form.find('[required], [data-parsley-required="true"]').each(function () {
                var $el = $(this);
                if (!$el.is(':visible') && 0 === $el.closest('.bornado-inline-slot').length) {
                    $el.removeAttr('required').removeAttr('data-parsley-required');
                }
            });
        }

        function refreshWidgets() {
            try {
                if (window.my_map && window.google && window.google.maps && window.google.maps.event) {
                    window.google.maps.event.trigger(window.my_map, 'resize');
                }
            } catch (e) {}
            $(window).trigger('resize');
        }

        /* ------------------------------------------------------------------ *
         * Save
         * ------------------------------------------------------------------ */
        function setSaving(on) {
            var $save = $('#bornado-edit-save');
            if (!$save.length) { return; }
            var $text = $save.find('.bornado-edit-bar__save-text');
            if (on) {
                $save.addClass('is-saving').prop('disabled', true);
                if ($text.length) { $text.text(t('saving', 'در حال ذخیره…')); }
            } else {
                $save.removeClass('is-saving').prop('disabled', false);
                if ($text.length) { $text.text(t('save', 'ذخیره تغییرات')); }
            }
        }

        function reopenAfterError() {
            for (var i = 0; i < editors.length; i++) {
                fillSlot(editors[i]);
            }
            remountPersistent();
        }

        function finalizeSaveFailure(options) {
            options = options || {};
            if (saveState.completed) {
                return;
            }

            saveState.completed = true;
            saveState.pending = false;
            clearSaveWatchdog();
            saved = false;
            skipUnload = false;
            setSaving(false);
            reopenAfterError();

            if (options.focusValidation) {
                focusFirstValidationError();
            }

            if (options.message && window.toastr) {
                toastr.error(options.message, '', {
                    timeOut: 5000,
                    closeButton: true,
                    positionClass: 'toast-top-right'
                });
            }
        }

        function finalizeSaveSuccess() {
            if (!saveState.pending || saveState.completed) {
                return;
            }

            saveState.completed = true;
            saveState.pending = false;
            clearSaveWatchdog();
            saved = true;
            dirty = false;
            skipUnload = true;
        }

        function focusFirstValidationError() {
            var $err = $form.find('.parsley-error').first();
            if ($err.length) {
                var top = $err.offset().top - 120;
                $('html, body').animate({ scrollTop: top < 0 ? 0 : top }, 300);
            }
        }

        function validateBeforeSave() {
            var parsley = (typeof $form.parsley === 'function') ? $form.parsley() : null;
            if (!parsley) {
                return true;
            }

            parsley.validate();
            return !(typeof parsley.isValid === 'function') || parsley.isValid();
        }

        function doSave() {
            skipUnload = true;
            setSaving(true);
            resetSaveState();

            reattachAll();
            neutralizeTerms();
            relaxHiddenRequired();
            if (!validateBeforeSave()) {
                finalizeSaveFailure({ focusValidation: true });
                return;
            }

            var imageBucket = $section.find('[data-bornado-edit="images"]').data('bornadoBucket');
            debugLog('save_clicked', {
                hasImageBucket: !!imageBucket,
                hasImageState: !!(imageBucket && imageBucket.imageState),
                existingCount: imageBucket && imageBucket.imageState ? imageBucket.imageState.existing.length : 0,
                pendingCount: imageBucket && imageBucket.imageState ? imageBucket.imageState.pendingUploads.length : 0
            });
            flushPendingImageChanges(imageBucket).done(function () {
                saveState.pending = true;
                saveState.requestObserved = false;
                saveState.completed = false;
                var realSubmit = $form.find('#ad_post_submit_button').get(0);
                if (realSubmit) {
                    realSubmit.click();
                } else {
                    $form.trigger('submit');
                }

                clearSaveWatchdog();
                saveState.watchdog = window.setTimeout(function () {
                    if (saveState.pending && !saveState.requestObserved) {
                        finalizeSaveFailure({
                            message: t('saveStartError', 'فرآیند ذخیره شروع نشد. صفحه را تازه‌سازی کنید و دوباره تلاش کنید.')
                        });
                    }
                }, 2200);
            }).fail(function (msg) {
                finalizeSaveFailure({ message: msg });
            });
        }

        /* ------------------------------------------------------------------ *
         * Wire up
         * ------------------------------------------------------------------ */
        $section.on('click', '[data-bornado-edit]', function (e) {
            if ($(e.target).closest('.bornado-inline-slot').length) {
                return;
            }
            var $el = $(e.target).closest('[data-bornado-edit]');
            if (!$el.length || $el.hasClass('bornado-editing') || !$el.hasClass('bornado-can-edit')) {
                return;
            }
            if ($(e.target).closest('a, button, [data-bs-toggle], .toggle-contact-number').length) {
                return;
            }
            e.preventDefault();
            openEditor($el);
        });

        $section.on('select2:opening.bornadomobilechoice', '.bornado-inline-slot select', function (e) {
            if (!shouldUseMobileChoiceForSelect(this)) {
                return;
            }
            e.preventDefault();
            openMobileChoiceSheet(this);
        });

        $section.on('mousedown.bornadomobilechoice touchstart.bornadomobilechoice click.bornadomobilechoice', '.bornado-inline-slot select', function (e) {
            if (!shouldUseMobileChoiceForSelect(this) || $(this).hasClass('select2-hidden-accessible')) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openMobileChoiceSheet(this);
        });

        $section.on('click.bornadomobilechoice', '.bornado-inline-slot .select2-container', function (e) {
            var selectEl = resolveSelectFromContainerTarget(e.target);
            if (!shouldUseMobileChoiceForSelect(selectEl)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openMobileChoiceSheet(selectEl);
        });

        $form.on('change input', 'input, select, textarea', function () {
            if (armed) { dirty = true; }
        });

        // Controls borrowed into slots/persistent regions live outside the form,
        // so track their edits here too (for the unsaved-changes guard).
        $section.on('change input', '.bornado-inline-slot :input', function () {
            if (armed) { dirty = true; }
        });

        if ($bar.length) {
            $bar.on('click', '.bornado-edit-bar__save', function (e) {
                e.preventDefault();
                doSave();
            });
            $bar.on('click', '.bornado-edit-bar__cancel', function () {
                skipUnload = true;
            });
        }

        function handleGalleryAddClick(btn) {
            var $gallery = $(btn).closest('[data-bornado-edit="images"]');
            var bucket = $gallery.data('bornadoBucket');
            if (bucket && bucket.$fileInput && bucket.$fileInput.length) {
                bucket.$fileInput.trigger('click');
            }
        }

        function handleGalleryThumbRemove(btn) {
            var $btn = $(btn);
            var imageIndex = parseInt($btn.attr('data-bornado-image-index') || '-1', 10);
            var $gallery = $btn.closest('[data-bornado-edit="images"]');
            var bucket = $gallery.data('bornadoBucket');
            if (!bucket || imageIndex < 0) {
                return;
            }

            removeQueuedImageAtIndex(bucket, imageIndex);
        }

        function toggleContactBridgeChoice(btn) {
            var key = String($(btn).attr('data-method-key') || '');
            var state = String($(btn).attr('data-contact-state') || '');
            var $host = $(btn).closest('[data-bornado-edit="contact"]');
            if (!key || !$host.length) {
                return;
            }

            if (state === 'disabled') {
                showContactMethodNotice($host, key);
                return;
            }

            if (setContactMethodSelected(key, state !== 'selected')) {
                hideContactMethodNotice($host);
                syncContactBridge($host);
            }
        }

        // Owl Carousel binds its own drag/click handlers on the thumbnail strip
        // and treats the first press on a control as a potential drag, which
        // swallows the first one or two clicks on our ✕ / ＋ buttons. We capture
        // the press events before Owl sees them so the very first click always
        // lands. (Capture phase + stopPropagation keeps Owl out of the way; the
        // native click below performs the actual action.)
        var sectionEl = $section.get(0);
        var GALLERY_CONTROL_SEL = '.bornado-gallery-thumb-remove, .bornado-gallery-add-btn';

        function closestGalleryControl(target) {
            return (target && target.closest) ? target.closest(GALLERY_CONTROL_SEL) : null;
        }

        ['pointerdown', 'mousedown', 'touchstart'].forEach(function (type) {
            document.addEventListener(type, function (e) {
                var ctrl = closestGalleryControl(e.target);
                if (ctrl && sectionEl && sectionEl.contains(ctrl)) {
                    if (type !== 'touchstart') {
                        e.preventDefault();
                    }
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                    e.stopPropagation();
                }
            }, true);
        });

        document.addEventListener('click', function (e) {
            if (!sectionEl || !e.target || !e.target.closest) {
                return;
            }
            if (mobileChoiceSheet && mobileChoiceSheet.length && !mobileChoiceSheet.prop('hidden')) {
                if (e.target.closest('.bornado-mobile-choice__backdrop, .bornado-mobile-choice__close')) {
                    var activeSelect = mobileChoiceActiveSelect;
                    var activeEditor = getEditorForNode(activeSelect);
                    var shouldRevertEditor = !!(
                        activeEditor &&
                        isEditorAlive(activeEditor) &&
                        getEditorSelectSnapshot(activeEditor) === String(activeEditor._mobileChoiceBaseline || '')
                    );
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileChoiceSheet();
                    if (shouldRevertEditor) {
                        closeEditor(activeEditor, false);
                    }
                    return;
                }
                var mobileChoiceItem = e.target.closest('.bornado-mobile-choice__item');
                if (mobileChoiceItem && mobileChoiceSheet.get(0).contains(mobileChoiceItem)) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!mobileChoiceItem.disabled) {
                        applyMobileChoiceSelection(mobileChoiceItem.getAttribute('data-value') || '');
                    }
                    return;
                }
            }
            var contactNoteCloseBtn = e.target.closest('.bornado-contact-inline-note__close');
            if (contactNoteCloseBtn && sectionEl.contains(contactNoteCloseBtn)) {
                e.preventDefault();
                e.stopPropagation();
                hideContactMethodNotice($(contactNoteCloseBtn).closest('[data-bornado-edit="contact"]'));
                return;
            }
            var contactChoiceBtn = e.target.closest('.bornado-contact-choice-btn');
            if (contactChoiceBtn && sectionEl.contains(contactChoiceBtn)) {
                e.preventDefault();
                e.stopPropagation();
                toggleContactBridgeChoice(contactChoiceBtn);
                return;
            }
            var removeBtn = e.target.closest('.bornado-gallery-thumb-remove');
            if (removeBtn && sectionEl.contains(removeBtn)) {
                e.preventDefault();
                e.stopPropagation();
                handleGalleryThumbRemove(removeBtn);
                return;
            }
            var addBtn = e.target.closest('.bornado-gallery-add-btn');
            if (addBtn && sectionEl.contains(addBtn)) {
                e.preventDefault();
                e.stopPropagation();
                handleGalleryAddClick(addBtn);
            }
        }, true);

        $section.on('click', '.bornad-edit-link', function (e) {
            e.preventDefault();
        });

        $form.on('change input', 'input[name="bornado_contact_methods[]"]', function () {
            syncContactBridge($section.find('[data-bornado-edit="contact"]').first());
        });

        $(document).on('ajaxSend.bornadoInlineEditSave', function (event, jqXHR, settings) {
            if (!saveState.pending || !isSbAdPostingRequest(settings)) {
                return;
            }

            saveState.requestObserved = true;
            clearSaveWatchdog();
        });

        $(document).on('ajaxSuccess.bornadoInlineEditSave', function (event, jqXHR, settings) {
            if (!saveState.pending || !isSbAdPostingRequest(settings)) {
                return;
            }

            if (isAdPostingFailureResponse(jqXHR && jqXHR.responseText)) {
                finalizeSaveFailure();
                return;
            }

            finalizeSaveSuccess();
        });

        $(document).on('ajaxError.bornadoInlineEditSave', function (event, jqXHR, settings) {
            if (!saveState.pending || !isSbAdPostingRequest(settings)) {
                return;
            }

            finalizeSaveFailure();
        });

        // Click-away confirms & closes open editors (the main "done" gesture for
        // grouped fields like category / location / contact / images). Clicks
        // inside a slot, popups (select2, maps autocomplete, datepickers) or the
        // save bar never count as "away".
        var IGNORE_AWAY = '.bornado-inline-slot, .bornado-edit-bar, .select2-container,' +
            ' .select2-dropdown, .select2-results, .pac-container, .ui-datepicker,' +
            ' .flatpickr-calendar, .datepicker, .ui-autocomplete,' +
            ' .bornado-mobile-choice__sheet, .bornado-mobile-choice__panel';

        $(document).on('mousedown.bornadoaway', function (e) {
            if (!editors.length) { return; }
            var $t = $(e.target);
            if ($t.closest(IGNORE_AWAY).length) { return; }

            var toClose = [];
            for (var i = 0; i < editors.length; i++) {
                var ed = editors[i];
                // Price editing contains both select2 and free-text input. Keep
                // it open until the user explicitly confirms, otherwise focus
                // and typing can be interrupted by outside-click heuristics.
                if (ed.key === 'price') { continue; }
                if (ed.$slot && $t.closest(ed.$slot).length) { continue; }
                if (Date.now() - (ed._openedAt || 0) < 350) { continue; }
                toClose.push(ed);
            }
            for (var j = 0; j < toClose.length; j++) {
                closeEditor(toClose[j], true);
            }
        });

        window.addEventListener('beforeunload', function (e) {
            if (dirty && !saved && !skipUnload) {
                var msg = t('unsavedLeave', 'تغییرات ذخیره‌نشده دارید. از این صفحه خارج می‌شوید؟');
                e.preventDefault();
                e.returnValue = msg;
                return msg;
            }
        });

        $(document).on('input.bornadomobilechoice', '.bornado-mobile-choice__search', function () {
            var term = $.trim(String(this.value || '')).toLowerCase();
            var $sheet = ensureMobileChoiceSheet();
            var $items = $sheet.find('.bornado-mobile-choice__item');
            var visible = 0;

            $items.each(function () {
                var label = $.trim($(this).find('.bornado-mobile-choice__item-label').text() || '').toLowerCase();
                var match = !term || label.indexOf(term) !== -1;
                $(this).toggle(match);
                if (match) {
                    visible++;
                }
            });

            $sheet.find('.bornado-mobile-choice__empty').prop('hidden', visible > 0);
        });

        window.addEventListener('resize', function () {
            if (mobileChoiceActiveSelect && !isMobileChoiceViewport()) {
                closeMobileChoiceSheet();
            }
        });

        // Availability: now, then a short burst of fast follow-up syncs while
        // AdForest's hidden form finishes its async injections. This preserves
        // the same resilience as the old 800/2000ms timers but makes editable
        // affordances appear much sooner on first load.
        function runAvailabilitySyncPass() {
            syncDynamicCustomFieldDisplay();
            refreshAvailability();
        }

        function scheduleInitialAvailabilityBurst() {
            var attempt = 0;
            var maxAttempts = 12;

            function tick() {
                runAvailabilitySyncPass();
                attempt++;
                if (attempt < maxAttempts) {
                    window.setTimeout(tick, 120);
                }
            }

            tick();
        }

        scheduleInitialAvailabilityBurst();

        if (window.MutationObserver) {
            var pending = null;
            var observer = new MutationObserver(function () {
                window.clearTimeout(pending);
                pending = window.setTimeout(function () {
                    syncDynamicCustomFieldDisplay();
                    syncOpenEditors();
                    refreshAvailability();
                }, 90);
            });
            observer.observe($form.get(0), { childList: true, subtree: true });
        }

        $('body').addClass('bornado-inline-edit-ready');
    });
})(typeof jQuery !== 'undefined' ? jQuery : null);
