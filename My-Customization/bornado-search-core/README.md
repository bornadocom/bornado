# Bornado Search Core

Shared search logic for custom Bornado/AdForest modules.

## Rule

New modules must not build AdForest search URLs on their own.

Use the shared API instead:

- `bornado_search_get_actions()`
- `bornado_search_get_selected_context()`
- `bornado_search_build_clean_query_args()`
- `window.BornadoSearchCore`

## Scope

Keep only shared search logic here:

- search page action resolution
- contextual all-cities / all-categories / all-filters URLs
- query cleanup
- common form-to-URL JavaScript helpers

Keep UI concerns in each module:

- markup
- CSS classes
- component-specific interactions
