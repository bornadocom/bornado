/**
 * AdForest AJAX Search (Search 2.0).
 *
 * Converts the GET-based sidebar/topbar search into instant AJAX filtering
 * with URL sync, debounced text inputs, dynamic category fields, and a
 * Reset Filters control. Falls back to normal form submission when the
 * endpoint is unreachable or the bootstrap data is missing.
 *
 * Containers (rendered by PHP templates):
 *   #adforest-ajax-sidebar    — wraps every filter form (widget area)
 *   #adforest-ajax-results    — the listing container swapped on each request
 *   #adforest-ajax-count      — optional element populated with total count
 *   [data-adforest-reset]     — reset button
 *   [data-adforest-spinner]   — spinner element
 */
(function ($) {
    'use strict';

    if (typeof window.adforestAjaxSearch === 'undefined') {
        return;
    }

    var config = window.adforestAjaxSearch;
    var DEBOUNCE_MS = parseInt(config.debounce, 10) || 400;
    // Modes:
    //   'category_based' — fields swap when a category is picked
    //   'global'         — every category's fields are merged + shown up-front
    // Legacy 'category' value is normalized to 'category_based' on the PHP
    // side, so we don't need to handle it here.
    var FILTER_MODE = config.filterMode === 'global' ? 'global' : 'category_based';

    // Debug aid: when ?adforest_debug=1 is in the URL, PHP sets config.debug
    // and we surface the resolved + raw modes so it's obvious whether the
    // option round-tripped to JS correctly. Production pages stay silent.
    if (config.debug && typeof window.console !== 'undefined' && window.console.log) {
        window.console.log('[AdForest] FILTER_MODE:', FILTER_MODE,
            '| filterMode (PHP):', config.filterMode,
            '| rawMode (DB):', config.rawMode);
    }

    var SIDEBAR_SEL = '#adforest-ajax-sidebar';
    var RESULTS_SEL = '#adforest-ajax-results';
    var COUNT_SEL   = '#adforest-ajax-count';
    var SPINNER_SEL = '[data-adforest-spinner]';
    var RESET_SEL   = '[data-adforest-reset]';

    // Fields that should NOT trigger an auto-request on every change: the
    // view-type toggle, the radius lat/long hidden inputs, etc. Submit them
    // explicitly through their parent form instead.
    var AUTO_TRIGGER_SKIP = ['lat', 'long'];

    // Scalar (single-value) filter names whose hidden mirrors must be kept
    // in sync across widgets. The legacy `adforest_search_params` helper
    // duplicates these as hidden inputs in every form; without syncing,
    // collectFilters() can pick up stale values from the mirrors.
    var SCALAR_SYNC_FIELDS = [
        'cat_id', 'country_id', 'ad_currency', 'ad_title', 'ad_type',
        'adtype', 'condition', 'warranty', 'ad', 'sort', 'c',
        'min_price', 'max_price', 'location', 'rd', 'lat', 'long',
        'view-type'
    ];

    var currentRequest = null;
    // Remember the last filter string we ran so rapid-fire change events
    // (e.g., jQuery-UI slider) don't fire duplicate identical requests.
    var lastRunQs = null;

    // Root names of fields the user has touched in this session. The filter
    // builder layers these over the current URL params so untouched widget
    // defaults (like a price range the user never moved) never leak into
    // the outgoing query string.
    var dirtyFields = {};

    function markDirty(name) {
        if (!name) { return; }
        dirtyFields[name] = true;
        var root = name.split('[')[0];
        if (root && root !== name) { dirtyFields[root] = true; }
    }
    function clearDirty() { dirtyFields = {}; }
    function isDirty(name) {
        if (!name) { return false; }
        if (dirtyFields[name]) { return true; }
        var root = name.split('[')[0];
        return !!(root && dirtyFields[root]);
    }

    function hasContainers() {
        return $(SIDEBAR_SEL).length > 0 && $(RESULTS_SEL).length > 0;
    }

    /**
     * Collect the active filters as a URL-encoded query string.
     *
     * Strategy: start from whatever is already in the URL (those were the
     * user's explicit choices on prior interactions), then OVERLAY the
     * fields the user has touched this session. Untouched widget defaults
     * (max_price=10000000 on a slider the user never moved, etc.) are
     * never included, keeping URLs clean and SEO-friendly.
     */
    function collectFilters() {
        var result = [];                  // array of "key=val" strings in final order
        var seenScalar = {};              // dedup for scalar (non-[]) names
        var dirtyRoots = {};              // roots we must strip from URL baseline
        Object.keys(dirtyFields).forEach(function (k) { dirtyRoots[k] = true; });

        function push(key, val) {
            if (!key) { return; }
            if (val === null || typeof val === 'undefined' || val === '') { return; }
            if (key === 'security' || key === 'action' ||
                key === '_wpnonce' || key === '_wp_http_referer') { return; }
            // Final safety net: strip any widget-default value, no matter
            // whether it came from a dirty form or the URL overlay.
            if (isWidgetDefault(key, val)) { return; }
            if (key.indexOf('[]') !== -1) {
                result.push(encodeURIComponent(key) + '=' + encodeURIComponent(val));
                return;
            }
            if (seenScalar[key]) { return; }
            seenScalar[key] = true;
            result.push(encodeURIComponent(key) + '=' + encodeURIComponent(val));
        }

        // 1) Collect the user's touched fields from every sidebar form.
        $(SIDEBAR_SEL).find('form').each(function () {
            var $form = $(this);
            var defaultMin = $form.attr('data-adforest-default-min');
            var defaultMax = $form.attr('data-adforest-default-max');

            $.each($form.serializeArray(), function (_, pair) {
                if (!isDirty(pair.name)) { return; }
                if (isSkippedDefaultValue(pair, defaultMin, defaultMax, $form)) { return; }
                push(pair.name, pair.value);
            });
        });

        // Sort select lives in its own form outside the sidebar.
        $('#sort-form').each(function () {
            if (!isDirty('sort')) { return; }
            $.each($(this).serializeArray(), function (_, pair) {
                if (pair.name === 'sort') { push(pair.name, pair.value); }
            });
        });

        // 2) Overlay with URL params for anything the user hasn't touched —
        //    these were active filters from prior actions and must persist.
        //    Widget defaults (max_price=10000000, sort=id-desc, etc.) that
        //    leaked in from a prior legacy GET submission get stripped here
        //    so they don't live in the URL forever.
        var urlPairs = splitQueryPairs(window.location.search);
        urlPairs.forEach(function (p) {
            var root = p.key.split('[')[0];
            if (dirtyRoots[p.key] || dirtyRoots[root]) { return; }
            if (isWidgetDefault(p.key, p.value)) { return; }
            push(p.key, p.value);
        });

        // 3) view-type comes from the URL toggle anchors. Preserve if set.
        if (!('view-type' in seenScalar)) {
            var urlParams = parseQuery(window.location.search);
            if (urlParams['view-type']) { push('view-type', urlParams['view-type']); }
        }

        return result.join('&');
    }

    /**
     * Split a URL search string into an ordered array of {key, value}
     * pairs — `parseQuery` uses a flat object and loses ordering / dup
     * keys, which we need for array-style names.
     */
    function splitQueryPairs(qs) {
        if (!qs) { return []; }
        if (qs.charAt(0) === '?') { qs = qs.substring(1); }
        if (!qs) { return []; }
        return qs.split('&').map(function (piece) {
            var idx = piece.indexOf('=');
            var key = idx >= 0 ? piece.substring(0, idx) : piece;
            var val = idx >= 0 ? piece.substring(idx + 1) : '';
            try { key = decodeURIComponent(key); } catch (e) {}
            try { val = decodeURIComponent(val.replace(/\+/g, ' ')); } catch (e) {}
            return { key: key, value: val };
        });
    }

    /**
     * Return true when the given (key, value) matches a widget's configured
     * default — e.g., max_price=10000000 on an untouched slider, or
     * sort=id-desc which is the first option of the sort select. Applied
     * uniformly to both dirty-field output and URL-overlay output so
     * defaults never leak into the URL regardless of how they got there.
     */
    function isWidgetDefault(key, val) {
        if (key === 'min_price' || key === 'max_price') {
            var $priceForm = $(SIDEBAR_SEL + ' form[data-adforest-default-min], ' +
                               SIDEBAR_SEL + ' form[data-adforest-default-max]').first();
            if ($priceForm.length) {
                var attr = key === 'min_price' ? 'data-adforest-default-min' : 'data-adforest-default-max';
                var def  = $priceForm.attr(attr);
                if (typeof def !== 'undefined' && String(val) === String(def)) {
                    return true;
                }
            }
        }
        if (key === 'sort') {
            var $sortSelect = $('#select-sort, #sort-form select[name="sort"]').first();
            if ($sortSelect.length && $sortSelect[0].options && $sortSelect[0].options.length) {
                var firstOpt = $sortSelect[0].options[0].value;
                if (val === firstOpt) { return true; }
            }
        }
        if (key === 'paged' || key === 'page-number') {
            if (val === '1') { return true; }
        }
        return false;
    }

    /**
     * Skip specific defaults that widgets prefill without user intent
     * (e.g., the price slider's configured min/max). Returns true when
     * this pair represents an untouched default and should be excluded.
     */
    function isSkippedDefaultValue(pair, defaultMin, defaultMax, $form) {
        if (!pair || !pair.name) { return false; }
        if (pair.value === '' || pair.value === '0') { return true; }

        if (isWidgetDefault(pair.name, pair.value)) { return true; }

        // Per-input data-adforest-default lets any widget mark a default
        // inline without further JS changes. Match by attribute selector —
        // jQuery handles bracket-containing names correctly when quoted.
        var $input = $form.find('[name="' + pair.name.replace(/"/g, '\\"') + '"]').filter(function () {
            return this.value === pair.value;
        }).first();
        if ($input.length && typeof $input.attr('data-adforest-default') !== 'undefined' &&
            String(pair.value) === String($input.attr('data-adforest-default'))) {
            return true;
        }
        return false;
    }

    function parseQuery(qs) {
        var out = {};
        if (!qs) { return out; }
        if (qs.charAt(0) === '?') { qs = qs.substring(1); }
        qs.split('&').forEach(function (piece) {
            if (!piece) { return; }
            var idx = piece.indexOf('=');
            var key = idx >= 0 ? piece.substring(0, idx) : piece;
            var val = idx >= 0 ? piece.substring(idx + 1) : '';
            try { key = decodeURIComponent(key); } catch (e) {}
            try { val = decodeURIComponent(val.replace(/\+/g, ' ')); } catch (e) {}
            out[key] = val;
        });
        return out;
    }

    /**
     * Push a new URL reflecting the active filters. Browsers that don't
     * support pushState (very old) silently fall through.
     *
     * @param {string} qs URL-encoded query string, without leading "?".
     */
    function syncUrl(qs) {
        if (!window.history || !window.history.pushState) { return; }
        var base = window.location.pathname;
        var next = qs ? (base + '?' + qs) : base;
        if (next === window.location.pathname + window.location.search) { return; }
        try {
            window.history.pushState({ adforestFiltersRaw: qs }, '', next);
        } catch (e) { /* noop */ }
    }

    function showSpinner() {
        $(SPINNER_SEL).addClass('is-visible').show();
        $(SIDEBAR_SEL).addClass('adforest-ajax-loading');
        $(RESULTS_SEL).addClass('adforest-ajax-loading').attr('aria-busy', 'true');
        // Disable submit buttons to prevent double-submits.
        $(SIDEBAR_SEL).find('button[type="submit"], input[type="submit"]').prop('disabled', true);
    }

    function hideSpinner() {
        $(SPINNER_SEL).removeClass('is-visible').hide();
        $(SIDEBAR_SEL).removeClass('adforest-ajax-loading');
        $(RESULTS_SEL).removeClass('adforest-ajax-loading').removeAttr('aria-busy');
        $(SIDEBAR_SEL).find('button[type="submit"], input[type="submit"]').prop('disabled', false);
    }

    /**
     * Fire the AJAX search request. pushState + spinner are handled here so
     * every call site (change event, submit event, popstate) behaves the
     * same way.
     */
    function runSearch(options) {
        if (!hasContainers()) { return; }
        options = options || {};
        var qs     = (typeof options.filters === 'string') ? options.filters : collectFilters();
        var silent = !!options.silent; // true when replaying history.
        var force  = !!options.force;

        // Skip if the query hasn't changed since the last completed run
        // (common with noisy sliders and cascading change events).
        if (!force && lastRunQs === qs && !currentRequest) {
            return;
        }

        if (currentRequest && currentRequest.abort) {
            try { currentRequest.abort(); } catch (e) {}
        }

        showSpinner();
        if (!silent) { syncUrl(qs); }

        currentRequest = $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'adforest_ajax_search',
                security: config.nonce,
                filters_raw: qs
            }
        })
        .done(function (response) {
            if (!response || !response.success || !response.data) {
                renderError(qs);
                return;
            }
            lastRunQs = qs;
            var data = response.data;
            $(RESULTS_SEL).html(data.html || '');
            if (typeof data.total !== 'undefined') {
                $(COUNT_SEL).text(data.total + ' ' + config.i18n.adsFound);
            }
            // Re-run DOM-wiring hooks (e.g. tooltips, sliders) that may
            // depend on fresh markup. Themes / other scripts can listen.
            $(document).trigger('adforest:search:rendered', [data, qs]);
        })
        .fail(function (xhr, status) {
            if (status === 'abort') { return; }
            renderError(qs);
        })
        .always(function () {
            hideSpinner();
            currentRequest = null;
        });
    }

    /**
     * Render a non-destructive error notice inside the results container
     * with a retry button + optional full-page fallback link. Keeps the
     * user on the page so they don't lose their filter context on a
     * transient 500. Full reload is only offered as a last resort.
     */
    function renderError(qs) {
        var fallbackUrl = config.searchPageUrl
            ? (qs ? (config.searchPageUrl + '?' + qs) : config.searchPageUrl)
            : '';
        var retry = '<button type="button" class="btn btn-sm btn-dark" data-adforest-retry>'
            + (config.i18n.retry || 'Retry') + '</button>';
        var fallback = fallbackUrl
            ? ' <a class="btn btn-sm btn-link" href="' + fallbackUrl + '">'
              + (config.i18n.reload || 'Reload page') + '</a>'
            : '';
        var msg = config.i18n.error || 'Something went wrong. Please try again.';
        $(RESULTS_SEL).html(
            '<div class="adforest-ajax-error alert alert-warning" role="alert">'
            + '<p>' + msg + '</p>'
            + retry + fallback
            + '</div>'
        );
    }

    /**
     * Global mode: fetch every category's deduplicated dynamic fields once
     * and inject them into the sidebar. Called on initial page load when
     * the configured filter mode is `global`. Idempotent — guarded so
     * re-entry from popstate or other paths doesn't fire a duplicate
     * request mid-flight.
     */
    var globalFieldsLoaded = false;
    var globalFieldsRequest = null;
    function loadGlobalFields() {
        if (FILTER_MODE !== 'global') { return; }
        if (globalFieldsLoaded || globalFieldsRequest) { return; }

        globalFieldsRequest = $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'adforest_ajax_cat_fields',
                security: config.nonce,
                mode: 'global'
            }
        }).done(function (response) {
            if (!response || !response.success || !response.data) { return; }
            var $target = $('#adforest-ajax-dynamic-fields');
            if (!$target.length) {
                $target = $('<div id="adforest-ajax-dynamic-fields"></div>').appendTo(SIDEBAR_SEL);
            }
            $target.html(response.data.html || '');
            globalFieldsLoaded = true;
            $(document).trigger('adforest:search:global-fields', [response.data]);
        }).always(function () {
            globalFieldsRequest = null;
        });
    }

    /**
     * Category mode: swap the filter sidebar's custom-field block with one
     * scoped to the freshly selected category. The sidebar continues to
     * hold the primary filters (price, location, sort, etc.).
     */
    function refreshCategoryFields(catId) {
        if (FILTER_MODE !== 'category_based') { return; }
        if (!catId) { return; }

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'adforest_ajax_cat_fields',
                security: config.nonce,
                cat_id: catId
            }
        }).done(function (response) {
            if (!response || !response.success) { return; }
            var $target = $('#adforest-ajax-dynamic-fields');
            if (!$target.length) {
                $target = $('<div id="adforest-ajax-dynamic-fields"></div>').appendTo(SIDEBAR_SEL);
            }
            $target.html(response.data.html || '');
            $(document).trigger('adforest:search:dynamic-fields', [response.data]);
        });
    }

    /**
     * Debounce wrapper. Returns a function that delays the last call by
     * `wait` ms — used for typed inputs (search title, location, price).
     */
    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var ctx  = this;
            var args = arguments;
            if (timer) { clearTimeout(timer); }
            timer = setTimeout(function () {
                timer = null;
                fn.apply(ctx, args);
            }, wait);
        };
    }

    function fieldNameOf($el) {
        return $el.attr('name') || '';
    }

    function isSkippedField($el) {
        var name = fieldNameOf($el);
        if (!name) { return true; }
        for (var i = 0; i < AUTO_TRIGGER_SKIP.length; i++) {
            if (AUTO_TRIGGER_SKIP[i] === name) { return true; }
        }
        return false;
    }

    /**
     * Run a "fresh category" search. Clicking a category in the sidebar
     * is an explicit restart action: we wipe every other filter (price,
     * location, custom fields, sort, pagination) from both the UI and the
     * dirty registry, then fire a search with only `cat_id=X`. This keeps
     * the URL clean and matches user expectation ("show me Electronics").
     *
     * In **global** mode the same click is treated as a non-resetting
     * narrowing operation: the user sees every category's filters at once
     * and expects a category pick to layer on top of whatever they've
     * already entered (price, custom fields, etc.) without losing context.
     *
     * Invoked from two paths:
     *   - Direct-select mode click on `.category_click_link`
     *   - Native submit of `#search_cats_w` (modal-confirmed subcategory
     *     picks funnel through here as well)
     */
    function runCategorySearch(catId) {
        if (!catId) { return; }

        if (FILTER_MODE === 'global') {
            // Global mode: don't reset other filters and don't reload the
            // dynamic-field block (it's already showing every category's
            // fields). Just record the new cat_id and let collectFilters()
            // overlay it on top of the existing filter state.
            markDirty('cat_id');
            var $catInputG = $('#cat_id');
            if ($catInputG.length) { $catInputG.val(catId); }
            propagateFieldValue('cat_id', catId, $catInputG.get(0) || null);
            runSearch({ force: true });
            return;
        }

        // Reset every sidebar form except the categories form itself.
        $(SIDEBAR_SEL).find('form').each(function () {
            var $form = $(this);
            if ($form.attr('id') === 'search_cats_w') { return; }
            if (typeof this.reset === 'function') { this.reset(); }
            $form.find('input[type="hidden"]').each(function () {
                if (this.name) { $(this).val(''); }
            });
            $form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
            $form.find('select').each(function () {
                if (this.options.length) { this.selectedIndex = 0; }
            });
        });
        // Reset the standalone sort select so the UI reflects the new URL.
        $('#sort-form select').each(function () {
            if (this.options.length) { this.selectedIndex = 0; }
        });
        // Clear any async dynamic-field block rendered for a prior category.
        $('#adforest-ajax-dynamic-fields').empty();

        // Rebuild dirty registry: only cat_id is active now.
        clearDirty();
        markDirty('cat_id');

        // Set and broadcast the new cat_id to every mirror input on the page.
        var $catInput = $('#cat_id');
        if ($catInput.length) { $catInput.val(catId); }
        propagateFieldValue('cat_id', catId, $catInput.get(0) || null);

        refreshCategoryFields(catId);

        // Use an explicit qs instead of collectFilters() so nothing else
        // can slip in — this is the category-only guarantee.
        runSearch({ filters: 'cat_id=' + encodeURIComponent(catId), force: true });
    }

    /**
     * When a scalar filter is changed in one widget, propagate the new
     * value to every hidden mirror carrying the same name elsewhere on
     * the page. Without this step, collectFilters() can pick up a stale
     * value from a mirror rendered during initial page load.
     */
    function propagateFieldValue(name, value, sourceEl) {
        if (!name) { return; }
        if (SCALAR_SYNC_FIELDS.indexOf(name) === -1) { return; }
        $('[name="' + name + '"]').each(function () {
            if (this === sourceEl) { return; }
            var $i = $(this);
            if ($i.is(':checkbox') || $i.is(':radio')) { return; }
            if ($i.val() !== value) { $i.val(value); }
        });
    }

    function bindEvents() {
        // Intercept legacy form submits — every widget renders its own form.
        // Legacy code paths (e.g. category modal) set a hidden input then
        // submit a specific form, without firing change events. Before we
        // serialize, sync each scalar field from the submitting form to
        // every other mirror so the user's latest intent wins the dedup.
        $(document).on('submit', SIDEBAR_SEL + ' form, #sort-form', function (e) {
            if (!hasContainers()) { return; }
            e.preventDefault();
            var $form = $(this);

            // Category widget submissions — whether via direct-select mode
            // or modal-confirmed subcategory picks — route through the
            // "fresh category" path so the URL stays just `?cat_id=X`.
            if ($form.attr('id') === 'search_cats_w') {
                var catId = $form.find('input[name="cat_id"]').val();
                if (catId) {
                    runCategorySearch(catId);
                    return;
                }
            }

            // Any field in the submitted form is considered user-intent.
            // Mark them dirty so the overlay picks them up, and propagate
            // scalar values to every mirror input on the page.
            $form.find('[name]').each(function () {
                var n = this.name;
                if (!n) { return; }
                markDirty(n);
            });
            SCALAR_SYNC_FIELDS.forEach(function (name) {
                var $src = $form.find('[name="' + name + '"]').filter(function () {
                    return this.value !== '' && this.value !== '0';
                }).first();
                if ($src.length) {
                    propagateFieldValue(name, $src.val(), $src.get(0));
                }
            });
            runSearch();
        });

        // Auto-trigger on change for selects, checkboxes, radios.
        $(document).on(
            'change',
            SIDEBAR_SEL + ' select, ' +
            SIDEBAR_SEL + ' input[type="checkbox"], ' +
            SIDEBAR_SEL + ' input[type="radio"], ' +
            SIDEBAR_SEL + ' input[type="hidden"], ' +
            '#sort-form select',
            function () {
                if (!hasContainers()) { return; }
                var $el = $(this);
                if (isSkippedField($el)) { return; }
                var name = fieldNameOf($el);

                // Any cat_id change (e.g., a select-based category widget)
                // is a fresh-category action — route through the same path
                // as the widget click so the URL stays minimal.
                if (name === 'cat_id') {
                    var newCat = $el.val();
                    if (newCat) {
                        runCategorySearch(newCat);
                        return;
                    }
                }

                markDirty(name);

                // Propagate scalar values to their mirror inputs before we
                // serialize — otherwise stale mirrors win the dedup pass.
                propagateFieldValue(name, $el.val(), this);

                runSearch();
            }
        );

        // Debounced trigger for typed inputs (title, location, number ranges).
        var debouncedRun = debounce(function () { runSearch(); }, DEBOUNCE_MS);
        $(document).on(
            'input',
            SIDEBAR_SEL + ' input[type="text"], ' +
            SIDEBAR_SEL + ' input[type="search"], ' +
            SIDEBAR_SEL + ' input[type="number"]',
            function () {
                if (!hasContainers()) { return; }
                var $el = $(this);
                if (isSkippedField($el)) { return; }
                markDirty(fieldNameOf($el));
                propagateFieldValue(fieldNameOf($el), $el.val(), this);
                debouncedRun();
            }
        );

        // Retry button inside the error notice.
        $(document).on('click', '[data-adforest-retry]', function (e) {
            e.preventDefault();
            runSearch({ force: true });
        });

        // Reset filters: clear every form, drop query string, wipe the
        // dirty registry, and run a fresh search with an empty filter set.
        $(document).on('click', RESET_SEL, function (e) {
            e.preventDefault();
            $(SIDEBAR_SEL).find('form').each(function () {
                var f = this;
                // Native reset clears visible fields but leaves hidden inputs
                // that carry persisted state; explicitly zero those as well.
                if (typeof f.reset === 'function') { f.reset(); }
                $(f).find('input[type="hidden"]').each(function () {
                    var n = this.name;
                    if (!n) { return; }
                    if (n === 'cat_id' || n === 'country_id' || n === 'ad_currency') {
                        $(this).val('');
                    }
                });
                $(f).find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
                $(f).find('select').each(function () {
                    var $sel = $(this);
                    if (this.options.length) { this.selectedIndex = 0; }
                    // Many sidebar selects are wrapped by Select2
                    // (.default-select → $('.default-select').select2()).
                    // Setting `selectedIndex` directly updates the native
                    // <select> but the Select2 widget keeps painting the
                    // previously-chosen label — so the user sees their old
                    // filter even though the form is internally cleared.
                    // Fire a namespaced change so Select2 repaints without
                    // re-bubbling to the generic .submit_on_select listener
                    // (which would queue another AJAX search before the
                    // explicit runSearch({ filters: '', force: true }) below).
                    $sel.trigger('change.select2');
                });
            });
            if (FILTER_MODE === 'category_based') {
                $('#adforest-ajax-dynamic-fields').empty();
            }
            // Global-mode fields stay rendered after a reset — they're
            // category-agnostic, and the user reset *values*, not the
            // available filter set. Re-clear inputs we just rendered.
            if (FILTER_MODE === 'global') {
                $('#adforest-ajax-dynamic-fields').find('input, select').each(function () {
                    var $i = $(this);
                    if ($i.is(':checkbox') || $i.is(':radio')) {
                        $i.prop('checked', false);
                    } else if ($i.is('select') && this.options.length) {
                        this.selectedIndex = 0;
                    } else {
                        $i.val('');
                    }
                });
            }
            clearDirty();
            // Force because the user explicitly asked to re-fetch, and the
            // new qs may match lastRunQs when the sidebar started empty.
            runSearch({ filters: '', force: true });
        });

        // Intercept pagination + show-more links inside the results container.
        $(document).on('click', RESULTS_SEL + ' .pagination a.page-link', function (e) {
            if (!hasContainers()) { return; }
            var href = $(this).attr('href') || '';
            var m = href.match(/[\?&]paged?=([0-9]+)/i) || href.match(/\/page\/(\d+)/i);
            if (!m) { return; }
            e.preventDefault();
            var page = parseInt(m[1], 10) || 1;
            markDirty('paged');
            var qs = collectFilters();
            // Strip any existing paged param, then append the new one.
            qs = qs.split('&').filter(function (p) {
                return p && p.indexOf('paged=') !== 0 && p.indexOf('page-number=') !== 0;
            }).concat(['paged=' + page]).join('&');
            runSearch({ filters: qs });
            // Scroll to top of results for a natural UX.
            $('html, body').animate({ scrollTop: $(RESULTS_SEL).offset().top - 80 }, 200);
        });

        // Intercept category clickthroughs rendered by the sidebar widget.
        // The legacy handler submits #search_cats_w; we intercept earlier
        // so the filter state + URL sync go through one code path.
        $(document).on('click', SIDEBAR_SEL + ' .category_click_link', function (e) {
            var catId = $(this).attr('data-cat-id');
            if (!catId) { return; }
            // Let the legacy behavior handle modal-based subcat picking.
            // The final submit of #search_cats_w still routes through
            // runCategorySearch via the submit handler above.
            var showSubWithParent = $('#sb_show_sub_with_parent').length && $('#sb_show_sub_with_parent').val() === '1';
            if (!showSubWithParent) { return; }
            e.preventDefault();
            e.stopPropagation();
            runCategorySearch(catId);
        });

        // Browser back/forward: replay filters from the URL and start a
        // new interaction session (clear dirty tracking against the
        // freshly restored baseline).
        window.addEventListener('popstate', function () {
            var params = parseQuery(window.location.search);
            hydrateInputsFromQuery(params);
            clearDirty();
            // Run with the URL's raw query string — collectFilters() may
            // not reflect checkbox/radio state perfectly after hydration.
            var raw = window.location.search.replace(/^\?/, '');
            runSearch({ filters: raw, silent: true, force: true });
        });
    }

    /**
     * On initial load (or after popstate) reflect URL params into inputs.
     * Respects the existing DOM — we never *clear* a field that isn't in
     * the URL, so server-rendered defaults remain intact.
     */
    function hydrateInputsFromQuery(params) {
        if (!params) { return; }
        Object.keys(params).forEach(function (key) {
            var value = params[key];
            var $inputs = $('[name="' + key + '"]');
            if (!$inputs.length) {
                // array-style names like custom[brand] — try loose match.
                $inputs = $('[name^="' + key + '["]');
            }
            $inputs.each(function () {
                var $i = $(this);
                if ($i.is(':checkbox') || $i.is(':radio')) {
                    $i.prop('checked', $i.val() === value);
                } else {
                    $i.val(value);
                }
            });
        });
    }

    $(function () {
        if (!hasContainers()) { return; }
        bindEvents();

        // Global mode: render every category's fields up-front so the
        // user can filter without first picking a category.
        if (FILTER_MODE === 'global') {
            loadGlobalFields();
        }

        // If the page loaded with filters in the URL (SEO / shared link),
        // the server already rendered the correct initial state — no need
        // to re-fetch. We only trigger one when the user touches a filter.
        $(document).trigger('adforest:search:ready', [config]);
    });

    // Expose a small API for other scripts / themers. `run` accepts either
    // a URL-encoded query string or nothing (in which case the current
    // sidebar state is collected automatically).
    window.adforestAjaxSearchApi = {
        run: function (qs) { runSearch({ filters: qs }); },
        collect: collectFilters,
        refreshCategoryFields: refreshCategoryFields,
        loadGlobalFields: loadGlobalFields,
        mode: function () { return FILTER_MODE; }
    };

})(jQuery);
