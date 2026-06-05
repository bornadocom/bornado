# قواعد فعلی Indexation و Robots

تاریخ ثبت: `05june2026`

## هدف این سند
این فایل **source of truth اجرایی** برای رفتار واقعی فعلی `bornado-routing` در زمینه:

- `robots`
- `index / noindex`
- تشخیص URLهای semantic
- تفاوت بین policy هدف و implementation فعلی

است.

## این سند چه چیزی را مشخص می کند
این سند نمی گوید Bornado در آینده ideally باید چگونه رفتار کند؛
بلکه ثبت می کند **الان در کد موجود دقیقا چه اتفاقی می افتد**.

برای policy هدف به فایل زیر مراجعه کنید:

- `plugins/bornado-routing/SEO-INDEXATION-POLICY-SINGLE-SITE.md`

برای منطق اجرایی فعلی، مرجع اصلی همین فایل است.

## تابع تصمیم گیر نهایی
منطق نهایی `robots` در این تابع جمع بندی می شود:

- `Bornado_Routing::should_noindex_request()`

این تابع در فایل زیر قرار دارد:

- `plugins/bornado-routing/bornado-routing.php`

## قانون 1: taxonomy archiveهای legacy همیشه noindex هستند
URLهای legacy زیر در implementation فعلی همیشه `noindex,follow` می گیرند:

- `/ad_country/<slug>/`
- `/ad_cats/<slug>/`

نمونه:

- `/ad_country/uk/` -> `noindex,follow`
- `/ad_country/london/` -> `noindex,follow`
- `/ad_cats/property/` -> `noindex,follow`

این رفتار مستقل از وجود یا عدم وجود landing است.

## قانون 2: routeهای `country_only` و `country_city` archive-native هستند
در implementation فعلی:

- `/uk/`
- `/uk/london/`

هنوز semantic route هستند،
اما از نظر WordPress و SEO plugin باید مثل archive واقعی `ad_country` رفتار کنند.

نتیجه عملی:

- clean URLهای country و country+city برای index شدن به landing وابسته نیستند
- title و description آن ها باید از منطق archive taxonomy و SEO plugin بیاید
- اگر query/filter اضافه داشته باشند، `noindex,follow` می شوند

نمونه:

- `/uk/` -> `index`
- `/uk/london/` -> `index`
- `/uk/?sort=price_asc` -> `noindex,follow`
- `/uk/london/?keyword=test` -> `noindex,follow`

## قانون 3: اگر route semantic category-based، landing indexable داشته باشد
اگر route semantic معتبر باشد و:

- `landing_post` پیدا شود
- و `Bornado_SEO_Landing_Manager::is_indexable()` برای آن `true` باشد

آنگاه:

- URL تمیز و canonical -> `index`
- همان URL با query/filter اضافه -> `noindex,follow`

نمونه:

- `/jobs/` با landing indexable -> `index`
- `/uk/london/property/` با landing indexable -> `index`
- `/uk/london/property/?min_price=1000` -> `noindex,follow`

## قانون 4: routeهای `category_only` بدون landing indexable همیشه noindex هستند
اگر route semantic از نوع `category_only` باشد و landing indexable نداشته باشد، نتیجه فعلی:

- `noindex,follow`

نمونه:

- `/jobs/` بدون landing indexable -> `noindex,follow`
- `/property/` بدون landing indexable -> `noindex,follow`

این rule در implementation فعلی **به طور صریح** فقط برای `category_only` نوشته شده است.

## قانون 5: routeهای semantic category-based اگر landing نداشته باشند، روی URL تمیز noindex نمی شوند
این مهم ترین نکته current-state است.

در implementation فعلی، routeهای زیر اگر:

- semantic و valid باشند
- landing indexable نداشته باشند
- و query/filter اضافه هم نداشته باشند

روی نسخه تمیز URL، `noindex` نمی شوند.

این شامل این route modeها است:

- `country_category`
- `country_city_category`

### نتیجه عملی
نمونه های زیر در current implementation روی URL تمیز indexable باقی می مانند:

- `/uk/property/` بدون landing indexable -> `index`
- `/uk/london/property/` بدون landing indexable -> `index`

اما اگر همان URLها query/filter اضافه داشته باشند:

- `/uk/?keyword=test` -> `noindex,follow`
- `/uk/london/?sort=popular` -> `noindex,follow`
- `/uk/property/?min_price=500` -> `noindex,follow`
- `/uk/london/property/?max_price=10000` -> `noindex,follow`

## قانون 6: query stateهای اضافه noindex می شوند
در routeهای semantic، اگر پارامترهای اضافه ای خارج از state ساختاری route وجود داشته باشد، URL غیرcanonical در نظر گرفته می شود و:

- `noindex,follow`

نمونه:

- `/uk/?sort=date_desc`
- `/uk/london/property/?keyword=apartment`
- `/jobs/?min_price=1000`

همه این ها در implementation فعلی باید `noindex,follow` بگیرند.

## ماتریس قطعی رفتار فعلی

### A) Legacy taxonomy archive
- `/ad_country/uk/` -> `noindex,follow`
- `/ad_country/london/` -> `noindex,follow`
- `/ad_cats/property/` -> `noindex,follow`

### B) `category_only`
- `/jobs/` با landing indexable -> `index`
- `/jobs/` بدون landing indexable -> `noindex,follow`
- `/jobs/?sort=price_asc` -> `noindex,follow`

### C) `country_only`
- `/uk/` -> `index`
- `/uk/?sort=price_asc` -> `noindex,follow`
- title/description -> از taxonomy archive / SEO plugin

### D) `country_city`
- `/uk/london/` -> `index`
- `/uk/london/?sort=price_asc` -> `noindex,follow`
- title/description -> از taxonomy archive / SEO plugin

### E) `country_category`
- `/uk/property/` با landing indexable -> `index`
- `/uk/property/` بدون landing indexable -> `index`
- `/uk/property/?sort=price_asc` -> `noindex,follow`

### F) `country_city_category`
- `/uk/london/property/` با landing indexable -> `index`
- `/uk/london/property/` بدون landing indexable -> `index`
- `/uk/london/property/?sort=price_asc` -> `noindex,follow`

## فاصله بین policy و implementation
اگر policy مدنظر تیم این باشد که:

- هر semantic route بدون landing واقعی
- یا بدون `indexable` flag

باید `noindex,follow` شود،

آن policy **هنوز به طور کامل در implementation فعلی enforce نشده است**.

در current implementation فقط این بخش ها به طور کامل enforce شده اند:

- `country_only` و `country_city` به صورت archive-native برای taxonomy `ad_country`
- `category_only` بدون landing indexable -> `noindex,follow`

اما برای این routeها هنوز enforce کامل وجود ندارد:

- `country_category`
- `country_city_category`

## نتیجه نهایی برای مراجعه های بعدی
هر وقت سوال این بود که:

- «الان کد واقعا چه می کند؟»

مرجع اول این فایل است.

هر وقت سوال این بود که:

- «target policy ایده آل ما چیست؟»

مرجع اول فایل `SEO-INDEXATION-POLICY-SINGLE-SITE.md` است.
