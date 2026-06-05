# Single-Site Country Routing Smoke Test

## هدف این فایل
این فایل ماتریس smoke test برای معماری جدید Bornado در حالت:

- single-site root
- country-first routing
- و الگوی `/{country}/{city}/{category}/...`

را ثبت می کند.

## نکته مهم
در این repository، staging/runtime واقعی در دسترس نیست.

بنابراین در این فاز:

- پیاده سازی کد انجام شده است
- lint روی فایل های ویرایش شده پاس شده است
- اما تست runtime روی محیط staging/WordPress live باید با این checklist انجام شود

## وضعیت validation این فاز

### انجام شده
- بازطراحی route context
- route typeهای جدید برای landing
- همگام سازی search context
- همگام سازی breadcrumb و schema
- lint check روی فایل های ویرایش شده

### باقی مانده برای staging واقعی
- تست resolve درخواست ها
- تست canonical و robots
- تست bind شدن landingها
- تست interaction فرم های search

## URLهای اصلی برای تست

### 1) Country Only
- `/uk/`
- `/ca/`
- `/us/`

این URLها باید semantic بمانند و از نظر query/archive یک term واقعی `ad_country` باشند، اما برای UI با `page-search.php` رندر شوند.

### 2) Country + City
- `/uk/london/`
- `/ca/toronto/`
- `/us/los-angeles/`

این URLها نیز باید semantic بمانند و از دید WordPress و SEO plugin، archive واقعی taxonomy `ad_country` باشند، اما ظاهرشان با ad search یکسان بماند.

### 3) Country + Category
- `/uk/property/`
- `/uk/jobs/`
- `/ca/services/`

### 4) Category Only Hubs / Landings
- `/jobs/`
- `/property/`
- `/services/`

این URLها نباید 404 شوند یا به attachment/media مثل SVG هدایت شوند. اگر برای آن ها `seo_landing` واقعی و `indexable` تعریف شده باشد، باید همان landing را روی URL ریشه ای مثل `/jobs/` render کنند و `index,follow` بمانند. اگر landing تعریف نشده باشد یا indexable نباشد، باید به عنوان search fallback با `noindex,follow` کار کنند. اگر کاربر در همین مسیر country انتخاب کند، درخواست query-based باید به مسیر country-first مثل `/uk/jobs/` نرمال شود.

### 5) Country + City + Category
- `/uk/london/property/`
- `/uk/london/jobs/`
- `/ca/toronto/services/`

### 6) Deep Category
- `/uk/london/property/apartment/`
- `/ca/toronto/services/immigration/`

### 7) Pagination
- `/uk/london/property/page/2/`
- `/uk/property/page/2/`
- `/jobs/page/2/`

### 8) Legacy Inputs
- `?country_id=<city_or_country_term_id>`
- `?country_id=<term>&cat_id=<term>`
- legacy taxonomy archiveهای `ad_country` و `ad_cats`

## برای هر URL چه چیزهایی باید چک شود

### A. Route resolve
- صفحه 404 نشود
- route context معتبر باشد
- country و city درست تشخیص داده شوند
- category chain درست تشخیص داده شود

### B. Canonical
- canonical دقیقا به URL semantic نهایی اشاره کند
- query stateها canonical مستقل نداشته باشند
- canonical drift بین plugin و SEO plugin رخ ندهد

### C. Robots
- صفحه های indexable `index,follow` بمانند
- routeهای thin یا query-based `noindex,follow` شوند

### D. Landing binding
- برای `country_only` و `country_city`: landing نباید owner template یا meta باشد و route باید archive-native بماند، حتی اگر با `page-search.php` رندر شود
- برای routeهای category-based: اگر route برای آن landing تعریف شده، template landing render شود
- برای routeهای category-based بدون landing: page-search fallback درست کار کند

### E. Breadcrumb
- country crumb درست باشد
- city crumb درست باشد
- category crumbها درست باشند
- Breadcrumb schema با canonical هماهنگ باشد

### F. Search forms
- فرم های category/location/search روی semantic base درست submit شوند
- hidden fields پارامترهای خالی یا route-derived اضافی را حمل نکنند
- all cities / all categories / all filters actionها به مسیر درست برگردند

## جدول smoke test

| URL | Expected route_mode | Expected canonical | Expected page type |
| --- | --- | --- | --- |
| `/uk/` | `country_only` | `/uk/` | archive-native query + ad-search template |
| `/uk/london/` | `country_city` | `/uk/london/` | archive-native query + ad-search template |
| `/jobs/` | `category_only` | `/jobs/` | landing indexable یا search fallback، بسته به route binding |
| `/uk/property/` | `country_category` | `/uk/property/` | landing یا search fallback |
| `/uk/london/property/` | `country_city_category` | `/uk/london/property/` | landing یا search fallback |
| `/uk/london/property/apartment/` | `country_city_category` | همان URL | landing یا search fallback |
| `/uk/london/property/page/2/` | `country_city_category` | `/uk/london/property/page/2/` | paginated search/landing |
| `/jobs/?country_id=<uk_term_id>` | redirect | `/uk/jobs/` | 301 به country-first |

## چک لیست مرورگر / view-source

### در HTML
- `html lang="fa"` وجود داشته باشد
- canonical درست چاپ شده باشد
- اگر breadcrumb schema چاپ می شود، itemها با URLهای semantic همسو باشند

### در rendered page
- breadcrumb country > city > category درست دیده شود
- title صفحه context جغرافیایی درست داشته باشد
- tag/filter submitها `?` خالی تولید نکنند
- برای `/uk/` و `/uk/london/`، title و description از تنظیمات archive taxonomy در Rank Math یا term meta بیاید

## چک لیست Query State
این URLها باید:

- render شوند
- ولی canonical آن ها به route پایه برگردد

نمونه:

- `/uk/london/property/?sort=latest`
- `/uk/london/property/?min_price=500`
- `/uk/london/property/?keyword=accountant`

## چک لیست Legacy Redirect

### انتظار
- URLهای query-based legacy در صورت mapping معتبر، 301 شوند
- archiveهای taxonomy legacy با semantic URL رقابت نکنند

### مثال
- `/?country_id=123&cat_id=456`
- `/ad_country/london/`
- `/ad_category/property/`

## نکات خاص QA

### 1) ambiguity در slugها
اگر slug یک city با slug یک category یا page تداخل داشته باشد، باید explicit test نوشته شود.

### 2) term hierarchy
اگر city در `ad_country` فرزند country نباشد، route باید invalid شود.

### 3) landing admin preview
در admin، preview URL لندینگ ها باید با route type جدید همخوان باشد.

## نتیجه مورد انتظار
اگر این smoke test بدون خطای مهم پاس شود، Bornado از نظر routing آماده این است که:

- روی single-site واقعی کار کند
- country و city را صریح در URL حمل کند
- و از نظر SEO و AI retrieval با context بسیار شفاف تر عمل کند
