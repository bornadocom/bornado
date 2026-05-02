# مدل داده Migration-Friendly برای Bornado

## هدف این فایل
این فایل مشخص می کند که Bornado از همین حالا چگونه باید داده های country, city, category, listing و landing را model کند تا مهاجرت آینده به سیستم اختصاصی:

- کم ریسک تر باشد
- به slugها و taxonomyهای وردپرس قفل نشود
- و نیاز به rewrite پرهزینه داده بعد از رشد نداشته باشد

این سند درباره payment یا billing نیست؛ تمرکز آن روی market architecture و portability داده است.

## اصل کلیدی
در WordPress فعلی، چند مفهوم مختلف روی هم افتاده اند:

- country
- city
- location tree
- route segment
- taxonomy term

برای migration حرفه ای، این ها باید از هم جدا شوند.

به بیان ساده:

- URL نباید منبع حقیقت باشد
- taxonomy term نباید مدل نهایی کسب و کار باشد
- و route logic نباید تنها راه فهمیدن market context باشد

## 7 اصل طراحی

### اصل 1: URL یک projection است، نه source of truth
مثلا `/uk/london/property/` باید از data model ساخته شود، نه اینکه data model از روی path reverse-engineer شود.

### اصل 2: country و city باید entity مستقل باشند
حتی اگر امروز در `ad_country` نگهداری می شوند، در مدل migration باید entity مجزا داشته باشند.

### اصل 3: category و geography باید decoupled باشند
location tree و category tree نباید با هم مخلوط شوند.

### اصل 4: landing page باید object مستقل باشد
یک landing فقط یک page نیست؛ یک asset سئویی با state مشخص است.

### اصل 5: indexability باید field مستقل داشته باشد
این تصمیم نباید فقط از وجود یا نبود post در وردپرس نتیجه گرفته شود.

### اصل 6: slugها قابل تغییرند، idها نه
برای migration، همیشه باید stable identifier داشته باشید.

### اصل 7: route type باید explicit باشد
نباید از روی طول path حدس زده شود که route چه نوعی است.

## entityهای پیشنهادی

### 1) MarketCountry
این entity نشان دهنده بازار مقصد است.

فیلدهای اصلی:

- `id`
- `country_code` مثل `uk`, `ca`, `us`
- `country_name_en`
- `country_name_fa`
- `primary_language`
- `secondary_languages`
- `market_tier`
- `market_status` مثل `active`, `beta`, `seed`, `disabled`
- `is_indexable`
- `launch_priority`

### 2) MarketCity
این entity شهر را در context یک market تعریف می کند.

فیلدهای اصلی:

- `id`
- `country_id`
- `city_code`
- `city_slug`
- `city_name_en`
- `city_name_fa`
- `parent_city_id` برای district/area در صورت نیاز
- `is_primary_city`
- `indexation_eligibility`
- `inventory_score`

### 3) Category
درخت دسته ها باید کاملا جدا باشد.

فیلدهای اصلی:

- `id`
- `parent_id`
- `slug`
- `name_en`
- `name_fa`
- `category_type`
- `is_money_category`
- `is_leaf`

### 4) Listing
معادل domain object اصلی آگهی.

فیلدهای اصلی:

- `id`
- `source_system_id`
- `author_id`
- `country_id`
- `city_id`
- `primary_category_id`
- `secondary_category_ids`
- `language`
- `listing_status`
- `published_at`
- `expires_at`
- `canonical_slug`
- `search_visibility`

### 5) SeoLanding
این entity باید مستقل از WordPress post type فکر شود، حتی اگر امروز روی `seo_landing` ذخیره می شود.

فیلدهای اصلی:

- `id`
- `route_type`
- `country_id`
- `city_id`
- `category_id`
- `deepest_category_id`
- `route_key`
- `seo_title`
- `meta_description`
- `intro_content`
- `faq_content`
- `is_indexable`
- `indexation_reason`
- `canonical_url`
- `last_reviewed_at`

### 6) RouteProjection
این entity لایه ای است که URL public را از entityهای domain می سازد.

فیلدهای اصلی:

- `route_key`
- `route_type`
- `country_slug`
- `city_slug`
- `category_path`
- `public_path`
- `canonical_path`
- `legacy_redirect_targets`

## فیلدهای حداقلی که همین الان باید در ذهن معماری تثبیت شوند
حتی اگر همه این ها امروز در دیتابیس وردپرس field مجزا ندارند، باید به عنوان contract منطقی پروژه تثبیت شوند:

- `country`
- `city`
- `category`
- `language`
- `market_status`
- `market_tier`
- `seo_landing_type`
- `indexation_state`
- `route_key`
- `canonical_state`

## نگاشت مدل فعلی وردپرس به مدل مقصد

### WordPress / AdForest فعلی
- `ad_post` → `Listing`
- `ad_country` → فعلا مخزن location tree
- `ad_cats` → `Category`
- `seo_landing` → `SeoLanding`
- `bornado-routing` → بخش زیادی از `RouteProjection`

### چیزی که نباید اشتباه گرفته شود
- `ad_country.term_id` لزوما معادل `country_id` نهایی نیست
- ممکن است یک term از `ad_country` در عمل city باشد
- پس term id فعلی فقط یک legacy reference است، نه business ID نهایی

## legacy referenceهایی که باید نگه داشته شوند
برای migration، این fieldها بسیار ارزشمندند:

- `legacy_wp_post_id`
- `legacy_wp_term_id`
- `legacy_taxonomy`
- `legacy_route_key`
- `legacy_site_id` اگر از multisite آمده باشد
- `legacy_permalink`

این referenceها باعث می شوند:

- redirectها راحت تر بمانند
- backfill داده ساده تر شود
- mapping بین WP و سیستم اختصاصی reversible بماند

## route_key پیشنهادی
به جای اتکا به path خام، از route key ساخت یافته استفاده شود.

نمونه:

```text
category:jobs
country:uk
country:uk|category:property
country:uk|city:london
country:uk|city:london|category:property
country:uk|city:london|category:property|leaf:apartment
```

مزیت:

- route type صریح می شود
- deduplication راحت تر می شود
- landing binding راحت تر می شود
- migration به هر framework جدید ساده تر می شود

## enumهای پیشنهادی

### `market_status`
- `seed`
- `active`
- `paused`
- `disabled`

### `market_tier`
- `tier_1`
- `tier_2`
- `tier_3`

### `seo_landing_type`
- `category_only`
- `country_only`
- `country_category`
- `country_city`
- `country_city_category`
- `country_city_category_leaf`

### `indexation_state`
- `index`
- `noindex_follow`
- `redirect`
- `disabled`

## قرارداد مهاجرت برای URL
در سیستم اختصاصی بعدی، public URL باید همیشه از این chain ساخته شود:

1. optional `MarketCountry`
2. optional `MarketCity`
3. `Category` path
4. optional pagination / filter state

برای route type برابر `category_only`، همین chain باید بدون `MarketCountry` و `MarketCity` هم معتبر باشد؛ یعنی:

- `/jobs/`
- `/property/`
- `/services/`

و نه از:

- term slugهای خام
- یا query argهای legacy

## قرارداد مهاجرت برای landing page
هر landing page باید بتواند بدون تکیه به WordPress این سوال ها را جواب دهد:

- برای کدام market است؟
- برای کدام city است؟
- برای کدام category است؟
- indexable است یا نه؟
- canonical آن چیست؟
- route type آن چیست؟

اگر answer این سوال ها فقط در URL یا taxonomy implicit باشد، migration آینده شکننده خواهد بود.

## minimal contract پیشنهادی برای export
اگر بخواهید بعدا از وردپرس export بگیرید، هر record landing ideally باید چیزی شبیه این داشته باشد:

```json
{
  "route_key": "country:uk|city:london|category:property",
  "route_type": "country_city_category",
  "country": { "code": "uk", "name_fa": "بریتانیا" },
  "city": { "slug": "london", "name_fa": "لندن" },
  "category": { "slug": "property", "name_fa": "املاک" },
  "indexation_state": "index",
  "canonical_path": "/uk/london/property/",
  "legacy": {
    "wp_post_id": 123,
    "site_id": 1
  }
}
```

## minimal contract پیشنهادی برای listing

```json
{
  "id": "listing_123",
  "legacy_wp_post_id": 123,
  "country_code": "uk",
  "city_slug": "london",
  "primary_category_slug": "property",
  "language": "fa",
  "listing_status": "published",
  "canonical_slug": "apartment-in-london"
}
```

## کارهایی که از همین حالا نباید بکنید

### 1) business identity را به slug گره بزنید
اگر slug تغییر کند، داده نباید identity خود را از دست بدهد.

### 2) country و city را یک field مشترک نگه دارید
این کار migration را پیچیده و analytics را کثیف می کند.

### 3) route type را از روی تعداد segmentها حدس بزنید
این کار در بلندمدت fragile است.

### 4) state indexation را فقط از وجود landing نتیجه بگیرید
landing ممکن است وجود داشته باشد ولی indexable نباشد.

## کارهایی که از همین حالا باید به عنوان discipline ذهنی بکنید

### 1) هر market را با country code استاندارد بشناسید
مثل:

- `uk`
- `ca`
- `us`
- `de`
- `au`

### 2) برای city و category شناسه پایدار داشته باشید
ولو امروز فقط در documentation و mapping layer.

### 3) route key را واحد تصمیم سازی سئو کنید
نه path خام را.

### 4) هر landing را با `indexation_state` و `seo_landing_type` توصیف کنید

## جمع بندی نهایی
اگر بخواهیم این سند را به یک قاعده عملی تبدیل کنیم:

**Bornado باید از همین حالا داده را به شکل entityهای بازار، شهر، دسته، آگهی و landing ببیند؛ نه صرفا به شکل taxonomy term، slug و path وردپرسی.**

این دقیقاً همان چیزی است که migration یک سال بعد را ساده، reversible و کم هزینه می کند.
