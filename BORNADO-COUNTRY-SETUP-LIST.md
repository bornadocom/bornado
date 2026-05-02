# لیست نهایی تنظیم کشورها برای Bornado

## هدف فایل
این فایل لیست production-ready کشورها را برای ساخت country root termها در `ad_country` آماده می کند.

این لیست برای همین معماری فعلی Bornado طراحی شده است:

- single-site
- country-first routing
- country root term در `ad_country`
- city termها به عنوان child زیر کشور

## نکته مهم
در پنل وردپرس:

- `slug` را در فیلد `Slug` وارد کنید
- `display_name_fa` را در فیلد `Name` وارد کنید
- `country_code` را در فیلد `Country Code` وارد کنید
- `display_name_en` را در فیلد `English Display Name` وارد کنید
- `market_status` را در فیلد `Market Status` وارد کنید

## قاعده استفاده
- فقط برای **country root term**ها از این لیست استفاده کنید
- برای cityها فقط `Name` و `Slug` را بسازید و آن ها را child همان country قرار دهید
- `market_status` در این فایل بر اساس tiering فعلی Bornado پیشنهاد شده است

## لیست نهایی

| slug | country_code | display_name_en | display_name_fa | market_status |
| --- | --- | --- | --- | --- |
| `uk` | `GB` | `United Kingdom` | `بریتانیا` | `tier1` |
| `ca` | `CA` | `Canada` | `کانادا` | `tier1` |
| `us` | `US` | `United States` | `ایالات متحده` | `tier1` |
| `de` | `DE` | `Germany` | `آلمان` | `tier2` |
| `au` | `AU` | `Australia` | `استرالیا` | `tier2` |
| `ae` | `AE` | `United Arab Emirates` | `امارات متحده عربی` | `tier2` |
| `tr` | `TR` | `Turkey` | `ترکیه` | `tier3` |
| `nl` | `NL` | `Netherlands` | `هلند` | `tier3` |
| `se` | `SE` | `Sweden` | `سوئد` | `tier3` |
| `at` | `AT` | `Austria` | `اتریش` | `tier3` |
| `fr` | `FR` | `France` | `فرانسه` | `tier3` |
| `no` | `NO` | `Norway` | `نروژ` | `tier3` |
| `dk` | `DK` | `Denmark` | `دانمارک` | `tier3` |
| `ie` | `IE` | `Ireland` | `ایرلند` | `tier3` |
| `it` | `IT` | `Italy` | `ایتالیا` | `tier3` |
| `ch` | `CH` | `Switzerland` | `سوئیس` | `tier3` |
| `be` | `BE` | `Belgium` | `بلژیک` | `tier3` |
| `nz` | `NZ` | `New Zealand` | `نیوزیلند` | `tier3` |

## پیشنهاد شروع عملی
اگر بخواهید خیلی تمیز و مرحله ای شروع کنید، ابتدا فقط این 6 کشور را بسازید:

- `uk`
- `ca`
- `us`
- `de`
- `au`
- `ae`

بعد از آن، کشورهای `tier3` را به عنوان data-model coverage اضافه کنید.

## یادداشت اجرایی
- برای UK از `slug = uk` و `country_code = GB` استفاده کنید
- England / Scotland / Wales را در این فاز country root نسازید
- آن ها باید به صورت city/region strategy زیر market بریتانیا مدیریت شوند، نه market مستقل

## نمونه ورود در پنل

### Example: United Kingdom
- `slug`: `uk`
- `country_code`: `GB`
- `display_name_en`: `United Kingdom`
- `display_name_fa`: `بریتانیا`
- `market_status`: `tier1`

### Example: Canada
- `slug`: `ca`
- `country_code`: `CA`
- `display_name_en`: `Canada`
- `display_name_fa`: `کانادا`
- `market_status`: `tier1`

### Example: Germany
- `slug`: `de`
- `country_code`: `DE`
- `display_name_en`: `Germany`
- `display_name_fa`: `آلمان`
- `market_status`: `tier2`
