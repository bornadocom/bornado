# Bornado Post-Update URL Checklist

## هدف
این چک‌لیست برای هر آپدیت AdForest، child theme، یا ماژول‌های Bornado استفاده می‌شود تا مطمئن شویم:

- URLهای semantic هنوز فعال هستند
- کلیک روی دسته‌ها به `?cat_id=` برنمی‌گردد
- canonical و route context هنوز درست کار می‌کنند
- هیچ تغییری در core قالب لازم نشده است

## 1) بررسی bootstrap
- مطمئن شوید loader در `My-Customization/bornado-search-core/mu-plugin-loader.php` هنوز `bornado-routing` و `bornado-search-core` را load می‌کند.
- مطمئن شوید فایل `My-Customization/bornado-routing/bornado-routing.php` بدون خطای syntax روی سرور deploy شده است.
- مطمئن شوید `sb_search_page` در تنظیمات AdForest هنوز روی صفحه جستجوی صحیح تنظیم است.

## 2) بررسی URLهای پایه
این مسیرها را مستقیما باز کنید:

- `/uk/`
- `/uk/london/`
- `/uk/property/`
- `/uk/london/property/`
- `/jobs/`
- `/jobs/page/2/`

انتظار:

- 404 نشوند
- به attachment/media redirect نشوند
- canonical به همان URL semantic اشاره کند

## 3) بررسی کلیک دسته‌ها
در صفحات search و landing این موارد را تست کنید:

- کلیک روی دسته در سایدبار
- کلیک روی دسته در map widget
- کلیک روی دسته در search-map dropdown

انتظار:

- navigation با URL semantic انجام شود
- نوار آدرس به `?cat_id=<id>` تغییر نکند
- اگر route semantic قابل ساخت نیست، فقط fallback داخلی رخ دهد و لینک عمومی شکسته نشود

## 4) بررسی back navigation دسته‌ها
وقتی داخل sub-category هستید:

- دکمه `Back to Parent`
- دکمه `Back to All Categories`

انتظار:

- به مسیر semantic برگردند
- query string قدیمی برای category navigation استفاده نشود

## 5) بررسی فرم‌ها و فیلترها
در یک route semantic مثل `/uk/london/property/` موارد زیر را تست کنید:

- submit فرم جستجو
- تغییر sort
- حذف tag/filter

انتظار:

- category/location ساختاری داخل query عمومی leak نشوند
- حذف آخرین tag آدرس را به `?` خالی نبرد
- filterهای غیرساختاری مثل `sort` یا `keyword` در query باقی بمانند

## 6) بررسی redirect و canonical
این URLها را چک کنید:

- `/?country_id=<term_id>&cat_id=<term_id>`
- `/ad_country/<slug>/`
- `/ad_cats/<slug>/` یا taxonomy archive قدیمی

انتظار:

- اگر mapping معتبر است، به URL semantic نهایی 301 شوند
- canonical روی نسخه semantic بایستد

## 7) بررسی debug
روی یک URL semantic این پارامتر را اضافه کنید:

- `?bornado_debug_route=1`

در response header انتظار می‌رود:

- `X-Bornado-Route-Status: valid`
- `X-Bornado-Route-Mode` متناسب با route
- `X-Bornado-Route-Category` یا `X-Bornado-Route-City` در صورت وجود

## 8) در صورت fail
اگر هر موردی دوباره query-based شد:

1. ابتدا loader و deploy فایل‌های `bornado-routing` و `bornado-search-core` را چک کنید.
2. سپس `sb_search_page` و rewrite flush را بررسی کنید.
3. بعد روی HTML همان widget بررسی کنید که آیا لینک semantic در `href`/`data-target-url` رندر شده یا نه.
4. در آخر JS مربوط به Search 2.0 را چک کنید که navigation را دوباره hijack نکرده باشد.
