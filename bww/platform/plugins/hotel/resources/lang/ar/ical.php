<?php

return [
    'name' => 'مزامنة iCal',
    'sync_calendars' => 'مزامنة التقويمات',
    'sync_all_calendars' => 'مزامنة جميع التقويمات',
    'external_calendars' => 'التقويمات الخارجية',
    'last_sync' => 'آخر مزامنة',
    'never' => 'أبدًا',
    'sync' => 'مزامنة',
    'sync_all' => 'مزامنة الكل',
    'download' => 'تحميل',
    'edit' => 'تعديل iCal لـ :name',

    // Statistics
    'total_rooms' => 'إجمالي الغرف',
    'synced_calendars' => 'التقويمات المتزامنة',
    'rooms_with_calendars' => 'الغرف التي تحتوي على تقاويم',
    'last_sync_time' => 'آخر وقت مزامنة',

    // Table columns
    'calendars' => 'التقويمات',
    'status' => 'الحالة',
    'not_applicable' => 'غير متاح',

    // Status badges
    'synced' => 'متزامن',
    'needs_sync' => 'يحتاج إلى مزامنة',
    'not_synced' => 'غير متزامن',
    'no_calendars' => 'لا توجد تقاويم',

    // Empty states
    'no_rooms_found' => 'لم يتم العثور على غرف',
    'no_rooms_desc' => 'أنشئ غرفة أولاً لبدء إدارة مزامنة التقويم.',

    // Actions
    'manage_calendars' => 'إدارة التقاويم',

    // Export calendar
    'export_calendar_title' => 'تصدير موجز التقويم',
    'copy_url_instruction' => 'انسخ هذا الرابط والصقه في منصة الحجز الخارجية الخاصة بك (Airbnb، Booking.com، إلخ)',
    'how_to_export' => 'كيفية التصدير؟',

    // Empty states for calendars
    'no_external_calendars' => 'لا توجد تقاويم خارجية مهيأة',
    'add_calendar_desc' => 'أضف رابط تقويم خارجي لبدء استيراد الحجوزات من المنصات الأخرى.',

    'calendar_name' => 'اسم التقويم',
    'calendar_name_placeholder' => 'أدخل اسم التقويم',
    'calendar_name_help' => 'أعط هذا التقويم اسمًا يسهل التعرف عليه (مثل "Booking.com" أو "Airbnb")',
    'calendar_url' => 'رابط التقويم',
    'calendar_url_placeholder' => 'أدخل رابط التقويم (على سبيل المثال من Booking.com أو Airbnb أو غيرها)',
    'calendar_url_help' => 'الصق رابط موجز iCal من منصة الحجز الخارجية الخاصة بك',

    // Form instructions
    'add_external_calendar_title' => 'إضافة تقويم خارجي',
    'add_external_calendar_desc' => 'استورد الحجوزات من المنصات الخارجية لمنع الحجز المزدوج والحفاظ على التوفر متزامنًا.',
    'supported_platforms' => 'المنصات المدعومة',
    'and_more' => 'والمزيد',
    'benefits_title' => 'الفوائد',
    'benefit_1' => 'منع الحجز المزدوج تلقائيًا',
    'benefit_2' => 'الحفاظ على التوفر متزامنًا عبر المنصات',
    'benefit_3' => 'توفير الوقت مع التحديثات التلقائية',
    'quick_tip' => 'نصيحة سريعة',
    'tip_desc' => 'يمكنك إضافة عدة موجزات تقويم من منصات مختلفة لنفس الغرفة.',
    'view_guide' => 'عرض الدليل خطوة بخطوة',
    'existing_calendars' => 'التقويمات الموجودة',
    'export_calendar' => 'تصدير التقويم',
    'export_calendar_to_platforms' => 'التصدير إلى المنصات الخارجية',
    'export_url' => 'رابط التصدير',
    'export_description' => 'استخدم هذا الرابط لتصدير حجوزاتك إلى المنصات الخارجية مثل Booking.com أو Airbnb أو غيرها.',
    'download_ical_file' => 'تحميل ملف iCal',
    'sync_success' => 'تمت مزامنة :success تقويم/تقاويم بنجاح. فشل: :failed',
    'sync_all_success' => 'تمت مزامنة :success تقويم/تقاويم بنجاح. فشل: :failed',

    // Logs
    'logs' => 'سجلات مزامنة iCal',
    'log_details' => 'سجل المزامنة #:id',
    'message' => 'الرسالة',
    'data' => 'البيانات',
    'calendar' => 'التقويم',
    'all_calendars' => 'جميع التقويمات',
    'clear_logs' => 'مسح جميع السجلات',
    'clear_logs_confirm' => 'هل أنت متأكد أنك تريد مسح جميع السجلات؟',
    'logs_cleared' => 'تم مسح جميع السجلات بنجاح',
    'error_occurred' => 'حدث خطأ. يرجى المحاولة مرة أخرى.',

    // Status
    'status_success' => 'نجاح',
    'status_warning' => 'تحذير',
    'status_error' => 'خطأ',

    // Email
    'email' => [
        'subject' => 'تنبيه فشل مزامنة iCal',
        'greeting' => 'مرحبًا،',
        'message' => 'اكتشفنا مشكلة في مزامنة iCal لنظام حجز الفندق الخاص بك.',
        'details' => 'تفاصيل الخطأ',
        'time' => 'الوقت',
        'error' => 'رسالة الخطأ',
        'action' => 'يرجى التحقق من إعدادات مزامنة iCal والتأكد من أن جميع روابط التقويم الخارجية صحيحة ويمكن الوصول إليها.',
        'view_logs' => 'عرض سجلات المزامنة',
        'closing' => 'شكرًا لاهتمامك بهذا الأمر.',
        'footer' => 'هذه رسالة تلقائية من :site_name.',
    ],

    // Documentation
    'documentation' => 'توثيق مزامنة iCal',
    'documentation_title' => 'كيفية استخدام مزامنة iCal',
    'what_is_ical' => 'ما هي مزامنة iCal؟',
    'what_is_ical_desc' => 'iCal (أو iCalendar) هو تنسيق قياسي لتبادل بيانات التقويم. تتيح لك ميزة مزامنة iCal مزامنة حجوزات غرف الفندق مع المنصات الخارجية مثل Booking.com وAirbnb وأنظمة الحجز الأخرى التي تدعم iCal.',

    'export_bookings' => 'تصدير حجوزاتك',
    'export_bookings_desc' => 'يمكنك تصدير حجوزات الفندق الخاصة بك إلى المنصات الخارجية لمنع الحجوزات المزدوجة.',
    'export_step1_title' => 'اذهب إلى صفحة مزامنة iCal',
    'export_step1_desc' => 'انتقل إلى الحجز > مزامنة iCal في لوحة الإدارة.',
    'export_step2_title' => 'ابحث عن الغرفة التي ترغب في تصديرها',
    'export_step2_desc' => 'انقر على "تعديل" للغرفة التي تريد تصدير حجوزاتها.',
    'export_step3_title' => 'انسخ رابط التصدير أو قم بتنزيل ملف iCal',
    'export_step3_desc' => 'انسخ رابط التصدير المقدم أو قم بتنزيل ملف iCal. أضف هذا الرابط إلى منصتك الخارجية (Booking.com أو Airbnb أو غيرها) وفقًا لتعليماتهم لاستيراد موجزات iCal.',

    'import_bookings' => 'استيراد الحجوزات الخارجية',
    'import_bookings_desc' => 'يمكنك استيراد الحجوزات من المنصات الخارجية لمنع الحجوزات المزدوجة في نظامك.',
    'import_step1_title' => 'احصل على رابط iCal من منصتك الخارجية',
    'import_step1_desc' => 'سجّل الدخول إلى منصتك الخارجية (Booking.com أو Airbnb أو غيرها) وابحث عن خيار تصدير التقويم بصيغة iCal. انسخ الرابط المقدم.',
    'import_step2_title' => 'اذهب إلى صفحة مزامنة iCal',
    'import_step2_desc' => 'انتقل إلى الحجز > مزامنة iCal في لوحة الإدارة.',
    'import_step3_title' => 'أضف التقويم الخارجي',
    'import_step3_desc' => 'انقر على "تعديل" للغرفة التي تريد استيراد الحجوزات لها. أدخل اسمًا للتقويم (مثل "Booking.com") والصق رابط iCal الذي نسخته مسبقًا.',
    'import_step4_title' => 'قم بمزامنة التقويم',
    'import_step4_desc' => 'انقر على "مزامنة" لمزامنة التقويم يدويًا أو انتظر حتى تعمل المزامنة التلقائية.',

    'sync_logs' => 'سجلات مزامنة iCal',
    'sync_logs_desc' => 'يحفظ النظام سجلات تفصيلية لجميع أنشطة المزامنة. يمكنك عرض هذه السجلات من خلال النقر على "سجلات المزامنة" في صفحة مزامنة iCal، مما يساعدك على معالجة أي مشكلات.',

    'troubleshooting' => 'استكشاف الأخطاء وإصلاحها',
    'troubleshooting_desc' => 'إليك بعض المشكلات الشائعة وحلولها:',
    'faq1_title' => 'التقويم الخارجي لا تتم مزامنته',
    'faq1_desc' => 'تأكد من أن الرابط صحيح ويمكن الوصول إليه. تتطلب بعض المنصات تسجيل الدخول للوصول إلى موجز iCal. حاول تنزيل ملف iCal مباشرة من الرابط للتأكد من أنه يعمل.',
    'faq2_title' => 'هناك تعارضات بين الحجوزات',
    'faq2_desc' => 'سيكتشف النظام التعارضات بين الحجوزات ويسجلها. يمكنك عرض هذه التعارضات في سجلات المزامنة، وستحتاج إلى حلها يدويًا عن طريق تعديل الحجوزات في أحد الأنظمة.',
    'faq3_title' => 'المزامنة التلقائية لا تعمل',
    'faq3_desc' => 'تأكد من إعداد مهام cron الخاصة بالخادم بشكل صحيح. يستخدم النظام مجدول Laravel لتشغيل أمر المزامنة كل ساعة. يمكنك أيضًا تنفيذ المزامنة يدويًا بالنقر على "مزامنة" أو "مزامنة جميع التقويمات" في صفحة مزامنة iCal.',
];
