<?php

return [
    'name' => 'المهمة المجدولة',
    'description' => 'قم بإعداد المهام الخلفية التلقائية للحفاظ على تشغيل موقعك بسلاسة.',
    'is_not_ready' => 'لم يتم تكوين المهمة المجدولة بعد',
    'is_not_ready_description' => 'يرجى اتباع التعليمات أدناه لإعداد المهمة المجدولة. هذا مطلوب لميزات مثل تذكيرات سلة التسوق المهجورة وجدولة البريد الإلكتروني والمهام التلقائية الأخرى.',
    'is_working' => 'المهمة المجدولة تعمل بشكل صحيح!',
    'is_not_working' => 'توقفت المهمة المجدولة عن العمل',
    'is_not_working_description' => 'لم يتم تشغيل المهمة المجدولة خلال آخر 10 دقائق. يرجى التحقق من إعدادات الخادم أو الاتصال بمزود الاستضافة.',
    'last_checked' => 'آخر نشاط: :time',
    'copy_button' => 'نسخ الأمر',
    'what_is' => [
        'title' => 'ما هي المهمة المجدولة؟',
        'description' => 'المهمة المجدولة هي مهمة تلقائية تعمل في الخلفية على خادمك. تتيح لموقعك تنفيذ المهام المهمة تلقائيًا دون الحاجة إلى القيام بأي شيء يدويًا.',
        'examples' => 'أمثلة',
        'features' => 'إرسال تذكيرات سلة التسوق المهجورة، ومعالجة رسائل البريد المجدولة، وتنظيف البيانات القديمة، وإنشاء التقارير، والمزيد.',
    ],
    'command' => [
        'title' => 'أمر المهمة المجدولة الخاص بك',
        'description' => 'انسخ هذا الأمر وأضفه إلى لوحة تحكم الاستضافة. يجب تشغيل هذا الأمر كل دقيقة للحفاظ على عمل مهامك التلقائية.',
    ],
    'setup' => [
        'name' => 'طريقة الإعداد',
        'copied' => 'تم النسخ إلى الحافظة!',
        'choose_hosting' => 'اختر لوحة تحكم الاستضافة أدناه واتبع التعليمات خطوة بخطوة:',
    ],
    'cpanel' => [
        'step1' => 'سجّل الدخول إلى حساب <strong>cPanel</strong> الخاص بك',
        'step2' => 'ابحث عن <strong>Cron Jobs</strong> في قسم Advanced وانقر عليه',
        'step3' => 'ضمن Add New Cron Job، اختر <strong>Once Per Minute (* * * * *)</strong> من القائمة المنسدلة للتوقيت',
        'step4' => '<strong>الصق الأمر</strong> الذي نسخته أعلاه في حقل Command',
        'step5' => 'انقر على <strong>Add New Cron Job</strong> للحفظ',
    ],
    'plesk' => [
        'step1' => 'سجّل الدخول إلى لوحة تحكم <strong>Plesk</strong> الخاصة بك',
        'step2' => 'انتقل إلى <strong>Scheduled Tasks</strong> (أو Cron Jobs)',
        'step3' => 'انقر على <strong>Add Task</strong> أو <strong>Schedule a Task</strong>',
        'step4' => 'اضبط الجدول الزمني للتشغيل <strong>كل دقيقة</strong> والصق الأمر',
        'step5' => 'انقر على <strong>OK</strong> أو <strong>Apply</strong> للحفظ',
    ],
    'directadmin' => [
        'step1' => 'سجّل الدخول إلى لوحة <strong>DirectAdmin</strong> الخاصة بك',
        'step2' => 'انتقل إلى <strong>Advanced Features</strong> ← <strong>Cron Jobs</strong>',
        'step3' => 'انقر على <strong>Add Cron Job</strong>',
        'step4' => 'اضبط جميع حقول الوقت على <strong>*</strong> (كل دقيقة) والصق الأمر',
        'step5' => 'انقر على <strong>Add</strong> لحفظ المهمة المجدولة',
    ],
    'ssh' => [
        'step1' => 'اتصل بخادمك عبر <strong>SSH</strong> باستخدام Terminal أو PuTTY',
        'step2' => 'اكتب <code>crontab -e</code> واضغط Enter لتعديل ملف crontab',
        'step3' => 'أضف سطرًا جديدًا في الأسفل و<strong>الصق الأمر</strong>',
        'step4' => 'اضغط <strong>Ctrl+X</strong>، ثم <strong>Y</strong>، ثم <strong>Enter</strong> للحفظ (لمحرر nano)',
        'step5' => 'المهمة المجدولة نشطة الآن وستعمل كل دقيقة',
    ],
    'need_help' => 'هل تحتاج إلى مساعدة؟ اتصل بمزود الاستضافة واطلب منه إعداد مهمة مجدولة تعمل كل دقيقة باستخدام الأمر الموضح أعلاه.',
];
