# تمپلیت واتس‌اپ برای انتشار آگهی

## نام پیشنهادی

`listing_published_manage_fa_v2`

## دسته‌بندی

`Utility`

## زبان

`Persian`

در تنظیمات فنی معمولا language code به‌صورت `fa` استفاده می‌شود.

## هدر پیشنهادی

Header type: `Image`

Image URL:

```text
https://bornado.com/wp-content/uploads/2026/05/Bornado.png
```

## بادی پیشنهادی

```text
آگهی شما با عنوان «{{listing_title}}» منتشر شد.

شما می‌توانید کاملا رایگان آگهی خود را ویرایش یا حذف کنید و یا تصاویر مورد نظر خود را به آن اضافه کنید. همچنین می‌توانید در صورت تمایل آگهی‌های دیگر خود را کاملا رایگان در Bornado درج کنید.
```

## فوتر پیشنهادی

```text
برنادو - وب سایت نیازمندی های ایرانیان خارج از کشور- Bornado
```

## دکمه

- Button type: `Visit Website`
- Button text: `مدیریت آگهی`
- URL prefix:

```text
https://bornado.com/notification-continue.php?t=
```

دکمه باید به‌صورت dynamic URL ساخته شود تا suffix را از سیستم بگیرد.

## پارامترها

- Header image ثابت = `https://bornado.com/wp-content/uploads/2026/05/Bornado.png`
- Body `{{listing_title}}` = `payload.listing.title`
- Button URL `{{1}}` = `payload.listing.continueToken`

## تنظیم config بعد از approval

در فایل `Services/bornado-notification-platform/config/notification-platform.local.php` برای `listing.published` این ساختار را بگذار:

```php
'listing.published' => array(
    'name'            => 'listing_published_manage_fa_v2',
    'language_code'   => 'fa',
    'header_media'    => array(
        'type' => 'image',
        'link' => 'https://bornado.com/wp-content/uploads/2026/05/Bornado.png',
    ),
    'body_parameters' => array(
        array(
            'name' => 'listing_title',
            'path' => 'payload.listing.title',
        ),
    ),
    'button_parameters' => array(
        array(
            'sub_type'   => 'url',
            'index'      => 0,
            'parameters' => array(
                'payload.listing.continueToken',
            ),
        ),
    ),
),
```

در این نسخه، header از نوع image ثابت است، متغیر body به‌صورت named (`{{listing_title}}`) و متغیر دکمه URL به‌صورت positional (`{{1}}`) تعریف شده است.
