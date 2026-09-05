<div dir="rtl" align="right">

# تأیید نهایی پلن دایرکتوری کسب‌وکار Bornado

تاریخ: ۵ سپتامبر ۲۰۲۶  
سند بررسی‌شده: `docs/business-directory/PLAN.md`، نسخهٔ ۹۶۳ خطی  
دامنه: معماری، انطباق با کد، WordPress، URL، SEO، Schema، AI، داده، امنیت و عملیات

## ۱. حکم نهایی

**بله؛ با نسخهٔ فعلی PLAN و تصمیم‌های اعمال‌شده یا عمداً ردشده ۱۰۰ درصد موافقم.**

آخرین ابهام مربوط به مالکیت تاریخچهٔ slug جغرافیای مشترک به‌صورت صحیح رفع شده است. در سطح PLAN، مخالفت یا تصمیم معماری حل‌نشدهٔ شناخته‌شده‌ای باقی نمانده است.

نتیجه:

- معماری کلان: تأیید
- مدل داده: تأیید
- قرارداد URL و routing: تأیید
- SEO و indexation: تأیید
- Schema و AI discoverability: تأیید
- امنیت و moderation: تأیید
- فازبندی: تأیید
- مجوز شروع فاز صفر: صادر می‌شود
- شروع فاز یک: فقط بعد از تکمیل خروجی‌ها و gateهای فاز صفر

تأیید ۱۰۰ درصدی به معنی کامل‌بودن implementation یا تضمین رتبه در گوگل نیست؛ به معنی کامل، حرفه‌ای، منسجم و قابل اجرای بودن تصمیم‌های PLAN در دامنهٔ تعریف‌شده است.

## ۲. تأیید اصلاح نهایی geo-alias

### ۲.۱ تشخیص درست مسئله

taxonomy زیر میان چند بخش پروژه مشترک است:

```text
ad_country
```

در نتیجه تغییر slug کشور یا شهر فقط دایرکتوری را متاثر نمی‌کند و می‌تواند همزمان این خانواده‌ها را تغییر دهد:

```text
/uk/london/property/
/uk/london/businesses/
/iranians/uk/london/
```

سپردن history جغرافیا فقط به `class-urls.php` دایرکتوری باعث می‌شد URL کسب‌وکار اصلاح شود ولی route آگهی یا geo guide شکسته بماند.

### ۲.۲ مالکیت صحیح

PLAN اکنون مالکیت را درست تفکیک می‌کند:

#### سرویس مرکزی geo-alias

مالک این موارد است:

- تاریخچه slug کشور و شهر در `ad_country`
- aliasهای تاریخی جغرافیا
- جلوگیری از تخصیص دوبارهٔ path تاریخی
- resolve alias پیش از resolve نهایی
- redirect خانواده‌های semantic متاثر
- جلوگیری از redirect chain

#### افزونهٔ `bornado-business-directory`

فقط مالک این historyها است:

- slug خود کسب‌وکار
- `bornado_business_cat`
- تغییر مکان canonical یک رکورد کسب‌وکار
- merge redirect کسب‌وکار

افزونه دایرکتوری نسخهٔ دیگری از geo history ذخیره نمی‌کند.

### ۲.۳ حفظ قرارداد هر خانواده

PLAN به‌درستی مقرر کرده است:

- tail معتبر route حفظ شود.
- query مجاز طبق قرارداد همان خانواده حفظ شود.
- pagination معتبر حفظ شود.
- state نامعتبر با ۳۰۱ عمومی پنهان نشود.
- state نامعتبر طبق قرارداد همان خانواده ۴۰۴ شود.
- مقصد redirect مستقیماً canonical نهایی باشد.

### ۲.۴ پوشش regression

تست تغییر slug شهر اکنون همزمان این سه خانواده را پوشش می‌دهد:

```text
/uk/london/property/
/uk/london/businesses/
/iranians/uk/london/
```

این تست هم در فاز صفر و هم در معیار خروج فاز یک آمده است. بنابراین قرارداد فقط یک توضیح نظری نیست و acceptance قابل اندازه‌گیری دارد.

### ۲.۵ عدم قفل نام کلاس

قفل‌نکردن نام فنی کلاس تصمیم درستی است. PLAN باید owner، invariant و رفتار قابل مشاهده را قفل کند؛ نام کلاس، schema جدول alias و API hook باید در:

```text
integration-contracts.md
```

مشخص شوند.

عبارت «سرویس مرکزی geo-alias» در این مرحله دقیق‌تر از اجبار زودهنگام به پیاده‌سازی داخل خود `bornado-routing` است.

## ۳. تأیید اصلاحات P0 قبلی

### ۳.۱ `service_area`

- `city_list` در فاز یک فقط فکت نمایشی و Schema است.
- عضویت هاب، count، filter، sitemap و canonical فقط از مکان canonical می‌آید.
- meta serialized برای membership query استفاده نمی‌شود.
- کشور از taxonomy مشتق می‌شود و منبع حقیقت دوم ندارد.

### ۳.۲ انتشار اتمیک

- تمام داده ابتدا در draft/pending کامل می‌شود.
- `publish_business()` snapshot کامل را validate می‌کند.
- انتشار خارج از مسیر واحد مسدود می‌شود.
- quality، URL و cache فقط بعد از snapshot معتبر محاسبه می‌شوند.

### ۳.۳ merge

- survivor UID پایدار می‌ماند.
- loser UID reuse نمی‌شود.
- loser tombstone دارد.
- redirect مستقیم و flatten است.
- self-merge و cycle ممنوع است.
- انتقال روابط اتمیک یا rollback‌پذیر است.

جزئیات owner، claim، media و termها به‌درستی به `data-model.md` سپرده شده‌اند.

### ۳.۴ جداسازی pipeline آگهی

- business پیش از `hydrate_route_context()` و lookup لندینگ آگهی جدا می‌شود.
- landing adapter family-aware است.
- دسته از `bornado_business_cat` می‌آید.
- query، template، title، canonical، robots، ItemList و assetهای آگهی bypass می‌شوند.

### ۳.۵ canonical query

- `sort`: noindex و canonical به هاب تمیز
- `q`: noindex و self-canonical به query نرمال‌شده
- هر دو خارج از sitemap
- exact match یکتا می‌تواند پیش از render ریدایرکت شود

### ۳.۶ Schema فکت‌های داخلی

- `operational_status` و `last_verified_at` در HTML می‌آیند.
- property ساختگی ساخته نمی‌شود.
- `dateModified` و `dissolutionDate` سوءاستفاده نمی‌شوند.
- ساعات استثنایی فقط با دادهٔ تاریخی واقعی می‌آید.

## ۴. تأیید اصلاحات P1 و P2

موارد زیر همگی درست و کافی‌اند:

- تفکیک Schema.org validity از Google Local Business eligibility
- الزام `<a href>` واقعی برای pagination
- محدودکردن discovery و cache فضای `q`
- public projection مشترک برای HTML، JSON-LD، REST و map
- `show_in_rest=false` در فاز یک، مگر controller allowlistشده
- invariantهای claim به‌عنوان gate فاز ۱.۵
- staged moderation بدون خارج‌کردن نسخهٔ publish
- triggerهای قطعی cache invalidation
- نرم‌کردن ادعاهای رتبه و citation هوش مصنوعی
- محدودکردن `city_term_ids` به locationهای routeable همان کشور
- idempotency فقط پیش از ساخت importer
- تعریف دامنهٔ چهار سند فاز صفر

## ۵. تأیید موارد عمداً اعمال‌نشده

با اعمال‌نشدن این موارد موافقم:

### جزئیات کامل merge در PLAN

تصمیم انتقال owner، claim، media و termها پاسخ عمومی یکتا ندارد و باید در `data-model.md` قفل شود.

### ماتریس کامل cache داخل PLAN

PLAN triggerهای قطعی را مشخص کرده است؛ ماتریس implementation جای `indexation-rules.md` است.

### invalidation عضویت هاب با تغییر service area

با display-only بودن `city_list` در فاز یک ناسازگار است و نباید انجام شود.

### immutable کردن `ad_country`

taxonomy مشترک کل سایت را بی‌دلیل قفل می‌کند. history و redirect مرکزی انتخاب مناسب‌تری است.

### rollback کامل importer

تا پیش از ورود importer به scope، gate فاز یک نیست.

### ساخت فوری چهار سند

این اسناد خروجی فاز صفر هستند، نه بخشی از اصلاح PLAN.

### قفل نام کلاس و API geo-alias

مالکیت و رفتار اکنون قفل شده‌اند؛ طراحی فنی دقیق باید در integration contract انجام شود.

### سایر موارد غیرالزامی

- `SearchResultsPage`
- registry اختصاصی attribute
- feature flag
- suite کامل تست به‌عنوان شرط تصویب PLAN
- ۴۰۴ برای هر جستجوی صفرنتیجه
- block فوری همهٔ `q`ها در `robots.txt`

هیچ‌کدام برای حرفه‌ای و قابل اجرای بودن این PLAN ضروری نیستند.

## ۶. خروجی‌های لازم فاز صفر

تأیید PLAN جایگزین اجرای این موارد نیست:

1. `url-contract.md`
2. `data-model.md`
3. `indexation-rules.md`
4. `integration-contracts.md`
5. extension contract routing
6. landing adapter
7. POC route و template
8. geo-alias contract
9. audit برخورد slug
10. regression آگهی، دایرکتوری و geo guide

فاز یک فقط بعد از تکمیل و تأیید این خروجی‌ها آغاز می‌شود.

## ۷. نتیجهٔ قطعی

PLAN فعلی:

- از امکانات پروژه بدون بازنویسی غیرضروری استفاده می‌کند.
- مرز دایرکتوری، آگهی و راهنمای جامعه را حفظ می‌کند.
- source of truthهای داده را مشخص کرده است.
- URL، canonical، pagination و indexation قطعی دارد.
- Schema استاندارد و بدون ادعای جعلی می‌سازد.
- برای موتور جستجو و مدل‌های AI به دادهٔ واقعی، پایدار و قابل تأیید تکیه می‌کند.
- حریم خصوصی، claim و moderation را متناسب با فازها تعریف می‌کند.
- ریسک taxonomy جغرافیای مشترک را در سطح همهٔ route familyها حل کرده است.

**حکم نهایی: تأیید ۱۰۰ درصدی PLAN در سطح معماری و قرارداد محصول؛ مجوز شروع فاز صفر؛ بدون پیشنهاد اصلاح دیگری در این ممیزی.**

</div>
