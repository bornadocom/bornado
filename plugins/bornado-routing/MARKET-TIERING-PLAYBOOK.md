# پلن Tiering بازارها برای Bornado

## هدف این فایل
این فایل یک روش حرفه ای برای اولویت بندی کشورها در Bornado تعریف می کند تا تصمیم بازار بر پایه «اندازه کشور» یا «حس شخصی» گرفته نشود.

این سند برای فاز 12 ماه آینده طراحی شده است:

- بدون وابستگی جدی به monetization فوری
- با فرض migration به سیستم اختصاصی در ادامه
- و با هدف ساخت marketplace / classified platform برای ایرانیان خارج از ایران

## اصل کلیدی
Bornado نباید همه بازارها را یکسان ببیند.

برای یک classified platform، تفاوت بسیار زیادی وجود دارد بین:

- بازاری که جامعه ایرانی بزرگ، متراکم و دیجیتال-فعال دارد
- بازاری که جامعه ایرانی دارد اما supply و search demand پراکنده است
- بازاری که از نظر تعداد ایرانیان یا intent جستجو هنوز به landing investment مستقل نمی رسد

پس باید از مدل tiering استفاده شود.

## نکته مهم درباره داده جمعیتی
اعداد diaspora بین کشورها کاملا یکدست نیستند، چون definitionها متفاوت اند:

- Iranian-born
- Iranian ancestry
- ethnic / cultural origin
- population with migration background

بنابراین این tiering باید به عنوان **تصمیم استراتژیک و عملیاتی** فهمیده شود، نه یک census table قطعی.

در این فایل، از جدیدترین سیگنال های در دسترس و نسبتاً معتبر استفاده شده است، از جمله:

- UK: حدود 114k Iranian-born بر اساس census 2021/22
- Canada: حدود 213k Iranian-born و حدود 281k ethnic/cultural origin در census 2021
- Germany: حدود 324k people with Iranian migrant background بر اساس Destatis 2024
- Australia: حدود 85.8k Iranian-born بر اساس داده های 2023 Home Affairs / ABS
- US: حدود 750k Iranian Americans بر اساس تحلیل Pew روی ACS 2024

برای برخی بازارها مثل UAE، داده های رسمی nationality breakdown کامل نیست و باید بیشتر با first-party signals تصمیم گرفت.

## مدل امتیازدهی پیشنهادی
هر بازار با 5 مولفه ارزیابی شود:

1. **اندازه جامعه ایرانی**  
   وزن: 30

2. **تراکم inventory بالقوه**  
   وزن: 25

3. **قدرت demand جستجو و سئو**  
   وزن: 20

4. **سادگی عملیات و moderation**  
   وزن: 15

5. **ارزش برند و trust برای Bornado**  
   وزن: 10

امتیاز نهایی از 100 محاسبه می شود.

## تعریف Tierها

### Tier 1
بازارهایی که باید:

- hub country مستقل داشته باشند
- city hubهای اصلی داشته باشند
- money pageهای country/city/category برایشان ساخته شود
- موجودی، محتوای editorial و internal linking روی آن ها متمرکز شود

### Tier 2
بازارهایی که:

- country hub داشته باشند
- فقط روی چند city/category منتخب برایشان landing ساخته شود
- investment محتوایی و indexing آن ها محافظه کارانه باشد

### Tier 3
بازارهایی که:

- در مدل داده و taxonomy حضور دارند
- اما SEO expansion مستقل و سنگین برایشان انجام نمی شود
- تا زمانی که first-party data آن ها را بالا نیاورد، بیشتر در حالت aggregation یا noindex selective می مانند

## توصیه Bornado برای 12 ماه آینده

### Tier 1: بازارهای اصلی

#### 1) United Kingdom
چرا Tier 1:

- شما همین حالا در این market context بیشتری دارید
- جامعه ایرانی شناخته شده و از نظر شهری متمرکز است
- URL strategy و examples فعلی شما با UK already aligned است
- برای فارسی + انگلیسیِ product language شروع مناسبی است
- از نظر trust و local business intent برای classifieds market بسیار خوب است

خروجی پیشنهادی:

- country hub کامل
- city hub برای London, Manchester, Birmingham و بسته به supply شهرهای بعدی
- pillarهای category و money pageهای city-category

#### 2) Canada
چرا Tier 1:

- یکی از بزرگ ترین و پایدارترین جوامع ایرانی خارج از ایران را دارد
- توزیع شهری برای classifieds مفید است: GTA, Vancouver, Montreal, Ottawa
- برای فارسی زبانان مهاجر، intentهای jobs, services, business, community و property قوی است
- از نظر product-market fit برای برد بلند بسیار مناسب است

خروجی پیشنهادی:

- country hub
- city hub برای Toronto / Richmond Hill corridor, Vancouver, Montreal
- category priority روی services, jobs, businesses, property

#### 3) United States
چرا Tier 1:

- بزرگ ترین بازار diaspora از نظر scale است
- concentration شهری بسیار ارزشمند دارد: Los Angeles, Orange County, Bay Area, Washington area
- اگرچه market بزرگ تر و از نظر content governance سخت تر است، اما از نظر audience size نادیده گرفتنش اشتباه است

نکته اجرایی:

- برای 12 ماه اول نباید تمام US را یکجا expand کنید
- باید با 2 تا 3 metro-first cluster شروع شود

## Tier 2: بازارهای مهم اما مرحله بعد

### 4) Germany
چرا Tier 2:

- جامعه ایرانی بزرگ و معتبر دارد
- برای jobs, professional services, migration services و community directories مناسب است
- اما اگر experience سایت عمدتا فارسی/انگلیسی بماند، بخشی از trust و mainstream SEO market از دست می رود

نتیجه:

- market مهم است
- اما بهتر است بعد از UK/Canada/US یا همزمان فقط در scale محدود فعال شود

### 5) Australia
چرا Tier 2:

- جامعه ایرانی با رشد خوب و توزیع نسبتا متمرکز دارد
- Sydney و Melbourne برای launch کافی اند
- market از نظر عملیات و زبان manageable است

چرا فعلا Tier 1 نیست:

- scale آن از UK/Canada/US کمتر است
- اگر ظرفیت تیم محدود باشد، ورود زودهنگام به Australia بازده کمتری از Canada یا US می دهد

### 6) United Arab Emirates
چرا Tier 2:

- concentration جمعیتی و اقتصادی مهمی دارد
- برای business listings, services, real estate, logistics و community demand می تواند بسیار قوی باشد

چرا فعلا Tier 1 نیست:

- data reliability کمتر است
- مدل تقاضا، residency patterns و lifecycle کاربران نسبت به UK/Canada شفافیت کمتری دارد
- بهتر است قبل از expansion سنگین، با first-party supply / search signals اعتبارسنجی شود

## Tier 3: بازارهای نگه داری شده در مدل، نه در SEO expansion

نمونه بازارهای مناسب Tier 3:

- Turkey
- Netherlands
- Sweden
- Austria
- France
- Norway
- Denmark

منطق:

- در بعضی از این کشورها جامعه ایرانی خوب است
- اما برای 12 ماه آینده، احتمالاً inventory و editorial coverage به حد country program مستقل نمی رسد
- این بازارها باید در taxonomy و data model حضور داشته باشند، اما نه لزوماً در roadmap سئویی اصلی

## قاعده ارتقا یا تنزل Tier
Tiering نباید static باشد.

هر market باید با 4 سیگنال first-party هر ماه یا هر فصل بازبینی شود:

1. تعداد آگهی های فعال
2. تعداد کاربر فعال/ثبت نام/بازگشتی از آن کشور
3. impressions و clicks ارگانیک از آن کشور
4. تعداد queryهای جستجوی با intent شهری/دسته ای

### Rule پیشنهادی برای ارتقا به Tier 1
اگر یک country این شرایط را همزمان داشت، candidate ارتقا به Tier 1 است:

- حداقل 3 city cluster با supply واقعی
- حداقل 2 category با demand و conversion خوب
- trend پایدار impressions یا direct traffic
- توان تولید محتوای local و moderation برای آن market

### Rule پیشنهادی برای ماندن در Tier 2
- market وعده دار است
- اما هنوز شهرهای کافی یا editorial depth کافی ندارد

### Rule پیشنهادی برای Tier 3
- وجود country در taxonomy لازم است
- اما ساخت landingهای indexable برای آن market هنوز premature است

## تمرکز category به تفکیک Tier

### Tier 1
روی این ها از ابتدا تمرکز شود:

- services
- jobs
- businesses
- property

### Tier 2
در شروع:

- services
- jobs
- selected local business listings

### Tier 3
فعلا:

- فقط aggregation
- بدون ساخت تعداد زیاد landing indexable

## ترتیب عملیاتی پیشنهادی

### مسیر پیشنهادی A
اگر هدف شما کمترین complexity و بیشترین leverage باشد:

1. UK
2. Canada
3. US
4. Germany
5. Australia

### مسیر پیشنهادی B
اگر بخواهید بیشتر روی بازارهای انگلیسی زبان بمانید:

1. UK
2. Canada
3. US
4. Australia
5. Germany

## مهم ترین اشتباهاتی که باید اجتناب شوند

### اشتباه 1
کشور را فقط چون «ایرانی دارد» وارد roadmap اصلی کنید.

### اشتباه 2
برای هر market از همان ابتدا city/category pageهای زیاد بسازید.

### اشتباه 3
Tiering را فقط با population size تعیین کنید و inventory / search demand را نادیده بگیرید.

### اشتباه 4
یک market را Tier 1 بنامید ولی برای آن:

- city hubs
- landing editorial
- internal linking
- moderation

تدارک واقعی نداشته باشید.

## جمع بندی نهایی
برای Bornado در 12 ماه آینده:

- **Tier 1**: UK, Canada, US
- **Tier 2**: Germany, Australia, UAE
- **Tier 3**: سایر بازارهای diaspora که باید در مدل داده حضور داشته باشند اما نه در expansion سئویی اصلی

اگر بخواهیم تصمیم را حتی ساده تر کنیم:

**برای Bornado، اول UK را عمیق کنید، بعد Canada را باز کنید، بعد US را metro-first اضافه کنید.**
