# راهنمای اجرای کامل و تست

این فایل یک راهنمای ساده و عملی برای راه‌اندازی، import، تست و اطمینان از صحت عملکرد سیستم انتخاب جهانی کشور/شهر است.

## این قابلیت دقیقا چه کاری می‌کند

- کاربر در فرم درج آگهی می‌تواند از بین کشورهای جهان و شهرهای جهان انتخاب کند.
- `ad_country` از قبل با همه شهرهای دنیا سنگین نمی‌شود.
- فقط وقتی آگهی واقعا `publish` شد، location نهایی به `ad_country` sync می‌شود.
- این رفتار باید با هر دو حالت `auto approved` و `manual approved` و همچنین publish بعد از پرداخت سازگار باشد.

## فایل‌های اصلی این پیاده‌سازی

- `adforest-child/bornado-geo-catalog.php`
- `adforest-child/bornado-geo-currency-overrides.php`
- `adforest-child/bornado-global-ad-location.php`
- `adforest-child/assets/js/bornado-global-ad-location.js`
- `adforest-child/assets/css/bornado-global-ad-location.css`
- `scripts/prepare-geonames.ps1`
- `BORNADO-GEONAMES-SETUP.md`

## نکته مهم

- در کدهای اصلی قالب تغییری داده نشده است.
- همه چیز از لایه‌ی `child theme` و کدهای مستقل شما سوار شده است.
- روی لوکالی که وردپرس ندارد فقط می‌توانید فایل‌های GeoNames را آماده کنید؛ import و تست واقعی باید روی محیطی انجام شود که وردپرس و WP-CLI دارد.

## مرحله 1: آماده‌سازی قبل از اجرا

قبل از هر چیز این موارد را آماده کنید:

- یک بکاپ از دیتابیس
- یک بکاپ از `wp-content/themes/adforest-child`
- دسترسی WP-CLI روی سرور یا محیط staging
- اگر cache یا object cache دارید، امکان flush آن

## مرحله 2: آماده‌سازی فایل‌های GeoNames

از ریشه پروژه این دستور را اجرا کنید:

```powershell
powershell -ExecutionPolicy Bypass -File ".\scripts\prepare-geonames.ps1"
```

اگر دیتاست سبک‌تر یا سنگین‌تر خواستید:

```powershell
powershell -ExecutionPolicy Bypass -File ".\scripts\prepare-geonames.ps1" -CityDataset "cities500.zip"
```

یا:

```powershell
powershell -ExecutionPolicy Bypass -File ".\scripts\prepare-geonames.ps1" -CityDataset "cities5000.zip"
```

خروجی این مرحله:

- `countryInfo.txt`
- فایل city dataset
- `alternateNamesV2.zip`

## مرحله 3: انتقال به محیط وردپرسی

فایل‌های GeoNames را به یک مسیر روی سرور یا staging منتقل کنید.  
مثال:

```text
/var/www/geonames/
```

## مرحله 4: import و seed

روی محیطی که وردپرس و WP-CLI دارد این دستورات را اجرا کنید:

```bash
wp bornado-geo import-countries "/var/www/geonames/countryInfo.txt"
wp bornado-geo import-cities "/var/www/geonames/cities1000.zip"
wp bornado-geo import-fa-names "/var/www/geonames/alternateNamesV2.zip"
wp bornado-geo seed-root-countries
```

اگر دیتاست دیگری دانلود کرده‌اید، نام فایل city را مطابق همان تغییر دهید.

## مرحله 5: چک اولیه بعد از import

بعد از import این موارد را بررسی کنید:

1. در wp-admin صفحه `Tools > Bornado Geo Catalog` باز شود.
2. تعداد `Catalog countries` باید پر شده باشد.
3. تعداد `Catalog cities` باید پر شده باشد.
4. تعداد `Root ad_country terms` باید پر شده باشد.
5. در taxonomy `ad_country` باید countryهای root وجود داشته باشند.
6. هنوز لازم نیست city termهای زیادی داخل `ad_country` ببینید؛ شهرها قرار است lazy ساخته شوند.

## مرحله 6: چک ارزها

فایل:

```text
adforest-child/bornado-geo-currency-overrides.php
```

را فقط در صورتی بازبینی کنید که taxonomy `ad_currency` شما نام‌گذاری خاص داشته باشد.

اگر همه چیز درست باشد:

- برای اکثر کشورها ارز باید خودکار resolve شود.
- اگر بعضی کشورها currency نگرفتند، alias مناسب را به همان فایل اضافه کنید.

## مرحله 7: تست سناریوی اصلی در حالت Auto Approved

پیش‌فرض:

- تایید آگهی روی `auto approved`

مراحل تست:

1. به صفحه درج آگهی بروید.
2. یک کشور انتخاب کنید.
3. یک شهری انتخاب کنید که هنوز مطمئنید در `ad_country` وجود ندارد.
4. بقیه فیلدهای لازم را پر کنید.
5. آگهی را ثبت کنید.

نتیجه‌ی مورد انتظار:

- آگهی مستقیم `publish` شود.
- country root term از قبل وجود داشته باشد یا resolve شود.
- city term بعد از publish ساخته شود.
- آگهی به country و city درست attach شود.
- `_adforest_ad_location` خالی نماند.
- اگر map coordinates خالی بودند، از city catalog پر شوند.
- currency از country root اعمال شود.
- phone sync خراب نشود.

## مرحله 8: تست سناریوی Manual Approved

پیش‌فرض:

- تایید آگهی را روی `manual`

مراحل تست:

1. یک آگهی جدید با country/city جدید ثبت کنید.
2. آگهی باید `pending` شود.
3. قبل از publish شدن، taxonomy `ad_country` را بررسی کنید.
4. سپس از wp-admin آگهی را `publish` کنید.

نتیجه‌ی مورد انتظار:

- هنگام `pending`، city term نباید به‌زور ساخته شده باشد.
- بعد از publish واقعی، city term ساخته شود.
- آگهی به termهای location درست attach شود.
- route و نمایش location بعد از publish درست باشد.

## مرحله 9: تست publish بعد از پرداخت

اگر Pay Per Post یا publish بعد از پرداخت دارید:

1. یک آگهی با country/city جدید ثبت کنید.
2. آگهی نباید همان لحظه city term نهایی را بسازد مگر واقعا publish شده باشد.
3. پرداخت را کامل کنید یا flow کامل publish بعد از پرداخت را اجرا کنید.

نتیجه‌ی مورد انتظار:

- city term فقط بعد از publish نهایی ساخته شود.
- قبل از publish نهایی، فقط pending selection ذخیره شده باشد.

## مرحله 10: تست تغییر location در ویرایش آگهی

1. یک آگهی published را باز کنید.
2. country/city آن را به location دیگری تغییر دهید.
3. ذخیره کنید.

نتیجه‌ی مورد انتظار:

- اگر policy فعلی باعث شود آگهی published بماند، sync جدید باید درست اعمال شود.
- اگر policy شما در update آن را pending کند، sync نهایی باید بعد از publish مجدد انجام شود.
- termهای قدیمی نباید به‌صورت نادرست باقی بمانند.

## مرحله 11: تست Search / Display / Routing

بعد از publish شدن چند آگهی با locationهای جدید این‌ها را بررسی کنید:

1. location روی کارت آگهی درست نمایش داده شود.
2. location روی single ad درست نمایش داده شود.
3. breadcrumb درست باشد.
4. `country_id` filtering درست کار کند.
5. picker / widgetهای location به مشکل نخورند.
6. اگر routing semantic فعال است، pageهای location درست resolve شوند.

## مرحله 12: تست Phone و Currency

برای هر کشور تستی این موارد را چک کنید:

1. phone number با کد کشور درست normalize شود.
2. currency term مناسب روی آگهی بنشیند.
3. `_adforest_ad_currency` با taxonomy currency هم‌خوان باشد.

اگر currency درست resolve نشد:

- alias آن کشور/ارز را در `bornado-geo-currency-overrides.php` اضافه کنید.

## مرحله 13: متاهایی که بد نیست بررسی شوند

اگر خواستید از نزدیک صحت فرآیند را بررسی کنید، این متاها مهم هستند:

- `_bornado_geo_pending_selection`
- `_bornado_geo_pending_hash`
- `_bornado_geo_synced_hash`
- `_bornado_geo_synced_at`
- `_adforest_ad_location`
- `_adforest_ad_currency`
- `_adforest_poster_contact`

## مرحله 14: علائم درست‌بودن سیستم

اگر این‌ها برقرار باشند، سیستم درست کار می‌کند:

- فرم درج آگهی location جهانی نشان می‌دهد.
- کاربر فقط از دیتاست مرجع انتخاب می‌کند.
- taxonomy `ad_country` از قبل با همه شهرها پر نشده است.
- city فقط بعد از publish واقعی ساخته می‌شود.
- auto approved و manual approved هر دو درست کار می‌کنند.
- search/display/routing نشکسته‌اند.
- currency و phone با country هم‌راستا هستند.

## مرحله 15: علائم خرابی یا نیاز به بررسی

اگر یکی از این موارد رخ داد، باید بررسی کنید:

- country در فرم می‌آید ولی city نمی‌آید
- آگهی publish می‌شود ولی city term ساخته نمی‌شود
- city term ساخته می‌شود ولی به آگهی attach نمی‌شود
- currency خالی می‌ماند
- phone normalization باعث pending ناخواسته می‌شود
- location روی search card یا single ad خالی/اشتباه است

## مرحله 16: اگر بخواهید تست را خیلی سریع انجام دهید

حداقل تست پیشنهادی:

1. import کامل
2. seed root countries
3. یک آگهی در حالت auto approve
4. یک آگهی در حالت manual approve
5. یک آگهی در flow پرداخت
6. یک ویرایش location روی آگهی موجود
7. یک بررسی روی search و single ad

اگر هر 7 مورد بالا درست بود، سیستم از نظر عملیاتی در وضعیت خوبی است.

## مرحله 17: ترتیب پیشنهادی اجرای واقعی

بهترین ترتیب برای اجرا:

1. staging
2. import GeoNames
3. seed root countries
4. smoke test کامل
5. بررسی ارزها
6. بررسی phone normalization
7. بررسی routing/search/display
8. سپس deploy روی production

## فایل راهنمای مکمل

برای setup دیتاست و دستورات پایه import این فایل را هم ببینید:

```text
BORNADO-GEONAMES-SETUP.md
```

