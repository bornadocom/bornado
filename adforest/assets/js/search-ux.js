/**
 * AdForest Search 2.0 — Filter UX layer.
 *
 * Adds active-filter chips, a clear-all button, sidebar visual feedback,
 * and a mobile drawer toggle on top of the existing AJAX search system.
 * Pure additive: hooks into the public events emitted by search-ajax.js
 * (`adforest:search:ready`, `adforest:search:rendered`) and uses the
 * `adforestAjaxSearchApi` to trigger filtered runs. No backend touched.
 *
 * Containers it creates / manages:
 *   #adf-active-filters  — chip + clear-all bar above the results
 *   #adf-open-filters    — mobile-only "Filters" toggle
 *   .adf-filters-backdrop — dim layer behind the open mobile drawer
 *
 * Containers it reads (from search-ajax.js):
 *   #adforest-ajax-sidebar
 *   #adforest-ajax-results
 */
(function ($) {
    'use strict';

    if (!window.adforestAjaxSearch || !window.adforestAjaxSearchApi) {
        return;
    }

    var SIDEBAR_SEL = '#adforest-ajax-sidebar';
    var RESULTS_SEL = '#adforest-ajax-results';
    var CHIPS_ID    = 'adf-active-filters';
    var CHIPS_SEL   = '#' + CHIPS_ID;
    var DRAWER_BTN  = 'adf-open-filters';

    // Params we never show as a removable chip — display toggles, sort
    // selects, pagination etc. are not really "filters" the user is
    // accumulating. Wallow them with a Set-style lookup.
    var SKIPPED_PARAMS = {
        'view-type': 1,
        'page-number': 1,
        'paged': 1,
        'lat': 1,
        'long': 1,
        'sort': 1,
        '_wpnonce': 1,
        '_wp_http_referer': 1,
        'security': 1,
        'action': 1,
        'adforest_debug': 1
    };

    // Friendly label overrides for known scalar params. Anything not
    // listed here falls through to the generic humanizer.
    var LABEL_MAP = {
        'cat_id':       'Category',
        'country_id':   'Location',
        'ad_currency':  'Currency',
        'c':            'Currency',
        'ad_title':     'Search',
        'ad_type':      'Type',
        'adtype':       'Type',
        'condition':    'Condition',
        'warranty':     'Warranty',
        'ad':           'Featured',
        'min_price':    'Min Price',
        'max_price':    'Max Price',
        'location':     'Location',
        'rd':           'Within (km)'
    };

    /* ---------------------------------------------------------------- *
     * Chip container injection
     * ---------------------------------------------------------------- */

    function ensureChipContainer() {
        if ($(CHIPS_SEL).length) { return; }
        var $results = $(RESULTS_SEL);
        if (!$results.length) { return; }
        $('<div id="' + CHIPS_ID + '" class="adf-active-filters" role="region" aria-label="Active filters"></div>')
            .insertBefore($results);
    }

    /* ---------------------------------------------------------------- *
     * URL → chip data
     * ---------------------------------------------------------------- */

    function splitQueryPairs(qs) {
        if (!qs) { return []; }
        if (qs.charAt(0) === '?') { qs = qs.substring(1); }
        if (!qs) { return []; }
        return qs.split('&').filter(Boolean).map(function (piece) {
            var idx = piece.indexOf('=');
            var key = idx >= 0 ? piece.substring(0, idx) : piece;
            var val = idx >= 0 ? piece.substring(idx + 1) : '';
            try { key = decodeURIComponent(key); } catch (e) {}
            try { val = decodeURIComponent(val.replace(/\+/g, ' ')); } catch (e) {}
            return { key: key, value: val };
        });
    }

    /**
     * Look up a human label for a param. Strategy:
     *   1. Known scalar override in LABEL_MAP
     *   2. custom[<slug>] → derive from the input's <label> / placeholder
     *   3. min_custom[<slug>] / max_custom[<slug>] → "Min/Max <Field>"
     *   4. Fallback: humanize the key
     */
    function labelFor(key) {
        if (LABEL_MAP[key]) { return LABEL_MAP[key]; }

        var customMatch = key.match(/^custom\[(.+?)\](\[\])?$/);
        if (customMatch) {
            return labelForSlug(customMatch[1]) || humanize(customMatch[1]);
        }
        var minMatch = key.match(/^min_custom\[(.+?)\]$/);
        if (minMatch) {
            return 'Min ' + (labelForSlug(minMatch[1]) || humanize(minMatch[1]));
        }
        var maxMatch = key.match(/^max_custom\[(.+?)\]$/);
        if (maxMatch) {
            return 'Max ' + (labelForSlug(maxMatch[1]) || humanize(maxMatch[1]));
        }
        return humanize(key);
    }

    function labelForSlug(slug) {
        // Match an input whose name references this slug, then climb to
        // the nearest accordion-button / panel header to grab its text.
        var sel = '[name="custom[' + slug + ']"], [name="custom[' + slug + '][]"], '
                + '[name="min_custom[' + slug + ']"], [name="max_custom[' + slug + ']"]';
        var $input = $(SIDEBAR_SEL + ' ' + sel).first();
        if (!$input.length) { return ''; }
        // Try the nearest accordion button text.
        var $accBtn = $input.closest('.accordion-item').find('.accordion-button').first();
        if ($accBtn.length && $.trim($accBtn.text())) {
            return $.trim($accBtn.text()).replace(/\s+/g, ' ');
        }
        // Fall back to placeholder or name.
        var ph = $input.attr('placeholder');
        return ph ? $.trim(ph) : '';
    }

    function humanize(key) {
        return key.replace(/[\[\]]/g, ' ')
                  .replace(/[_-]+/g, ' ')
                  .replace(/\s+/g, ' ')
                  .trim()
                  .replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    /**
     * Resolve the displayed value for a chip — for selects/radios we
     * prefer the option label over the raw value.
     */
    function valueFor(key, value) {
        var sel = '[name="' + cssEscape(key) + '"]';
        var $inp = $(SIDEBAR_SEL + ' ' + sel).first();
        if ($inp.length && $inp.is('select')) {
            var $opt = $inp.find('option').filter(function () { return this.value === value; }).first();
            if ($opt.length) { return $.trim($opt.text()) || value; }
        }
        if ($inp.length && ($inp.is(':radio') || $inp.is(':checkbox'))) {
            var $match = $(SIDEBAR_SEL + ' [name="' + cssEscape(key) + '"][value="' + cssEscape(value) + '"]').first();
            if ($match.length) {
                var $lab = $('label[for="' + $match.attr('id') + '"]');
                if ($lab.length) { return $.trim($lab.text()) || value; }
            }
        }
        return value;
    }

    function cssEscape(s) {
        return String(s).replace(/(["\\])/g, '\\$1');
    }

    /* ---------------------------------------------------------------- *
     * Chip rendering
     * ---------------------------------------------------------------- */

    function renderChips() {
        ensureChipContainer();
        var $bar = $(CHIPS_SEL);
        if (!$bar.length) { return; }

        var pairs = splitQueryPairs(window.location.search);
        var chips = [];
        pairs.forEach(function (p) {
            if (!p.key) { return; }
            if (p.value === '' || p.value === '0') { return; }
            // Strip array suffix from the lookup key.
            var lookupKey = p.key.replace(/\[\]$/, '');
            var rootKey   = lookupKey.split('[')[0];
            if (SKIPPED_PARAMS[rootKey]) { return; }

            chips.push({
                key:    p.key,
                value:  p.value,
                label:  labelFor(lookupKey),
                shown:  valueFor(lookupKey, p.value)
            });
        });

        if (!chips.length) {
            $bar.empty().removeClass('is-visible');
            return;
        }

        var html = '';
        chips.forEach(function (c) {
            html += '<span class="adf-chip" '
                +  'data-key="' + escapeAttr(c.key) + '" '
                +  'data-value="' + escapeAttr(c.value) + '" '
                +  'role="button" tabindex="0" '
                +  'title="Remove filter">'
                +  '<span class="adf-chip__label">' + escapeHtml(c.label) + ':</span> '
                +  '<span class="adf-chip__value">' + escapeHtml(c.shown) + '</span>'
                +  '<span class="adf-chip__x" aria-hidden="true">&times;</span>'
                +  '</span>';
        });
        html += '<button type="button" id="adf-clear-filters" class="adf-clear-all">'
             +  'Clear All</button>';

        $bar.html(html).addClass('is-visible');
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ---------------------------------------------------------------- *
     * Chip click → remove that filter and re-run
     * ---------------------------------------------------------------- */

    function removeFilter(key, value) {
        var pairs = splitQueryPairs(window.location.search);
        var kept = pairs.filter(function (p) {
            return !(p.key === key && p.value === value);
        });
        var qs = kept.map(function (p) {
            return encodeURIComponent(p.key) + '=' + encodeURIComponent(p.value);
        }).join('&');

        // Mirror the removal into the sidebar inputs so the next
        // collectFilters() doesn't repopulate the value from a stale
        // form field.
        clearInputValue(key, value);

        // Run the search with the explicit qs so URL + query are in sync.
        if (window.adforestAjaxSearchApi && window.adforestAjaxSearchApi.run) {
            window.adforestAjaxSearchApi.run(qs);
        }
    }

    function clearInputValue(key, value) {
        var $inputs = $(SIDEBAR_SEL + ' [name="' + cssEscape(key) + '"]');
        $inputs.each(function () {
            var $i = $(this);
            if ($i.is(':checkbox') || $i.is(':radio')) {
                if ($i.val() === value) { $i.prop('checked', false); }
            } else if ($i.is('select')) {
                if ($i.val() === value) { this.selectedIndex = 0; }
            } else {
                $i.val('');
            }
        });
    }

    /* ---------------------------------------------------------------- *
     * Sidebar visual feedback
     * ---------------------------------------------------------------- */

    function highlightActiveFilters() {
        // Strip prior markers, then re-add for whatever's now active.
        $(SIDEBAR_SEL + ' .adf-filter-active').removeClass('adf-filter-active');

        var pairs = splitQueryPairs(window.location.search);
        pairs.forEach(function (p) {
            var rootKey = p.key.replace(/\[\]$/, '').split('[')[0];
            if (SKIPPED_PARAMS[rootKey]) { return; }
            if (p.value === '' || p.value === '0') { return; }

            var $matches = $(SIDEBAR_SEL + ' [name="' + cssEscape(p.key) + '"]');
            if (!$matches.length) {
                // try array-style name
                $matches = $(SIDEBAR_SEL + ' [name="' + cssEscape(p.key) + '[]"]');
            }
            $matches.each(function () {
                var $i = $(this);
                if ($i.is(':checkbox') || $i.is(':radio')) {
                    if ($i.val() !== p.value) { return; }
                }
                $i.closest('.accordion-item, .form-field, .panel, .widget')
                  .addClass('adf-filter-active');
            });
        });
    }

    /* ---------------------------------------------------------------- *
     * Mobile drawer
     * ---------------------------------------------------------------- */

    function ensureDrawerControls() {
        if ($('#' + DRAWER_BTN).length || !$(SIDEBAR_SEL).length) { return; }

        // Insert a "Filters" button above the chips bar; visible only
        // at mobile widths via CSS.
        var $anchor = $(CHIPS_SEL);
        if (!$anchor.length) { $anchor = $(RESULTS_SEL); }
        if (!$anchor.length) { return; }

        $('<button type="button" id="' + DRAWER_BTN + '" class="adf-mobile-filters-btn" aria-expanded="false">'
            + '<i class="fas fa-sliders-h" aria-hidden="true"></i> '
            + 'Filters</button>'
        ).insertBefore($anchor);

        $('<div class="adf-filters-backdrop" aria-hidden="true"></div>').appendTo('body');
    }

    function openDrawer() {
        $('body').addClass('adf-mobile-filters-open');
        $('#' + DRAWER_BTN).attr('aria-expanded', 'true');
    }
    function closeDrawer() {
        $('body').removeClass('adf-mobile-filters-open');
        $('#' + DRAWER_BTN).attr('aria-expanded', 'false');
    }
    function isDrawerOpen() {
        return $('body').hasClass('adf-mobile-filters-open');
    }

    /* ---------------------------------------------------------------- *
     * Loading-state alias
     * Mirror the existing `.adforest-ajax-loading` toggle to the spec's
     * `.loading` class so external CSS using either name works.
     * ---------------------------------------------------------------- */

    function syncLoadingClass() {
        var $r = $(RESULTS_SEL);
        if (!$r.length) { return; }
        $r.toggleClass('loading', $r.hasClass('adforest-ajax-loading'));
    }

    /* ---------------------------------------------------------------- *
     * Event wiring
     * ---------------------------------------------------------------- */

    function bind() {
        // Chip click → remove filter
        $(document).on('click', CHIPS_SEL + ' .adf-chip', function (e) {
            e.preventDefault();
            var $chip = $(this);
            removeFilter($chip.attr('data-key'), $chip.attr('data-value'));
        });
        // Keyboard: Enter / Space on focused chip
        $(document).on('keydown', CHIPS_SEL + ' .adf-chip', function (e) {
            if (e.which === 13 || e.which === 32) {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

        // Clear All → reuse the existing reset button's logic
        $(document).on('click', '#adf-clear-filters', function (e) {
            e.preventDefault();
            var $existingReset = $('[data-adforest-reset]').first();
            if ($existingReset.length) {
                $existingReset.trigger('click');
            } else if (window.adforestAjaxSearchApi && window.adforestAjaxSearchApi.run) {
                window.adforestAjaxSearchApi.run('');
            }
        });

        // Mobile drawer toggle / close
        $(document).on('click', '#' + DRAWER_BTN, function (e) {
            e.preventDefault();
            isDrawerOpen() ? closeDrawer() : openDrawer();
        });
        $(document).on('click', '.adf-filters-backdrop, .adf-mobile-filters-close', function (e) {
            e.preventDefault();
            closeDrawer();
        });
        // Esc closes the drawer
        $(document).on('keydown', function (e) {
            if (e.which === 27 && isDrawerOpen()) { closeDrawer(); }
        });
        // Auto-close if viewport widens past mobile breakpoint
        $(window).on('resize', function () {
            if (window.innerWidth >= 992 && isDrawerOpen()) { closeDrawer(); }
        });

        // Loading-class mirroring — observe class changes on results.
        if (window.MutationObserver) {
            var rEl = document.querySelector(RESULTS_SEL);
            if (rEl) {
                new MutationObserver(syncLoadingClass)
                    .observe(rEl, { attributes: true, attributeFilter: ['class'] });
            }
        }

        // Refresh after every AJAX render — chips, highlights, drawer btn.
        $(document).on('adforest:search:rendered adforest:search:dynamic-fields adforest:search:global-fields', function () {
            renderChips();
            highlightActiveFilters();
            ensureDrawerControls();
            syncLoadingClass();
        });

        // Browser back / forward — re-sync chips against the new URL.
        $(window).on('popstate', function () {
            renderChips();
            highlightActiveFilters();
        });
    }

    /* ---------------------------------------------------------------- *
     * Init
     * ---------------------------------------------------------------- */

    $(function () {
        if (!$(SIDEBAR_SEL).length || !$(RESULTS_SEL).length) { return; }
        ensureChipContainer();
        ensureDrawerControls();
        bind();
        renderChips();
        highlightActiveFilters();
    });

    // Public surface — themers can call these.
    window.adforestSearchUx = {
        renderChips:      renderChips,
        highlight:        highlightActiveFilters,
        openDrawer:       openDrawer,
        closeDrawer:      closeDrawer,
        removeFilter:     removeFilter
    };

})(jQuery);
