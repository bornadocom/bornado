# ممیزی مدل Country / City / Category در Bornado

## هدف این فایل
این فایل وضعیت واقعی کدبیس فعلی Bornado را از نظر مدل کشور، شهر، دسته، routing و search ثبت می کند تا تصمیم معماری روی فرض اشتباه بنا نشود.

هدف این سند «توصیه محصولی» نیست؛ هدف آن ثبت حقیقت فنی کد فعلی و gap های مهم است.

## جمع بندی اجرایی
نتیجه اصلی ممیزی این است:

- کد فعلی Bornado برای **single-site با segmentation مفهومی** مناسب تر از site-per-country است.
- اما routing فعلی برای **حمل همزمان `country + city + category` در یک سایت واحد** هنوز آماده نیست.
- ساختار فعلی URLهای شبیه `/uk/london/property/` امروز عملا بیشتر با **prefix مولتی سایت** کار می کند، نه با دو لایه geography داخل یک site.

به بیان ساده:

- در نصب subdirectory multisite، `uk` می تواند prefix سایت باشد
- و بعد `london/property` توسط routing resolve شود

اما در single-site:

- اگر بخواهید `uk/london/property` داشته باشید
- resolver فعلی `uk` را به عنوان اولین `ad_country` term می خواند
- و `london/property` را به عنوان chain دسته ها تفسیر می کند
- بنابراین این معماری بدون توسعه routing، هنوز fully supported نیست

## فایل های مرجع اصلی

### 1) شبکه وردپرس
- `wp-config.php`

سیگنال های مهم:

- `MULTISITE = true`
- `SUBDOMAIN_INSTALL = false`
- `DOMAIN_CURRENT_SITE = bornado.com`
- `PATH_CURRENT_SITE = /`

این یعنی کدبیس برای شبکه subdirectory نیز آماده شده است.

### 2) مدل داده AdForest
- `adforest/adforest/inc/setup-wizard-adf/plugins/adforest-framework/adforest-framework/cpt/index.php`

نکات مهم:

- CPT مستقل `_sb_country` ثبت شده است
- CPT اصلی آگهی `ad_post` است
- taxonomy سلسله مراتبی `ad_country` برای location روی `ad_post` ثبت شده است
- taxonomy سلسله مراتبی `ad_cats` برای دسته ها ثبت شده است

نکته مهم معماری:

- در عمل Bornado روی `_sb_country` سوار نشده است
- بلکه در routing و search عمدتا از `ad_country` استفاده می کند

## حقیقت فنی مدل جغرافیا در کد فعلی

### 1) `ad_country` در عمل «country model» خالص نیست
هرچند نام taxonomy برابر `ad_country` است، اما در UI و routing بیشتر مانند **location hierarchy** استفاده می شود:

- country
- city
- و در بعضی فرض ها حتی سطح های محلی تر

این موضوع در خود AdForest هم از labelها مشخص است:

- `Ad Locations`
- `Parent Location`
- `Edit Location`

پس در Bornado فعلی، `ad_country` باید به عنوان **درخت جغرافیایی** دیده شود، نه صرفا لیست کشورها.

### 2) routing فعلی فقط یک سگمنت جغرافیایی را در URL semantic می سازد
در `My-Customization/bornado-routing/bornado-routing.php` تابع `build_semantic_url()` فقط:

- یک slug از `ad_country`
- و سپس chain کامل `ad_cats`

را وارد URL می کند.

یعنی target URL فعلی این فرم را می سازد:

- `{location}/{category}/{subcategory}`

نه این فرم را:

- `{country}/{city}/{category}/{subcategory}`

## حقیقت فنی resolver فعلی

در `resolve_semantic_route()`:

- اولین segment با `get_term_by( 'slug', ..., 'ad_country' )` چک می شود
- اگر match شد، به عنوان `city_term` در context ذخیره می شود
- بقیه segmentها به عنوان chain دسته ها resolve می شوند

پس route mode های فعلی عملا این ها هستند:

- `category_only`
- `city_only`
- `city_category`

و هنوز route mode ای برای این ها وجود ندارد:

- `country_only`
- `country_city`
- `country_category`
- `country_city_category`

## نقش مولتی سایت در URLهای فعلی
در همان فایل routing، تابع `normalize_route_path()` path را نسبت به `home_url()` normalize می کند.

این یعنی در multisite subdirectory:

- آدرس ورودی ممکن است `uk/london/property`
- ولی بعد از حذف prefix سایت، resolver عملا `london/property` را می بیند

در نتیجه:

- `/uk/london/property/` در معماری فعلی بیشتر «prefix سایت + route داخلی» است
- نه «country + city + category در یک site واحد»

این مهم ترین نکته این ممیزی است.

## نقش Search Core
- `My-Customization/bornado-search-core/includes/class-bornado-search-core.php`
- `My-Customization/bornado-search-core/includes/class-bornado-search-context.php`

Search Core در query context این کلیدها را normalize می کند:

- `country_id`
- `ad_country`
- `location`

پس در لایه search هم country/location هنوز یک مفهوم واحد و فشرده است.

همچنین helperهای path:

- `strip_leading_city()`
- `strip_leading_category()`
- `strip_all_filters()`

همگی با فرض **یک location leading segment** طراحی شده اند.

## نقش SEO Landing
- `My-Customization/bornado-routing/includes/class-seo-landing-manager.php`

SEO landing CPT فعلی route type های زیر را پشتیبانی می کند:

- `city_only`
- `category_only`
- `city_category`

و meta key اصلی آن هنوز از واژه `country_term_id` استفاده می کند، در حالی که UI می گوید:

- `City (ad_country)`

این هم نشان می دهد که کد فعلی میان country و city در این taxonomy مرز صریح و سفتی ندارد.

## نتیجه مهم برای تصمیم معماری

### چیزی که الان بدون تغییر اساسی پشتیبانی می شود
- یک site با segmentation بر پایه location + category
- یا multisite subdirectory که prefix market/site را خارج از resolver حمل کند

### چیزی که هنوز target-state است، نه current-state
- single-site با URLهای کامل این شکلی:
  - `/uk/`
  - `/uk/london/`
  - `/uk/london/property/`
  - `/uk/london/property/apartment/`

برای رسیدن به این target-state باید routing توسعه پیدا کند.

## توصیه مدل مفهومی برای فاز بعد
اگر Bornado بخواهد معماری single-site را حفظ کند، مدل مفهومی پیشنهادی این است:

- `country` = سطح اول market geography
- `city` = child اصلی country
- `district_or_area` = اختیاری
- `category` = درخت `ad_cats`
- `seo_landing_type` = یک enum مستقل از route format

اما مهم است که این مدل مفهومی **مستقیما با ساختار فعلی URL یکی فرض نشود**.

## Gap هایی که باید صریح ثبت شوند

### Gap 1: ambiguity در نام گذاری
- `ad_country` در عمل location tree است
- در routing واژه `city_term` به کار می رود
- در meta box واژه `country_term_id` استفاده شده ولی label می گوید `City (ad_country)`

### Gap 2: فقط یک geo segment در URL
build فعلی URL فقط یک term از `ad_country` را به path اضافه می کند.

### Gap 3: route type های ناکافی برای single-site country-first
برای target-state جدید، landing manager و resolver باید route type های جدید بشناسند.

### Gap 4: policy فعلی indexation بیشتر با مدل subsite-aligned نوشته شده است
فایل `SEO-INDEXATION-POLICY.md` منطق خوبی دارد، اما فرضش این است که context کشور در subsite حمل می شود.

## تصمیم عملی مبتنی بر این ممیزی
این ممیزی تصمیم اصلی plan را تایید می کند:

- **از نظر business architecture**: single-site با country segmentation هنوز بهترین انتخاب است
- **از نظر code architecture**: برای رسیدن به URL parity با مدل فعلی multisite، یک فاز توسعه routing لازم است

پس تصمیم حرفه ای این است:

1. single-site را به عنوان مقصد معماری نگه دارید
2. gap فعلی routing را صریح بپذیرید
3. migration به single-site country-first را به صورت یک فاز مهندسی مستقل انجام دهید
4. تا قبل از آن، assumptions محصولی و assumptions فنی را با هم قاطی نکنید

## خروجی نهایی این ممیزی
اگر بخواهیم فقط یک جمله را به عنوان verdict این فایل نگه داریم، آن جمله این است:

**Bornado از نظر استراتژی باید single-site بماند، اما از نظر routing هنوز برای single-site با URLهای country/city/category به یک hardening و redesign محدود نیاز دارد.**
