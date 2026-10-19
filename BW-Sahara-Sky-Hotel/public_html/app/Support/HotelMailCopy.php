<?php

namespace App\Support;

/** Wording of the hotel's account emails in the three site languages. Unknown languages fall back to English. */
class HotelMailCopy
{
    public const LOCALES = ['en', 'ar', 'zh'];

    public static function locale(?string $locale): string
    {
        $locale = strtolower((string) $locale);

        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    public static function dir(string $locale): string
    {
        return $locale === 'ar' ? 'rtl' : 'ltr';
    }

    /** @return array<string,string> */
    public static function get(string $locale): array
    {
        return self::STRINGS[self::locale($locale)];
    }

    private const STRINGS = [
        'en' => [
            'brand' => 'B&W SAHARA SKY',
            'tagline' => 'THE DESERT COLLECTION',
            'font' => "Georgia,'Times New Roman',serif",
            'font_body' => "'Segoe UI',Helvetica,Arial,sans-serif",
            'code_subject' => 'Your B&W Sahara Sky sign-in code: :code',
            'code_preheader' => 'Your sign-in code is :code. It expires in :minutes minutes.',
            'code_title' => 'Your sign-in code',
            'code_intro' => 'Use this code to sign in to your B&W Sahara Sky account and continue your booking.',
            'code_label' => 'ONE-TIME CODE',
            'code_expiry' => 'It expires in :minutes minutes and works once.',
            'code_safety_title' => 'Keep it private',
            'code_safety' => 'The hotel will never ask you for this code. If you did not try to sign in, you can safely ignore this email; nobody can use the code without access to your inbox.',
            'alert_subject' => 'Security alert: a session was ended · B&W Sahara Sky',
            'alert_preheader' => 'An old sign-in on your account was used again, so we ended that session.',
            'alert_title' => 'We ended a sign-in session',
            'alert_intro' => 'An old sign-in token for your account was used again. That can mean it was copied, so as a precaution we ended that session. Your other sign-in sessions were not changed.',
            'alert_time' => 'Time',
            'alert_ip' => 'IP address',
            'alert_device' => 'Device',
            'alert_next' => 'To continue, sign in again with a new email code. If this was not you, contact the hotel and tell us the details above.',
            'footer_place' => 'Between Bahariya Oasis and Farafra, Egypt',
            'footer_note' => 'This email was sent because someone entered your address on our website.',
            'footer_auto' => 'Automatic message, please do not reply.',
        ],
        'ar' => [
            'brand' => 'B&W SAHARA SKY',
            'tagline' => 'THE DESERT COLLECTION',
            'font' => "Tahoma,'Segoe UI',Arial,sans-serif",
            'font_body' => "Tahoma,'Segoe UI',Arial,sans-serif",
            'code_subject' => 'رمز الدخول إلى حسابك في B&W Sahara Sky: :code',
            'code_preheader' => 'رمز الدخول هو :code وينتهي خلال :minutes دقائق.',
            'code_title' => 'رمز الدخول الخاص بك',
            'code_intro' => 'استخدم هذا الرمز لتسجيل الدخول إلى حسابك في B&W Sahara Sky وإكمال حجزك.',
            'code_label' => 'رمز لمرة واحدة',
            'code_expiry' => 'ينتهي خلال :minutes دقائق ويعمل مرة واحدة فقط.',
            'code_safety_title' => 'حافظ على سريته',
            'code_safety' => 'لن يطلب منك الفندق هذا الرمز أبدًا. إذا لم تحاول تسجيل الدخول فتجاهل هذه الرسالة بأمان، فلا يمكن لأحد استخدام الرمز دون الوصول إلى بريدك.',
            'alert_subject' => 'تنبيه أمني: تم إنهاء جلسة دخول · B&W Sahara Sky',
            'alert_preheader' => 'تم استخدام جلسة دخول قديمة لحسابك مرة أخرى، لذلك أنهينا تلك الجلسة.',
            'alert_title' => 'أنهينا إحدى جلسات الدخول',
            'alert_intro' => 'تم استخدام رمز دخول قديم لحسابك مرة أخرى، وقد يعني هذا أنه نُسخ. احتياطًا أنهينا تلك الجلسة، ولم تتأثر جلسات الدخول الأخرى.',
            'alert_time' => 'الوقت',
            'alert_ip' => 'عنوان IP',
            'alert_device' => 'الجهاز',
            'alert_next' => 'للمتابعة سجّل الدخول مرة أخرى برمز جديد على بريدك. إذا لم تكن أنت فتواصل مع الفندق وأرسل لنا التفاصيل أعلاه.',
            'footer_place' => 'بين واحة البحرية والفرافرة، مصر',
            'footer_note' => 'وصلتك هذه الرسالة لأن أحدًا أدخل بريدك على موقعنا.',
            'footer_auto' => 'رسالة آلية، يرجى عدم الرد.',
        ],
        'zh' => [
            'brand' => 'B&W SAHARA SKY',
            'tagline' => 'THE DESERT COLLECTION',
            'font' => "'Microsoft YaHei','PingFang SC',Arial,sans-serif",
            'font_body' => "'Microsoft YaHei','PingFang SC',Arial,sans-serif",
            'code_subject' => '您的 B&W Sahara Sky 登录验证码：:code',
            'code_preheader' => '您的登录验证码是 :code，:minutes 分钟内有效。',
            'code_title' => '您的登录验证码',
            'code_intro' => '请使用此验证码登录您的 B&W Sahara Sky 账户并继续预订。',
            'code_label' => '一次性验证码',
            'code_expiry' => ':minutes 分钟内有效，且仅可使用一次。',
            'code_safety_title' => '请妥善保管',
            'code_safety' => '酒店不会向您索要此验证码。如果不是您本人在登录，请忽略此邮件；没有您的邮箱权限，任何人都无法使用该验证码。',
            'alert_subject' => '安全提醒：一个登录会话已被结束 · B&W Sahara Sky',
            'alert_preheader' => '您账户的旧登录凭证被再次使用，我们已结束该登录会话。',
            'alert_title' => '我们已结束一个登录会话',
            'alert_intro' => '您账户的一个旧登录凭证被再次使用，这可能意味着它被复制。出于安全考虑，我们已结束该登录会话，您的其他登录会话不受影响。',
            'alert_time' => '时间',
            'alert_ip' => 'IP 地址',
            'alert_device' => '设备',
            'alert_next' => '如需继续，请使用新的邮箱验证码重新登录。如果不是您本人操作，请联系酒店并告知上述信息。',
            'footer_place' => '埃及，巴哈里耶绿洲与法拉夫拉之间',
            'footer_note' => '您收到此邮件，是因为有人在我们的网站上输入了您的邮箱地址。',
            'footer_auto' => '系统自动邮件，请勿回复。',
        ],
    ];
}
