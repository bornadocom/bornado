# تمپلیت واتس‌اپ برای انتشار آگهی

## نام پیشنهادی

`listing_published_manage_fa_v1`

## دسته‌بندی

`Utility`

## زبان

`Persian`

در تنظیمات فنی معمولا language code به‌صورت `fa` استفاده می‌شود.

## هدر پیشنهادی

```text
آگهی شما با عنوان «{{1}}» در Bornado منتشر شد.
```

## بادی پیشنهادی

```text
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

- Header `{{1}}` = `payload.listing.title`
- برای Body هیچ پارامتری ارسال نمی‌شود
- دکمه URL parameter = `payload.listing.continueToken`

## تنظیم config بعد از approval

در فایل `Services/bornado-notification-platform/config/notification-platform.local.php` برای `listing.published` این ساختار را بگذار:

```php
'listing.published' => array(
    'name'            => 'listing_published_manage_fa_v1',
    'language_code'   => 'fa',
    'header_parameters' => array(
        'payload.listing.title',
    ),
    'body_parameters' => array(),
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
