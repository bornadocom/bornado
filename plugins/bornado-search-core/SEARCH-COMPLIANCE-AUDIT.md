# Search Core Compliance Audit

## Compliant Or Patched
- `My-Customization/bornado-search-core/assets/js/bornado-search-core.js`
  - Removes empty values from form/query params.
  - Sanitizes current `window.location.search` before reuse.
  - Intercepts native GET search forms that were still bypassing Search Core.
- `adforest-child/adforest-child/bornado-search-compat.php`
  - Overrides `adforest_search_params()` without editing the parent theme.
  - Rebuilds hidden query inputs from sanitized query args only.
- `My-Customization/adforest-header-search-4-clone/templates/header-search-4-clone.php`
  - Stops re-rendering empty hidden params from raw `$_GET`.
  - Stops copying polluted `window.location.search` when changing category.
- `My-Customization/my-custom-elementor-widgets/includes/adforest-search-by-location-v2.php`
  - Uses sanitized hidden query rendering through Search Core helpers.

## Remaining Theme-Level Bypasses To Watch
- `adforest/adforest/template-parts/headers/header-4.php`
  - Native theme search form still exists outside custom modules.
  - Empty query handling is now improved by the global Search Core submit sanitizer, but the template itself is still a theme-owned entry point.
- `adforest/adforest/template-parts/layouts/search/search-sidebar.php`
- `adforest/adforest/template-parts/layouts/search/search-topbar.php`
- `adforest/adforest/template-parts/layouts/search/search-map.php`
- `adforest/adforest/template-parts/layouts/search/location/search-sidebar.php`
- `adforest/adforest/template-parts/layouts/search/category/search-sidebar.php`
  - These theme layouts still rebuild URLs and state in multiple places.
  - They benefit from the `adforest_search_params()` child override, but remain theme-level code paths that should be watched after updates.

## Main Root Causes Found
1. Raw replay of `QUERY_STRING` into hidden inputs.
2. Raw replay of `$_GET` into hidden inputs.
3. Reuse of `window.location.search` without removing empty params first.
4. Native GET form submits that bypassed `BornadoSearchCore`.

## Regression Test Matrix
- Submit with empty keyword:
  - No `?ad_title=` in the final URL.
- Submit with empty city/category:
  - No `country_id=` or `cat_id=` in the final URL.
- Change category in header clone from a polluted URL:
  - Existing empty params must be dropped before redirect.
- Location widget v2:
  - Inherited hidden query fields must not carry empty values.
- Native theme search forms:
  - Empty fields must be removed even when the theme submits with plain GET.
