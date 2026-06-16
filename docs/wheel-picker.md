# Wheel Picker

## Purpose
This document is the source of truth for the reusable Bornado wheel picker.

It explains:

- where the wheel picker lives
- how to render it from PHP
- how to open or control it from JavaScript
- which configuration options are expected
- how to create different UI variants without duplicating the component

## Files
- `adforest-child/bornado-wheel-picker.php`: WordPress bootstrap, asset registration, PHP render helper
- `adforest-child/assets/js/bornado-wheel-picker.js`: dependency-free front-end runtime
- `adforest-child/assets/css/bornado-wheel-picker.css`: core styles, theme styles, and variant styles

## Architecture
The picker is implemented as a reusable child-theme module.

The public APIs are:

- PHP: `bornado_render_wheel_picker($args)`
- PHP: `bornado_wheel_picker_enqueue_assets()`
- JS: `window.BornadoWheelPicker.init(rootEl, config)`
- JS: `window.BornadoWheelPicker.open(rootEl, options)`
- JS: `window.BornadoWheelPicker.close(rootEl)`

The current implementation is dependency-free and does not rely on CDN assets.

## Default Usage From PHP
Use PHP rendering when the page template or feature owns the picker root markup.

```php
echo bornado_render_wheel_picker(
    array(
        'id' => 'bornado-inline-date-wheel-picker',
        'class_name' => 'bornado-inline-date-wheel-picker',
        'type' => 'date',
        'variant' => 'date-modal',
        'hidden' => true,
        'title' => __('انتخاب تاریخ', 'adforest'),
        'confirm_text' => __('تایید تاریخ', 'adforest'),
        'cancel_text' => __('انصراف', 'adforest'),
        'preview_format' => 'YYYY-MM-DD',
        'output_format' => 'YYYY-MM-DD',
        'column_order' => array('year', 'month', 'day'),
    )
);
```

## Default Usage From JavaScript
Use JS control when a feature wants to open the picker for a specific field on demand.

```js
var root = document.getElementById('bornado-inline-date-wheel-picker');

window.BornadoWheelPicker.open(root, {
  sourceInput: inputEl,
  restoreFocus: inputEl,
  title: 'انتخاب تاریخ',
  initialValue: inputEl.value
});
```

## PHP Arguments
The `bornado_render_wheel_picker($args)` helper currently supports these keys:

- `id`: unique DOM id for the root element
- `class_name`: extra CSS classes on the root
- `type`: currently `date`
- `variant`: visual mode such as `date-modal`, `date-inline`, `filter-wheel`, `compact-wheel`
- `hidden`: whether the root starts hidden
- `rtl`: whether the picker runs in RTL mode
- `title`: visible dialog title
- `eyebrow`: small title above the main heading
- `confirm_text`: confirm button label
- `cancel_text`: cancel button label
- `show_output`: whether the read-only output field is visible inside the picker
- `preview_format`: visible preview format
- `output_format`: confirmed output format written back to the field
- `min_year`: lower year bound
- `max_year`: upper year bound
- `column_order`: current supported columns are `year`, `month`, `day`
- `labels`: per-column label overrides
- `months`: per-month label overrides

## JavaScript API
### `init(rootEl, config)`
Initializes one picker root and returns a controller object.

### `open(rootEl, options)`
Opens a picker and optionally binds it to an input.

Supported `open()` options:

- `sourceInput`: input element that receives the confirmed value
- `restoreFocus`: element to focus after close
- `title`: runtime title override
- `initialValue`: initial date value to open with
- `onConfirm`: callback receiving `(formattedValue, meta)`
- `config`: optional one-off config patch before opening

### Controller Methods
`init()` returns a controller with:

- `open(options)`
- `close()`
- `getValue(format)`
- `setValue(value)`
- `update(configPatch)`
- `destroy()`

## Supported Formats
The current date parser and formatter safely support:

- `YYYY-MM-DD`
- `MM/DD/YYYY`
- month-name based values such as `January 09, 2026`

For Bornado form integration, prefer `YYYY-MM-DD` unless a specific field contract requires something else.

## Variants
Use `variant` for appearance and placement differences, not for logic duplication.

Current variants:

- `date-modal`: bottom sheet / modal picker
- `date-inline`: embedded inline picker
- `filter-wheel`: same structure with filter-oriented accent styling
- `compact-wheel`: smaller dialog width and tighter spacing

When a new feature needs a different look, prefer extending CSS under a new variant class instead of cloning the component.

## Styling Contract
The root is namespaced under `.bornado-wheel-picker`.

Recommended layering:

- core styles: structure, row height, highlight band, scroll mechanics
- theme styles: colors, radii, spacing, typography
- variant styles: per-feature visual differences

When styling a new consumer:

1. keep shared structure in `bornado-wheel-picker.css`
2. add a new variant class if the layout differs
3. avoid feature-specific inline CSS when a reusable variant can cover it

## Current Consumer
The first real consumer is inline ad edit:

- `adforest-child/bornado-inline-ad-edit.php`
- `adforest-child/assets/js/bornado-inline-ad-edit.js`

Current behavior:

- mobile inline-edit date inputs with `.dynamic-form-date-fields` open the wheel picker
- confirmed values are written back as `YYYY-MM-DD`
- desktop and non-inline contexts continue using existing project behavior

## Adding A New Consumer
For a new page or feature:

1. render one picker root with `bornado_render_wheel_picker($args)` or ensure one already exists on the page
2. open it from JS when the user interacts with the target field
3. choose the right `variant`
4. keep output format aligned with the backend field contract
5. only add new CSS when the existing variants are not enough

## Extension Guidance
If a future feature needs a different wheel type, extend the existing module instead of creating a second picker system.

Preferred direction:

- keep one public API
- add new config keys only when reusable across more than one consumer
- add new variants for appearance
- add new picker types only when there is a real second use case

## Maintenance Notes
- assets are versioned with `filemtime()`
- the picker is intentionally self-contained and CDN-free
- inline-edit integration should remain a consumer of the picker, not the owner of picker logic
