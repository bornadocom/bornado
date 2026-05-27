# سیاست Canonical و Indexation برای معماری Single-Site در Bornado

## هدف این فایل
این فایل policy پیشنهادی canonical و indexation را برای زمانی تعریف می کند که Bornado بخواهد:

- روی یک سایت واحد بماند
- کشورها را داخل همان site مدیریت کند
- و URLهای country-first بسازد

این سند مکمل `SEO-INDEXATION-POLICY.md` است.

تفاوت مهم:

- فایل قدیمی بیشتر با فرض **context کشور در subsite** نوشته شده است
- این فایل با فرض **context کشور در path همان site** نوشته شده است

## اصل کلیدی
در Bornado، هر URL فقط وقتی باید index شود که هر 4 شرط زیر را همزمان داشته باشد:

1. intent مشخص
2. inventory یا supply کافی
3. محتوای editorial واقعی
4. canonical شفاف و بدون ambiguity

اگر یکی از این 4 شرط وجود نداشته باشد:

- `noindex,follow`

## نکته حیاتی درباره current-state
این policy برای **target-state single-site** تعریف شده است.

در current-state:

- routing فعلی هنوز بیشتر `city_only`, `category_only`, `city_category` را می شناسد
- و برای حمل کامل `country/city/category` در یک site نیاز به توسعه دارد

بنابراین این سند:

- policy نهایی مورد نظر را مشخص می کند
- اما لزوماً به این معنی نیست که همین الان تمام routeهای آن بدون توسعه routing قابل اجرا هستند

## ساختار URL هدف
اگر Bornado روی single-site country segmentation بماند، ساختار target باید این باشد:

- `/uk/`
- `/uk/property/`
- `/uk/london/`
- `/uk/london/property/`
- `/uk/london/property/apartment/`

## قوانین canonical

### Rule 1: هر URL indexable باید self-canonical باشد
مثال:

- `/uk/` → canonical به خودش
- `/uk/london/` → canonical به خودش
- `/uk/london/property/` → canonical به خودش

### Rule 2: query stateها canonical مستقل ندارند
نمونه:

- `?sort=`
- `?min_price=`
- `?max_price=`
- `?keyword=`
- `?custom[...]`

این ها باید canonical شوند به route پایه semantic.

### Rule 3: legacy URLها canonical نهایی نیستند
نمونه:

- `/ad_country/...`
- `/ad_category/...`
- query URLهای قدیمی مثل `?country_id=...`

اگر mapping semantic معتبر وجود داشت:

- `301 redirect`

اگر mapping معتبر وجود نداشت:

- `noindex`

### Rule 4: URL بومی `seo_landing` هرگز public canonical اصلی نیست
native permalink مربوط به CPT `seo_landing` باید:

- یا 301 شود
- یا canonical و redirect آن به public route نهایی برگردد

## قوانین indexation به تفکیک نوع صفحه

### 1) Country Hub
نمونه:

- `/uk/`
- `/ca/`
- `/us/`

وضعیت پیشنهادی:

- `index` فقط اگر country hub واقعی داشته باشد

حداقل شرایط:

- intro محتوایی روشن
- internal links به city hubها و category pillarها
- metadata اختصاصی
- مشخص بودن جامعه هدف

اگر country page فقط یک archive خام یا thin wrapper باشد:

- `noindex`

### 2) Category Only Hub / Landing
نمونه:

- `/jobs/`
- `/property/`
- `/services/`

وضعیت پیشنهادی:

- قابل نمایش برای UX
- اگر landing واقعی و `indexable` داشته باشد: `index`
- اگر landing ندارد یا thin است: `noindex,follow`
- canonical تمیز خودش را داشته باشد، مثلا `/jobs/`

کاربرد:

- وقتی کاربر هنوز کشور انتخاب نکرده اما دسته را انتخاب کرده است
- جلوگیری از 404 یا redirect اشتباه به attachment/media
- ایجاد یک hub broad-intent برای خود category وقتی محتوای editorial واقعی دارد
- دادن فرصت به کاربر برای انتخاب country/city و سپس انتقال به مسیر country-first

اگر country context بعدا از query، session یا انتخاب کاربر به دست آمد:

- مسیر باید به canonical country-first مثل `/uk/jobs/` یا `/ca/jobs/` نرمال شود

شرط index:

- landing واقعی و publish شده
- `indexable` flag روشن
- intro / FAQ / محتوای editorial واقعی
- broad intent مستقل از country
- thin wrapper یا صرفا search shell نباشد

### 3) Country + Category
نمونه:

- `/uk/property/`
- `/uk/jobs/`
- `/ca/services/`

وضعیت پیشنهادی:

- `index` فقط برای pillar categoryهای اصلی
- بقیه به صورت پیش فرض `noindex`

مناسب برای:

- services
- jobs
- businesses
- property

شرط index:

- demand broad
- intro editorial
- FAQ یا explanatory block
- internal links به city-category pageهای مهم

### 4) Country + City
نمونه:

- `/uk/london/`
- `/ca/toronto/`
- `/us/los-angeles/`

وضعیت پیشنهادی:

- فقط برای شهرهای اولویت دار `index`
- برای شهرهای کم عمق `noindex`

سیگنال های city hub indexable:

- inventory پایدار
- community density
- intent جستجوی کافی
- امکان نوشتن محتوای محلی واقعی

### 5) Country + City + Category
نمونه:

- `/uk/london/property/`
- `/ca/toronto/jobs/`
- `/us/los-angeles/services/`

وضعیت پیشنهادی:

- این ها مهم ترین money pageهای Bornado هستند
- `index` فقط با landing واقعی و supply کافی

این نوع صفحه:

- دقیق ترین intent را دارد
- برای internal linking بسیار مهم است
- برای AI search هم واضح ترین market context را می سازد

### 6) Country + City + Category + Subcategory
نمونه:

- `/uk/london/property/apartment/`
- `/ca/toronto/services/immigration/`

وضعیت پیشنهادی:

- فقط selective `index`
- نه به صورت bulk

اگر این صفحات فقط thin combinations باشند:

- `noindex`

## آستانه های عملیاتی پیشنهادی
این thresholdها ranking factor نیستند؛ فقط rule-of-thumb عملیاتی برای جلوگیری از thin index هستند.

### Country Hub
`index` اگر:

- حداقل 30 آگهی فعال مرتبط
- یا حداقل 3 city cluster فعال
- و محتوای editorial واقعی

### Country + Category
`index` اگر:

- حداقل 15 آگهی/مورد فعال
- یا evidence روشن از intent broad

### Country + City
`index` اگر:

- حداقل 12 آگهی/مورد فعال
- و شهر واقعا برای diaspora آن کشور مهم باشد

### Country + City + Category
`index` اگر:

- حداقل 8 آگهی/مورد فعال
- و صفحه بتواند intro و FAQ خاص همان intent را حمل کند

### Deep Subcategory
`index` اگر:

- حداقل 5 مورد فعال
- و query intent متفاوت و مستقل داشته باشد

اگر این thresholdها برقرار نیست:

- `noindex,follow`

## Semantic routeهای بدون landing
هر routeی که از نظر فنی resolve می شود اما:

- landing واقعی ندارد
- metadata اختصاصی ندارد
- یا indexable flag ندارد

باید:

- `noindex,follow`

این همان قاعده ای است که به جلوگیری از index bloat کمک می کند.

## Query URLs و filter states
همه filter stateها:

- crawlable برای UX بمانند
- ولی indexable نباشند

پیشنهاد:

- `noindex,follow`
- canonical به base semantic route

نمونه:

- `/uk/london/property/?sort=price_desc`
- `/uk/london/property/?min_price=1000`
- `/uk/london/property/?keyword=accountant`

همه این ها باید به:

- `/uk/london/property/`

canonical شوند.

## Pagination
نمونه:

- `/uk/property/page/2/`
- `/uk/london/property/page/3/`

وضعیت پیشنهادی:

- crawlable بمانند
- ولی SEO target اصلی strategy نباشند

در فاز فعلی:

- self-canonical برای pagination قابل قبول است
- اما landing editorial برای pagination ساخته نشود

اگر بعدا data نشان دهد pagination index budget را هدر می دهد:

- می توان به `noindex,follow` تغییر داد

## Taxonomy archiveهای legacy
نمونه:

- `/ad_country/london/`
- `/ad_category/property/`

وضعیت پیشنهادی:

- اگر معادل semantic route دارند: `301`
- اگر ندارند: `noindex`

نباید این archiveهای legacy با semantic routeهای جدید وارد رقابت index شوند.

## سیاست robots به زبان ساده

### باید index شوند
- country hubهای واقعی
- category-only hubهای منتخب با landing واقعی
- pillar categoryهای مهم
- city hubهای اولویت دار
- city-category money pageهای منتخب
- deep pageهای محدود و ثابت شده

### باید noindex شوند
- query URLs
- filter states
- routeهای بدون landing
- thin location pages
- thin subcategory combinations
- archiveهای legacy بدون redirect

## منطق AI Search
در AI-driven retrieval، کشور و شهر فقط وقتی ارزش دارند که تفاوت واقعی ایجاد کنند.

پس هر page indexable باید:

- market را روشن کند
- audience را روشن کند
- تفاوت واقعی آن market را بگوید
- و فقط clone page کشور دیگر نباشد

مثال خوب:

- `املاک برای ایرانیان ساکن لندن | Bornado`

مثال ضعیف:

- صفحه ای که فقط نام London جایگزین Manchester شده و هیچ تمایز محتوایی ندارد

## smoke test پیشنهادی
بعد از پیاده سازی single-site routing، این URLها باید هر بار تست شوند:

- `/uk/`
- `/uk/property/`
- `/uk/london/`
- `/uk/london/property/`
- `/uk/london/property/apartment/`
- `/uk/london/property/page/2/`
- `/uk/london/property/?sort=latest`
- یک legacy URL قدیمی با `country_id`

و روی همه این ها باید 4 چیز بررسی شود:

1. status code
2. canonical
3. robots
4. landing binding

## جمع بندی نهایی
اگر Bornado معماری single-site را اجرا می کند، policy ساده این است:

- **فقط URLهایی index شوند که market-specific, content-backed و supply-backed هستند**
- **باقی routeها برای UX بمانند، اما وارد index اصلی نشوند**

و اگر بخواهیم این را به یک جمله تبدیل کنیم:

**در Bornado، semantic route به تنهایی کافی نیست؛ فقط semantic route + landing واقعی + inventory کافی حق index شدن دارد.**
