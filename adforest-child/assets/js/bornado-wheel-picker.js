(function (window, document) {
    'use strict';

    if (!window || !document) {
        return;
    }

    var GLOBAL_CONFIG = window.BornadoWheelPickerConfig || {};
    var GLOBAL_DEFAULTS = GLOBAL_CONFIG.defaults || {};
    var DEFAULT_COLUMNS = ['year', 'month', 'day'];
    var DEFAULT_MONTHS = [
        { value: '01', label: 'January', shortLabel: 'Jan' },
        { value: '02', label: 'February', shortLabel: 'Feb' },
        { value: '03', label: 'March', shortLabel: 'Mar' },
        { value: '04', label: 'April', shortLabel: 'Apr' },
        { value: '05', label: 'May', shortLabel: 'May' },
        { value: '06', label: 'June', shortLabel: 'Jun' },
        { value: '07', label: 'July', shortLabel: 'Jul' },
        { value: '08', label: 'August', shortLabel: 'Aug' },
        { value: '09', label: 'September', shortLabel: 'Sep' },
        { value: '10', label: 'October', shortLabel: 'Oct' },
        { value: '11', label: 'November', shortLabel: 'Nov' },
        { value: '12', label: 'December', shortLabel: 'Dec' }
    ];
    var DEFAULT_LABELS = {
        year: 'Year',
        month: 'Month',
        day: 'Day'
    };
    var DEFAULTS = {
        type: 'date',
        variant: 'date-modal',
        rtl: false,
        title: 'Select date',
        eyebrow: 'Wheel Picker',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        closeText: 'Close',
        showOutput: false,
        previewFormat: 'YYYY-MM-DD',
        outputFormat: 'YYYY-MM-DD',
        rowHeight: 48,
        visibleRows: 5,
        minYear: 1930,
        maxYear: (new Date()).getFullYear() + 10,
        columnOrder: DEFAULT_COLUMNS.slice(),
        labels: DEFAULT_LABELS,
        months: DEFAULT_MONTHS.slice()
    };
    var instances = [];

    function assign(target) {
        var i;
        var key;
        var source;

        target = target || {};
        for (i = 1; i < arguments.length; i++) {
            source = arguments[i] || {};
            for (key in source) {
                if (Object.prototype.hasOwnProperty.call(source, key)) {
                    target[key] = source[key];
                }
            }
        }

        return target;
    }

    function copyArray(list) {
        return Array.isArray(list) ? list.slice() : [];
    }

    function pad(value) {
        return String(value < 10 ? '0' + value : value);
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = String(value == null ? '' : value);
        return div.innerHTML;
    }

    function resolveElement(target) {
        if (!target) {
            return null;
        }
        if (target.nodeType === 1) {
            return target;
        }
        if (typeof target === 'string') {
            return document.querySelector(target);
        }
        return null;
    }

    function parseDataConfig(root) {
        if (!root) {
            return {};
        }

        var raw = root.getAttribute('data-bornado-wheel-picker-config');
        if (!raw) {
            return {};
        }

        try {
            return JSON.parse(raw) || {};
        } catch (error) {
            return {};
        }
    }

    function normalizeLabels(labels) {
        return assign({}, DEFAULT_LABELS, GLOBAL_DEFAULTS.labels || {}, labels || {});
    }

    function normalizeMonths(months) {
        var source = Array.isArray(months) && months.length ? months : (Array.isArray(GLOBAL_DEFAULTS.months) && GLOBAL_DEFAULTS.months.length ? GLOBAL_DEFAULTS.months : DEFAULT_MONTHS);
        var mapped = [];
        var i;
        var item;

        for (i = 0; i < source.length; i++) {
            item = source[i];
            if (typeof item === 'string') {
                mapped.push({
                    value: pad(i + 1),
                    label: item,
                    shortLabel: item
                });
                continue;
            }

            mapped.push({
                value: item && item.value ? String(item.value) : pad(i + 1),
                label: item && item.label ? String(item.label) : pad(i + 1),
                shortLabel: item && item.shortLabel ? String(item.shortLabel) : (item && item.label ? String(item.label) : pad(i + 1))
            });
        }

        return mapped;
    }

    function normalizeColumns(columns) {
        var candidate = Array.isArray(columns) && columns.length ? columns : (Array.isArray(GLOBAL_DEFAULTS.columnOrder) && GLOBAL_DEFAULTS.columnOrder.length ? GLOBAL_DEFAULTS.columnOrder : DEFAULT_COLUMNS);
        var normalized = [];
        var seen = {};
        var i;
        var key;

        for (i = 0; i < candidate.length; i++) {
            key = String(candidate[i] || '').toLowerCase();
            if ((key === 'year' || key === 'month' || key === 'day') && !seen[key]) {
                normalized.push(key);
                seen[key] = true;
            }
        }

        for (i = 0; i < DEFAULT_COLUMNS.length; i++) {
            key = DEFAULT_COLUMNS[i];
            if (!seen[key]) {
                normalized.push(key);
            }
        }

        return normalized;
    }

    function normalizeFormat(value) {
        return String(value || '')
            .replace(/yyyy/g, 'YYYY')
            .replace(/yy/g, 'YYYY')
            .replace(/dd/g, 'DD')
            .replace(/mm/g, 'MM');
    }

    function normalizeConfig(config) {
        var merged = assign({}, DEFAULTS, GLOBAL_DEFAULTS || {}, config || {});
        merged.labels = normalizeLabels(merged.labels);
        merged.months = normalizeMonths(merged.months);
        merged.columnOrder = normalizeColumns(merged.columnOrder);
        merged.visibleRows = parseInt(merged.visibleRows, 10) || DEFAULTS.visibleRows;
        merged.rowHeight = parseInt(merged.rowHeight, 10) || DEFAULTS.rowHeight;
        merged.minYear = parseInt(merged.minYear, 10) || DEFAULTS.minYear;
        merged.maxYear = parseInt(merged.maxYear, 10) || DEFAULTS.maxYear;
        if (merged.maxYear < merged.minYear) {
            merged.maxYear = merged.minYear;
        }
        merged.previewFormat = normalizeFormat(merged.previewFormat || DEFAULTS.previewFormat);
        merged.outputFormat = normalizeFormat(merged.outputFormat || DEFAULTS.outputFormat);
        merged.rtl = !!merged.rtl;
        merged.showOutput = !!merged.showOutput;
        return merged;
    }

    function daysInMonth(monthIndex, year) {
        return new Date(year, monthIndex + 1, 0).getDate();
    }

    function buildDays(monthIndex, year) {
        var count = daysInMonth(monthIndex, year);
        var items = [];
        var day;

        for (day = 1; day <= count; day++) {
            items.push(pad(day));
        }

        return items;
    }

    function buildYears(minYear, maxYear) {
        var years = [];
        var year;

        for (year = minYear; year <= maxYear; year++) {
            years.push(String(year));
        }

        return years;
    }

    function parseDateValue(value, config) {
        var today = new Date();
        var baseYear = clamp(today.getFullYear(), config.minYear, config.maxYear);
        var result = {
            year: baseYear,
            month: today.getMonth(),
            day: today.getDate()
        };
        var raw = value;
        var matches;
        var monthIndex;
        var date;

        if (raw && typeof raw === 'object' && Object.prototype.toString.call(raw) === '[object Date]' && !isNaN(raw.getTime())) {
            result.year = clamp(raw.getFullYear(), config.minYear, config.maxYear);
            result.month = raw.getMonth();
            result.day = raw.getDate();
            return result;
        }

        raw = String(raw || '').trim();
        if (!raw) {
            return result;
        }

        matches = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s].*)?$/);
        if (matches) {
            result.year = clamp(parseInt(matches[1], 10), config.minYear, config.maxYear);
            result.month = clamp(parseInt(matches[2], 10) - 1, 0, 11);
            result.day = clamp(parseInt(matches[3], 10), 1, 31);
            return result;
        }

        matches = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (matches) {
            result.month = clamp(parseInt(matches[1], 10) - 1, 0, 11);
            result.day = clamp(parseInt(matches[2], 10), 1, 31);
            result.year = clamp(parseInt(matches[3], 10), config.minYear, config.maxYear);
            return result;
        }

        for (monthIndex = 0; monthIndex < config.months.length; monthIndex++) {
            if (raw.indexOf(config.months[monthIndex].label) === 0 || raw.indexOf(config.months[monthIndex].shortLabel) === 0) {
                matches = raw.match(/(\d{1,2}).*?(\d{4})$/);
                if (matches) {
                    result.month = monthIndex;
                    result.day = clamp(parseInt(matches[1], 10), 1, 31);
                    result.year = clamp(parseInt(matches[2], 10), config.minYear, config.maxYear);
                    return result;
                }
            }
        }

        date = new Date(raw);
        if (!isNaN(date.getTime())) {
            result.year = clamp(date.getFullYear(), config.minYear, config.maxYear);
            result.month = date.getMonth();
            result.day = date.getDate();
        }

        return result;
    }

    function sanitizeState(state, config) {
        var safe = assign({}, state || {});
        safe.year = clamp(parseInt(safe.year, 10) || config.minYear, config.minYear, config.maxYear);
        safe.month = clamp(parseInt(safe.month, 10) || 0, 0, 11);
        safe.day = clamp(parseInt(safe.day, 10) || 1, 1, daysInMonth(safe.month, safe.year));
        return safe;
    }

    function formatDate(state, format, config) {
        var safe = sanitizeState(state, config);
        var replacements = {
            YYYY: String(safe.year),
            MM: pad(safe.month + 1),
            DD: pad(safe.day),
            MMMM: config.months[safe.month] ? config.months[safe.month].label : pad(safe.month + 1),
            MMM: config.months[safe.month] ? config.months[safe.month].shortLabel : pad(safe.month + 1)
        };
        var output = String(format || 'YYYY-MM-DD');

        output = output.replace(/MMMM/g, replacements.MMMM);
        output = output.replace(/MMM/g, replacements.MMM);
        output = output.replace(/YYYY/g, replacements.YYYY);
        output = output.replace(/MM/g, replacements.MM);
        output = output.replace(/DD/g, replacements.DD);
        return output;
    }

    function getStateIndexForKey(instance, key) {
        if (key === 'month') {
            return instance.state.month;
        }
        if (key === 'day') {
            return instance.state.day - 1;
        }
        if (key === 'year') {
            return instance.state.year - instance.config.minYear;
        }
        return 0;
    }

    function updatePreview(instance) {
        if (instance.previewEl) {
            instance.previewEl.textContent = formatDate(instance.state, instance.config.previewFormat, instance.config);
        }
        if (instance.outputEl) {
            instance.outputEl.value = formatDate(instance.state, instance.config.outputFormat, instance.config);
        }
    }

    function updateColumnActiveClasses(column, activeIndex) {
        var items = column && column.track ? column.track.querySelectorAll('[data-wheel-item]') : [];
        var i;

        for (i = 0; i < items.length; i++) {
            items[i].classList.toggle('is-active', i === activeIndex);
            if (i === activeIndex) {
                items[i].setAttribute('aria-selected', 'true');
            } else {
                items[i].setAttribute('aria-selected', 'false');
            }
        }
    }

    function setColumnItems(instance, key, items) {
        var column = instance.columns[key];
        var html = [];
        var i;

        if (!column || !column.list) {
            return;
        }

        column.items = copyArray(items);
        for (i = 0; i < column.items.length; i++) {
            html.push(
                '<button type="button" class="bornado-wheel-picker__item" data-wheel-item data-index="' + i + '" role="option" aria-selected="false">' +
                    escapeHtml(column.items[i]) +
                '</button>'
            );
        }
        column.list.innerHTML = html.join('');
        updateColumnActiveClasses(column, 0);
    }

    function onColumnChanged(instance, key, activeIndex) {
        if (key === 'month') {
            instance.state.month = clamp(activeIndex, 0, 11);
            rebuildDayColumn(instance);
            return;
        }

        if (key === 'day') {
            instance.state.day = clamp(activeIndex + 1, 1, daysInMonth(instance.state.month, instance.state.year));
            updatePreview(instance);
            return;
        }

        if (key === 'year') {
            instance.state.year = clamp(instance.config.minYear + activeIndex, instance.config.minYear, instance.config.maxYear);
            rebuildDayColumn(instance);
        }
    }

    function createWheel(instance, key) {
        var column = instance.columns[key];
        var track = column && column.track ? column.track : null;
        var state = {
            activeIndex: getStateIndexForKey(instance, key),
            scrollTimer: 0
        };

        if (!track) {
            return null;
        }

        function maxIndex() {
            return Math.max(0, (column.items || []).length - 1);
        }

        function normalizeIndex(index) {
            return clamp(parseInt(index, 10) || 0, 0, maxIndex());
        }

        function setActive(index, emit) {
            var normalized = normalizeIndex(index);
            if (normalized !== state.activeIndex) {
                state.activeIndex = normalized;
                updateColumnActiveClasses(column, normalized);
                if (emit !== false) {
                    onColumnChanged(instance, key, normalized);
                }
            } else {
                updateColumnActiveClasses(column, normalized);
            }
        }

        function currentIndexFromScroll() {
            return normalizeIndex(Math.round(track.scrollTop / instance.config.rowHeight));
        }

        function snapToCurrent(animate) {
            var index = currentIndexFromScroll();
            setActive(index, true);
            track.scrollTo({
                top: index * instance.config.rowHeight,
                behavior: animate === false ? 'auto' : 'smooth'
            });
        }

        function onScroll() {
            setActive(currentIndexFromScroll(), true);
            window.clearTimeout(state.scrollTimer);
            state.scrollTimer = window.setTimeout(function () {
                snapToCurrent(true);
            }, 80);
        }

        function onClick(event) {
            var item = event.target.closest('[data-wheel-item]');
            var index;

            if (!item || !track.contains(item)) {
                return;
            }

            event.preventDefault();
            index = normalizeIndex(item.getAttribute('data-index'));
            api.slideTo(index, true, true);
        }

        track.addEventListener('scroll', onScroll, { passive: true });
        track.addEventListener('click', onClick);

        var api = {
            update: function () {
                state.activeIndex = normalizeIndex(state.activeIndex);
                updateColumnActiveClasses(column, state.activeIndex);
            },
            slideTo: function (index, animate, emit) {
                var normalized = normalizeIndex(index);
                state.activeIndex = normalized;
                updateColumnActiveClasses(column, normalized);
                if (emit !== false) {
                    onColumnChanged(instance, key, normalized);
                }
                track.scrollTo({
                    top: normalized * instance.config.rowHeight,
                    behavior: animate === false ? 'auto' : 'smooth'
                });
            },
            destroy: function () {
                window.clearTimeout(state.scrollTimer);
                track.removeEventListener('scroll', onScroll);
                track.removeEventListener('click', onClick);
            }
        };

        Object.defineProperty(api, 'activeIndex', {
            get: function () {
                return state.activeIndex;
            }
        });

        return api;
    }

    function buildMarkup(instance) {
        var labelsHtml = [];
        var columnsHtml = [];
        var i;
        var key;
        var labelText;

        for (i = 0; i < instance.config.columnOrder.length; i++) {
            key = instance.config.columnOrder[i];
            labelText = instance.config.labels[key] || key;
            labelsHtml.push('<span class="bornado-wheel-picker__label" data-wheel-label="' + escapeHtml(key) + '">' + escapeHtml(labelText) + '</span>');
            columnsHtml.push(
                '<div class="bornado-wheel-picker__column" data-wheel-column="' + escapeHtml(key) + '">' +
                    '<div class="bornado-wheel-picker__track" data-wheel-track="' + escapeHtml(key) + '" role="listbox" aria-label="' + escapeHtml(labelText) + '">' +
                        '<div class="bornado-wheel-picker__list" data-wheel-list="' + escapeHtml(key) + '"></div>' +
                    '</div>' +
                '</div>'
            );
        }

        return '' +
            '<div class="bornado-wheel-picker__dialog">' +
                '<button type="button" class="bornado-wheel-picker__backdrop" data-wheel-action="close" aria-label="' + escapeHtml(instance.config.closeText) + '"></button>' +
                '<div class="bornado-wheel-picker__panel" role="dialog" aria-modal="true" aria-label="' + escapeHtml(instance.config.title) + '">' +
                    '<div class="bornado-wheel-picker__handle"></div>' +
                    '<div class="bornado-wheel-picker__header">' +
                        '<div class="bornado-wheel-picker__header-copy">' +
                            '<span class="bornado-wheel-picker__eyebrow">' + escapeHtml(instance.config.eyebrow) + '</span>' +
                            '<h3 class="bornado-wheel-picker__title">' + escapeHtml(instance.config.title) + '</h3>' +
                        '</div>' +
                        '<div class="bornado-wheel-picker__preview" data-wheel-preview></div>' +
                    '</div>' +
                    '<div class="bornado-wheel-picker__labels">' + labelsHtml.join('') + '</div>' +
                    '<div class="bornado-wheel-picker__drum">' +
                        '<div class="bornado-wheel-picker__highlight"></div>' +
                        '<div class="bornado-wheel-picker__fade"></div>' +
                        '<div class="bornado-wheel-picker__columns">' + columnsHtml.join('') + '</div>' +
                    '</div>' +
                    (instance.config.showOutput
                        ? '<div class="bornado-wheel-picker__output-wrap"><input type="text" class="bornado-wheel-picker__output" data-wheel-output readonly></div>'
                        : '') +
                    '<div class="bornado-wheel-picker__actions">' +
                        '<button type="button" class="bornado-wheel-picker__button bornado-wheel-picker__button--ghost" data-wheel-action="cancel">' + escapeHtml(instance.config.cancelText) + '</button>' +
                        '<button type="button" class="bornado-wheel-picker__button bornado-wheel-picker__button--primary" data-wheel-action="confirm">' + escapeHtml(instance.config.confirmText) + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function render(instance) {
        var i;
        var key;
        var column;

        instance.root.classList.remove('bornado-wheel-picker--date-modal', 'bornado-wheel-picker--date-inline', 'bornado-wheel-picker--filter-wheel', 'bornado-wheel-picker--compact-wheel');
        instance.root.classList.add('bornado-wheel-picker--' + instance.config.variant);
        instance.root.classList.toggle('is-rtl', !!instance.config.rtl);
        instance.root.style.setProperty('--bornado-wheel-picker-row-height', instance.config.rowHeight + 'px');
        instance.root.style.setProperty('--bornado-wheel-picker-visible-rows', String(instance.config.visibleRows));
        instance.root.style.setProperty('--bornado-wheel-picker-drum-height', (instance.config.rowHeight * instance.config.visibleRows) + 'px');
        instance.root.style.setProperty('--bornado-wheel-picker-offset', (((instance.config.visibleRows - 1) / 2) * instance.config.rowHeight) + 'px');
        instance.root.style.setProperty('--bornado-wheel-picker-column-count', String(instance.config.columnOrder.length));
        instance.root.innerHTML = buildMarkup(instance);

        instance.previewEl = instance.root.querySelector('[data-wheel-preview]');
        instance.outputEl = instance.root.querySelector('[data-wheel-output]');
        instance.columns = {};
        instance.wheels = {};

        for (i = 0; i < instance.config.columnOrder.length; i++) {
            key = instance.config.columnOrder[i];
            column = instance.root.querySelector('[data-wheel-column="' + key + '"]');
            instance.columns[key] = {
                root: column,
                track: column ? column.querySelector('[data-wheel-track="' + key + '"]') : null,
                list: column ? column.querySelector('[data-wheel-list="' + key + '"]') : null,
                items: []
            };
        }

        setColumnItems(instance, 'month', instance.config.months.map(function (item) {
            return item.shortLabel || item.label || item.value;
        }));
        setColumnItems(instance, 'year', buildYears(instance.config.minYear, instance.config.maxYear));
        setColumnItems(instance, 'day', buildDays(instance.state.month, instance.state.year));

        for (i = 0; i < instance.config.columnOrder.length; i++) {
            key = instance.config.columnOrder[i];
            instance.wheels[key] = createWheel(instance, key);
        }

        instance.root.addEventListener('click', instance.onClick);
        updatePreview(instance);
    }

    function rebuildDayColumn(instance) {
        var dayItems = buildDays(instance.state.month, instance.state.year);

        if (instance.state.day > dayItems.length) {
            instance.state.day = dayItems.length;
        }

        setColumnItems(instance, 'day', dayItems);

        if (instance.wheels.day) {
            instance.wheels.day.update();
            instance.wheels.day.slideTo(getStateIndexForKey(instance, 'day'), false, false);
        }

        updatePreview(instance);
    }

    function syncToState(instance) {
        var i;
        var key;

        rebuildDayColumn(instance);
        for (i = 0; i < instance.config.columnOrder.length; i++) {
            key = instance.config.columnOrder[i];
            if (instance.wheels[key]) {
                instance.wheels[key].update();
                instance.wheels[key].slideTo(getStateIndexForKey(instance, key), false, false);
            }
        }
        updatePreview(instance);
    }

    function anyPickerOpen() {
        var i;
        for (i = 0; i < instances.length; i++) {
            if (instances[i] && instances[i].root && !instances[i].root.hidden) {
                return true;
            }
        }
        return false;
    }

    function syncBodyOpenState() {
        document.body.classList.toggle('bornado-wheel-picker-open', anyPickerOpen());
    }

    function closeAllExcept(current) {
        var i;
        for (i = 0; i < instances.length; i++) {
            if (instances[i] !== current) {
                closeInstance(instances[i], { restoreFocus: false });
            }
        }
    }

    function dispatchInputEvents(inputEl) {
        if (!inputEl) {
            return;
        }
        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
        inputEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function confirmInstance(instance) {
        var formattedValue = formatDate(instance.state, instance.config.outputFormat, instance.config);
        var meta = {
            formattedValue: formattedValue,
            state: assign({}, instance.state),
            sourceInput: instance.sourceInput || null
        };

        if (instance.sourceInput) {
            instance.sourceInput.value = formattedValue;
            dispatchInputEvents(instance.sourceInput);
        }

        if (typeof instance.onConfirm === 'function') {
            instance.onConfirm(formattedValue, meta);
        }

        closeInstance(instance);
    }

    function closeInstance(instance, options) {
        options = options || {};
        if (!instance || !instance.root || instance.root.hidden) {
            return;
        }

        instance.root.hidden = true;
        instance.root.classList.remove('is-open');

        if (options.restoreFocus !== false && instance.restoreFocusEl && typeof instance.restoreFocusEl.focus === 'function') {
            instance.restoreFocusEl.focus();
        }

        instance.onConfirm = null;
        instance.sourceInput = null;
        instance.restoreFocusEl = null;
        syncBodyOpenState();
    }

    function openInstance(instance, options) {
        var initialState;
        var title;
        var panel;

        if (!instance || !instance.root) {
            return null;
        }

        options = options || {};
        closeAllExcept(instance);

        title = options.title ? String(options.title) : instance.config.title;
        panel = instance.root.querySelector('.bornado-wheel-picker__panel');
        if (instance.titleEl) {
            instance.titleEl.textContent = title;
        }
        if (panel) {
            panel.setAttribute('aria-label', title);
        }

        instance.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        instance.sourceInput = options.sourceInput || null;
        instance.restoreFocusEl = options.restoreFocus || options.sourceInput || document.activeElement || null;

        initialState = parseDateValue(
            options.initialValue != null
                ? options.initialValue
                : (instance.sourceInput ? instance.sourceInput.value : null),
            instance.config
        );

        instance.state = sanitizeState(initialState, instance.config);
        instance.root.hidden = false;
        instance.root.classList.add('is-open');
        syncToState(instance);
        syncBodyOpenState();

        window.requestAnimationFrame(function () {
            syncToState(instance);
        });

        window.setTimeout(function () {
            syncToState(instance);
        }, 100);

        return instance.api;
    }

    function updateInstance(instance, patch) {
        if (!instance || !instance.root) {
            return null;
        }

        instance.root.removeEventListener('click', instance.onClick);
        instance.config = normalizeConfig(assign({}, instance.config, patch || {}));
        instance.state = sanitizeState(instance.state, instance.config);
        render(instance);
        instance.titleEl = instance.root.querySelector('.bornado-wheel-picker__title');
        return instance.api;
    }

    function destroyInstance(instance) {
        var i;
        var key;

        if (!instance) {
            return;
        }

        closeInstance(instance, { restoreFocus: false });

        if (instance.root) {
            instance.root.removeEventListener('click', instance.onClick);
            instance.root.innerHTML = '';
            delete instance.root.__bornadoWheelPickerInstance;
        }

        for (key in instance.wheels) {
            if (Object.prototype.hasOwnProperty.call(instance.wheels, key) && instance.wheels[key] && typeof instance.wheels[key].destroy === 'function') {
                instance.wheels[key].destroy();
            }
        }

        for (i = instances.length - 1; i >= 0; i--) {
            if (instances[i] === instance) {
                instances.splice(i, 1);
            }
        }

        syncBodyOpenState();
    }

    function createInstance(root, config) {
        var mergedConfig = normalizeConfig(assign({}, parseDataConfig(root), config || {}));
        var instance = {
            root: root,
            config: mergedConfig,
            state: sanitizeState(parseDateValue(root.getAttribute('data-wheel-initial-value') || '', mergedConfig), mergedConfig),
            sourceInput: null,
            restoreFocusEl: null,
            onConfirm: null,
            columns: {},
            wheels: {},
            previewEl: null,
            outputEl: null,
            titleEl: null,
            onClick: null,
            api: null
        };

        instance.onClick = function (event) {
            var actionEl = event.target.closest('[data-wheel-action]');
            var action = actionEl ? actionEl.getAttribute('data-wheel-action') : '';

            if (action === 'close' || action === 'cancel') {
                event.preventDefault();
                closeInstance(instance);
                return;
            }

            if (action === 'confirm') {
                event.preventDefault();
                confirmInstance(instance);
            }
        };

        instance.api = {
            element: root,
            open: function (options) {
                return openInstance(instance, options);
            },
            close: function () {
                closeInstance(instance);
            },
            getValue: function (format) {
                return formatDate(instance.state, normalizeFormat(format || instance.config.outputFormat), instance.config);
            },
            setValue: function (value) {
                instance.state = sanitizeState(parseDateValue(value, instance.config), instance.config);
                syncToState(instance);
                return instance.api;
            },
            update: function (patch) {
                return updateInstance(instance, patch);
            },
            destroy: function () {
                destroyInstance(instance);
            }
        };

        render(instance);
        instance.titleEl = instance.root.querySelector('.bornado-wheel-picker__title');
        return instance;
    }

    function init(target, config) {
        var root = resolveElement(target);
        var instance;

        if (!root) {
            return null;
        }

        if (root.__bornadoWheelPickerInstance) {
            instance = root.__bornadoWheelPickerInstance;
            if (config) {
                updateInstance(instance, config);
            }
            return instance.api;
        }

        instance = createInstance(root, config || {});
        root.__bornadoWheelPickerInstance = instance;
        instances.push(instance);
        return instance.api;
    }

    function open(target, options) {
        var api = init(target, options && options.config ? options.config : null);
        return api ? api.open(options || {}) : null;
    }

    function close(target) {
        var root = resolveElement(target);
        var instance = root && root.__bornadoWheelPickerInstance ? root.__bornadoWheelPickerInstance : null;
        if (instance) {
            closeInstance(instance);
        }
    }

    function initAll() {
        var roots = document.querySelectorAll('[data-bornado-wheel-picker-config]');
        var i;

        for (i = 0; i < roots.length; i++) {
            init(roots[i]);
        }
    }

    document.addEventListener('keydown', function (event) {
        var i;
        if (event.key !== 'Escape') {
            return;
        }

        for (i = instances.length - 1; i >= 0; i--) {
            if (instances[i] && instances[i].root && !instances[i].root.hidden) {
                closeInstance(instances[i]);
                return;
            }
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.BornadoWheelPicker = {
        init: init,
        open: open,
        close: close
    };
})(window, document);
