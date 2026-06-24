# Location Taxonomy Policy and Data Entry Rules

---

## 1. Purpose of This Document

This document defines the **philosophy of the location model** used in the project and the **data that must be entered** for countries and cities.

It is intentionally limited to:

* the structure of the location taxonomy
* the required input data for country terms
* the required input data for city terms
* the editorial rules for creating and maintaining location data

This document does **not** define runtime site behavior, search behavior, SEO implementation details, routing logic, or indexation rules.

---

## 2. Core Model Philosophy

The project uses a **strict two-level geographic model**:

* Level 1: Country
* Level 2: City

Cities are always children of countries.

No third public geographic level is part of this model. In other words, we do **not** model public location data around state, province, region, county, or similar administrative layers.

This is a deliberate product decision. The goal is not to represent geography with full political precision, but to keep the location system:

* simple
* controlled
* consistent
* easy to maintain
* easy for users and editors to understand

---

## 3. Conceptual Public Location Shape

At the conceptual level, the public location model is based on these shapes:

```text
/{country-slug}
/{country-slug}/{city-slug}
```

Examples:

```text
/uk
/uk/london
/uk/liverpool

/us
/us/new-york
/us/los-angeles
```

This section describes the **location model only**. It should not be read as a complete definition of every public URL pattern used elsewhere in the project.

---

## 4. Country Entity

Each country is a managed top-level taxonomy term with structured metadata.

### Required Input Fields

| Field | Description | Example |
| --- | --- | --- |
| name (FA) | Main Persian display name of the country | بریتانیا |
| slug | Stable English slug used as the country identifier | uk |
| country_code | ISO 3166-1 Alpha-2 code | GB |
| phone_dial_code | International calling code | +44 |
| currency | Currency assignment for that market | GBP |

### Notes

* `name (FA)` is the primary display label for Persian users.
* `slug` must be short, stable, lowercase, and English.
* `slug` is an editorial URL identifier. It is not an ISO field and does not need to match `country_code`.
* `country_code` must use a valid ISO alpha-2 country code.
* `phone_dial_code` must use the international format, such as `+44`, `+1`, or `+98`.
* `currency` should be assigned as a controlled site currency value, not as free-form text.

### Example Country Record

```json
{
  "name": "بریتانیا",
  "slug": "uk",
  "country_code": "GB",
  "phone_dial_code": "+44",
  "currency": "GBP"
}
```

### Optional Project Metadata

Depending on project needs, a country may also store managed metadata such as:

* English display name
* market status
* internal currency reference

These are project-managed fields and do not change the core two-level model.

---

## 5. City Entity

Each city is a child term under exactly one country.

A city cannot exist as a standalone root location.

### Required Input Fields

| Field | Description | Example |
| --- | --- | --- |
| name (FA) | Main Persian display name of the city | لیورپول |
| slug | Stable English slug for the city | liverpool |
| parent_country | The country term this city belongs to | uk |

### Notes

* A city is created only under an existing country.
* City data should remain lightweight.
* City records should not be expanded into separate public layers such as district, county, or region.
* If additional internal metadata is ever needed, it must not change the core country -> city model.

### Example City Record

```json
{
  "name": "لیورپول",
  "slug": "liverpool",
  "parent_country": "uk"
}
```

---

## 6. Data Entry Rules

When entering or maintaining location data, the following rules apply:

* A country must be created before any city can be entered under it.
* Every city must belong to exactly one country.
* No orphan city terms are allowed.
* Country slugs must be unique and stable.
* City slugs should be chosen carefully to avoid ambiguity or editorial conflicts.
* Editors must not invent unofficial geographic structures for the base taxonomy.
* Countries and cities must come from trusted real-world geographic references.
* Free-form user-generated locations must not become part of the core taxonomy.

---

## 7. Naming and Slug Rules

To keep the taxonomy consistent:

* Persian names are used for display.
* English slugs are used for system identity.
* Country slugs should be short and recognizable, such as `uk`, `us`, `de`, or `ca`.
* City slugs should be lowercase English transliterations where applicable.
* Slugs should be stable over time and should not be changed casually.
* Administrative suffixes and unnecessary noise should be avoided unless they are required for clarity.

---

## 8. Out of Scope

This document does not define:

* SEO policy
* canonical rules
* index / noindex behavior
* query parameter behavior
* archive behavior
* search behavior
* landing-page behavior
* implementation details of routing or rendering

Those concerns must be documented separately.

---

## 9. One-Line Summary (for AI models)

> A controlled two-level location taxonomy where countries are top-level managed entities with Persian display names and structured market metadata, cities are always child terms of countries, and location data entry is limited to a simple, stable country -> city model.

