# Bornado GeoNames Setup

این پروژه برای انتخاب جهانی کشور/شهر از یک کاتالوگ self-hosted استفاده می‌کند و فقط پس از `publish` واقعی، termهای `ad_country` را می‌سازد یا sync می‌کند.

## پیش‌نیاز

- روی لوکال شما لازم نیست وردپرس اجرا باشد.
- import واقعی فقط روی محیطی انجام می‌شود که WP-CLI و دیتابیس وردپرس در دسترس باشد.

## آماده‌سازی فایل‌های GeoNames

از ریشه‌ی پروژه این اسکریپت را اجرا کنید:

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

## import روی سرور / محیط وردپرسی

بعد از انتقال فایل‌ها به محیطی که وردپرس و WP-CLI دارد:

```bash
wp bornado-geo import-countries "/path/to/geonames/countryInfo.txt"
wp bornado-geo import-cities "/path/to/geonames/cities1000.zip"
wp bornado-geo import-fa-names "/path/to/geonames/alternateNamesV2.zip"
wp bornado-geo import-city-fa-supplement "/path/to/geonames/city-fa-supplement.sample.csv"   # optional but recommended for gaps
wp bornado-geo seed-root-countries
```

## بعد از import چه اتفاقی می‌افتد

- همه‌ی کشورها در catalog مرجع ذخیره می‌شوند.
- شهرها در catalog مرجع ذخیره می‌شوند.
- نام‌های فارسی از `alternateNamesV2` روی country/city اعمال می‌شوند.
- اگر بعضی شهرها هنوز نام فارسی مناسب نداشته باشند، فایل supplement می‌تواند همان gapها را روی خود catalog تکمیل کند.
- root country termها در `ad_country` ساخته و با metaهای لازم sync می‌شوند.
- city termها هنوز ساخته نمی‌شوند؛ فقط وقتی آگهی واقعا `publish` شود ساخته خواهند شد.

## مکمل نام فارسی شهرها

اگر بعد از `import-fa-names` دیدید بعضی شهرها هنوز نام فارسی خوبی ندارند، از فایل supplement استفاده کنید.

فرمت فایل:

```csv
geoname_id,name_fa
6173331,ونکوور
6167865,تورنتو
5907364,برنابی
```

نکته‌ها:
- کلید اصلی باید `geoname_id` باشد
- `name_fa` باید واقعاً فارسی باشد
- این فایل برای پر کردن gapها است، نه جایگزین کامل `alternateNamesV2`
- نمونه‌ی اولیه در `var/geonames/city-fa-supplement.sample.csv` ساخته می‌شود

## mapping ارز

فایل `adforest-child/bornado-geo-currency-overrides.php` شامل aliasهای پیش‌فرض برای کدهای رایج ارز است.

اگر taxonomy `ad_currency` شما slug/name متفاوتی دارد، همان فایل را می‌توان با aliasهای بیشتر تکمیل کرد.  
مقدار هر override می‌تواند یکی از این‌ها باشد:

- `term_id`
- `slug`
- `name`
- آرایه‌ای از fallbackها

## مسیرهای کد مرتبط

- `adforest-child/bornado-geo-catalog.php`
- `adforest-child/bornado-geo-currency-overrides.php`
- `adforest-child/bornado-global-ad-location.php`
- `adforest-child/assets/js/bornado-global-ad-location.js`
- `adforest-child/assets/css/bornado-global-ad-location.css`

