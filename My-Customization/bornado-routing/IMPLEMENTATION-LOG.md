# گزارش پیاده سازی Bornado SEO Routing

## هدف این مستند
این فایل ثبت می کند که در این فاز دقیقا چه تغییراتی برای معماری URL سئویی Bornado انجام شده است تا بعدا بدون ابهام بتوان فهمید چه چیزهایی پیاده سازی شده، در چه فایل هایی انجام شده، و چه محدوده ای از پروژه را تحت تاثیر قرار می دهد.

## خروجی نهایی این فاز
یک لایه routing سئویی مستقل برای AdForest ساخته شد که بدون تغییر در فایل های اصلی قالب، URL های semantic را به state داخلی فعلی AdForest ترجمه می کند.

الگوی URL هدف:

- `/sitePrefix/city/`
- `/sitePrefix/city/category/`
- `/sitePrefix/city/category/subcategory/`
- `/sitePrefix/city/category/subcategory/page/2/`

نمونه واقعی:

- `/uk/manchester/`
- `/uk/manchester/property/`
- `/uk/manchester/property/apartment/`

## فایل های ایجاد شده

### 1) افزونه routing
فایل اصلی افزوده شده:

- `My-Customization/bornado-routing/bornado-routing.php`

این فایل هسته اصلی معماری URL جدید را پیاده سازی می کند.

### 2) فایل های مستندات
در همین پوشه برای شفافیت بیشتر این مستندات اضافه شدند:

- `My-Customization/bornado-routing/IMPLEMENTATION-LOG.md`
- `My-Customization/bornado-routing/USAGE-GUIDE.md`

## فایل های اصلاح شده

در وضعیت نهایی این فاز، پیاده سازی فعال فقط داخل افزونه `bornado-routing` و مستندات همان پوشه نگهداری می شود.

## کارهایی که در افزونه routing انجام شد

### 1) ثبت rewrite rule برای URL های semantic
در افزونه، rewrite rule های عمومی ثبت شد تا مسیرهای semantic به query vars داخلی وردپرس تبدیل شوند:

- `bornado_seo_route`
- `bornado_seo_path`

همچنین مسیر صفحه بندی نیز پشتیبانی شد:

- `/page/N/`

### 2) parse و resolve مسیر
در نسخه نهایی، resolve مسیر فقط به `parse_request` محدود نماند و در چند لایه تقویت شد:

- segment اول به عنوان `city` از taxonomy `ad_country` resolve می شود
- segment های بعدی به عنوان category path از taxonomy `ad_cats` resolve می شوند
- رابطه parent/child بین category و subcategory به صورت سخت گیرانه اعتبارسنجی می شود
- route های `category-only` نیز پشتیبانی شدند، مثل:
  - `/uk/property/`
  - `/uk/jobs/`
- route های `city-only` نیز پشتیبانی شدند، مثل:
  - `/uk/london/`

برای hardening نهایی، این لایه ها اضافه شدند:

- `request` filter برای گرفتن route قبل از این که وردپرس آن را به page/attachment/404 تبدیل کند
- `parse_request` برای تکمیل context
- `pre_handle_404` برای late recovery اگر وردپرس زودتر request را 404 تشخیص داده باشد
- نرمال سازی path مخصوص Multisite subdirectory تا prefixهایی مثل `/uk/` داخل resolver اشتباه تفسیر نشوند

اگر chain معتبر نباشد، route نامعتبر در نظر گرفته می شود.

### 3) ترجمه URL جدید به state داخلی AdForest
از آنجا که AdForest جستجو را عمدتا با `$_GET['country_id']` و `$_GET['cat_id']` می سازد، در این افزونه state داخلی تزریق شد تا قالب بدون بازنویسی هسته، همان نتایج را از URL جدید نمایش دهد.

کارهای انجام شده در این بخش:

- تعیین `country_id` از روی شهر
- تعیین `cat_id` از روی deepest category
- تزریق مقادیر به `$_GET` و `$_REQUEST`
- بازسازی `$_SERVER['QUERY_STRING']` برای سازگاری با `adforest_search_params()`

این بخش بسیار مهم است چون فرم ها و hidden input های AdForest به query string خام وابسته هستند.

### 4) استفاده از template فعلی AdForest
برای route های semantic معتبر، به جای ساخت template جدید، از `page-search.php` خود قالب استفاده شد.

نتیجه:

- از rendering فعلی AdForest استفاده می شود
- منطق core قالب دست نخورده باقی می ماند
- نگهداری بعدی ساده تر می شود
- route معتبر مستقیما به search page تنظیم شده در AdForest bind می شود تا main query وردپرس آن را صفحه معتبر بداند

### 5) 301 redirect برای URL های legacy
لایه redirect اضافه شد تا URL های قدیمی قابل تبدیل، به canonical جدید هدایت شوند.

نمونه URL های legacy که برایشان منطق redirect در نظر گرفته شد:

- `?country_id=...`
- `?cat_id=...`
- `?ad_cat_sub...`
- archive های `ad_country`
- archive های `ad_cats`

همچنین pagination های مبتنی بر query string مثل `?paged=2` و `?page=2` به ساختار `/page/2/` نرمال شدند.

### 6) canonical
برای جلوگیری از duplicate content:

- canonical route واحد ساخته شد
- canonical برای route های جدید و legacy تولید می شود
- برای SEO plugin ها نیز فیلتر canonical اضافه شد:
  - Yoast
  - Rank Math
  - AIOSEO
- در صورت نبود plugin SEO، تگ canonical مستقیم در `wp_head` چاپ می شود
- در نسخه نهایی، چاپ canonical دستی طوری harden شد که در صورت حضور providerهای رایج SEO، canonical تکراری چاپ نشود

### 7) noindex برای URL های غیرcanonical
منطق `wp_robots` اضافه شد تا URL های filter-based و query-heavy که canonical SEO نیستند، در صورت نیاز `noindex,follow` بگیرند.

این بخش برای جلوگیری از index شدن URL های تکراری بسیار مهم است.

### 8) بازنویسی term link ها
برای لینک taxonomy ها:

- `term_link` برای `ad_country`
- `term_link` برای `ad_cats`

به URL semantic جدید هدایت شد. در نسخه نهایی:

- اگر country/city context موجود باشد، لینک دسته به فرم country-first ساخته می شود
- اگر context کشور موجود نباشد، لینک دسته به فرم `category-only` مثل `/jobs/` ساخته می شود
- routeهای `category-only` معتبر هستند، اما به عنوان hub/UX بدون کشور، باید `noindex,follow` بمانند

این تغییر برای حل مشکل واقعی لایو لازم شد، چون بعضی دسته ها مثل `jobs` و `services` باید بدون context شهر هم route معتبر داشته باشند.

### 9) بازنویسی لینک های تولیدشده توسط AdForest
چون خود AdForest بعضی URL ها را با query string می سازد، فیلترهای زیر برای override رفتار آن اضافه شدند:

- `adforest_page_lang_url`
- `adforest_category_widget_form_action`
- `adforest_filter_taxonomy_popup_actions`

این کار باعث شد فرم ها و popup های دسته در route های جدید تا حد ممکن روی ساختار semantic باقی بمانند.

### 10) جلوگیری از تفسیر اشتباه route به عنوان page/attachment/media
در لایو مشاهده شد که بعضی slugها مثل `jobs` یا `services` ممکن است به جای archive semantic، به مسیرهای media یا attachment resolve شوند.

برای حل این مشکل:

- routeهای semantic در `request` filter خیلی زودتر از چرخه عادی وردپرس گرفته شدند
- اگر route معتبر باشد، request مستقیما به search page ادفارست bind می شود
- این کار از افتادن مسیرها به 404 یا asset URLهایی مثل فایل SVG جلوگیری می کند

این بخش یکی از مهم ترین hardeningهای نهایی برای production بود.

### 11) debug headers برای عیب یابی لایو
برای عیب یابی امن و سبک روی محیط live، debug mode هدر اضافه شد که با query parameter فعال می شود:

- `?bornado_debug_route=1`

و در response header وضعیت resolver را نشان می دهد.

این قابلیت برای تشخیص این که route اصلا وارد context شده یا نه، در فرایند رفع خطاهای لایو استفاده شد.

## مسائلی که عمدا انجام نشد

- هیچ فایل core از قالب AdForest ویرایش نشد
- child theme موجود revert یا overwrite نشد
- ساختار داخلی taxonomy های وردپرس تغییر داده نشد
- ثبت دوباره taxonomy ها یا تغییر slug داخلی آن ها انجام نشد
- سیستم search/filter AdForest حذف یا rewrite کامل نشد

## محدودیت های شناخته شده

### 1) این workspace نصب کامل وردپرس نیست
در این repo ریشه واقعی `wp-content` و هسته کامل وردپرس موجود نبود. به همین دلیل افزونه در مسیر سورسی زیر ساخته شد:

- `My-Customization/bornado-routing/`

و باید روی سرور در محل واقعی وردپرس قرار بگیرد.

### 2) تست runtime کامل انجام نشد
اعتبارسنجی منطقی و lint IDE انجام شد، اما اجرای `php -l` یا تست واقعی وردپرس در shell این ماشین ممکن نبود چون دستور `php` در محیط shell در دسترس نبود.

### 3) برخی لینک های قدیمی قالب ممکن است هنوز context شهر نداشته باشند
این مورد در نسخه اولیه وجود داشت، اما در نسخه نهایی تا حد زیادی با پشتیبانی از `category-only` route برطرف شد.

با این حال همچنان باید در هر آپدیت مهم AdForest بررسی شود که template یا widget جدیدی لینک taxonomy را به روشی متفاوت نسازد.

### 4) وضعیت pagination
پشتیبانی pagination semantic در کد نهایی harden شده و parsing مستقیم `/page/N/` به resolver اضافه شده است.

با این حال چون آخرین اصلاح pagination بعد از review نهایی اعمال شد، توصیه می شود در محیط live این URLها همیشه به عنوان smoke test چک شوند:

- `/uk/jobs/page/2/`
- `/uk/property/page/2/`
- `/uk/manchester/property/page/2/`

## نتیجه کلی این فاز
در پایان این فاز:

- routing سئویی پایه پیاده سازی شد
- URL های semantic به state داخلی AdForest ترجمه می شوند
- redirect و canonical برای جلوگیری از duplicate content اضافه شد
- pagination مسیر semantic پشتیبانی می شود
- زیرساخت برای Multisite آماده شد
- routeهای country-first و `category-only` هم روی لایو پشتیبانی شدند
- تداخل با attachment/media/page در لایه request کنترل شد

## وضعیت فعلی برای ادامه کار
اگر بعدا بخواهیم فاز بعدی را انجام دهیم، بهترین ادامه ها این ها هستند:

- تست واقعی روی محیط staging/live
- بررسی خروجی crawl با Search Console
- افزودن breadcrumb/schema منطبق با route جدید
- بررسی edge case های slug conflict
- مانیتور کردن pagination semantic در لایو بعد از هر آپدیت
