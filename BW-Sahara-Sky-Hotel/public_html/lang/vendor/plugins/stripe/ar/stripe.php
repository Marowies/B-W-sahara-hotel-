<?php

return [
    'webhook_secret' => 'سر Webhook',
    'webhook_setup_guide' => [
        'title' => 'دليل إعداد Webhook في Stripe',
        'description' => 'اتبع هذه الخطوات لإعداد Webhook في Stripe',
        'step_1_label' => 'تسجيل الدخول إلى لوحة تحكم Stripe',
        'step_1_description' => 'تفضل بزيارة :link وانقر على زر Add Endpoint في قسم Webhooks بتبويب Developers.',
        'step_2_label' => 'حدد الحدث وقم بإعداد نقطة النهاية',
        'step_2_description' => 'حدد حدث payment_intent.succeeded وأدخل الرابط التالي في حقل Endpoint URL: :url',
        'step_3_label' => 'إضافة نقطة النهاية',
        'step_3_description' => 'انقر على زر Add Endpoint لحفظ Webhook.',
        'step_4_label' => 'نسخ توقيع السر',
        'step_4_description' => 'انسخ قيمة Signing Secret من قسم Webhook Details والصقها في حقل Stripe Webhook Secret في قسم Stripe بتبويب Payment في صفحة الإعدادات.',
    ],
    'no_payment_charge' => 'لا توجد رسوم دفع. يُرجى المحاولة مرة أخرى!',
    'payment_failed' => 'فشلت عملية الدفع!',
    'payment_type' => 'نوع الدفع',
];
