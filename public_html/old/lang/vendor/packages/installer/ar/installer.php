<?php

return [
    'title' => 'التثبيت',
    'next' => 'الخطوة التالية',
    'forms' => [
        'errorTitle' => 'حدثت الأخطاء التالية:',
    ],
    'welcome' => [
        'title' => 'مرحبًا',
        'message' => 'قبل البدء، نحتاج إلى بعض المعلومات حول قاعدة البيانات. ستحتاج إلى معرفة العناصر التالية قبل المتابعة.',
        'language' => 'اللغة',
        'next' => 'هيا بنا',
    ],
    'requirements' => [
        'title' => 'متطلبات الخادم',
        'php_version_required' => 'مطلوب إصدار PHP :version',
    ],
    'permissions' => [
        'next' => 'تكوين البيئة',
    ],
    'environment' => [
        'wizard' => [
            'title' => 'إعدادات البيئة',
            'form' => [
                'name_required' => 'اسم البيئة مطلوب.',
                'app_name_label' => 'عنوان الموقع',
                'app_url_label' => 'الرابط',
                'db_connection_label' => 'اتصال قاعدة البيانات',
                'db_connection_label_mysql' => 'MySQL',
                'db_host_label' => 'مضيف قاعدة البيانات',
                'db_port_label' => 'منفذ قاعدة البيانات',
                'db_name_label' => 'اسم قاعدة البيانات',
                'db_name_placeholder' => 'اسم قاعدة البيانات',
                'db_username_label' => 'اسم مستخدم قاعدة البيانات',
                'db_username_placeholder' => 'اسم مستخدم قاعدة البيانات',
                'db_password_label' => 'كلمة مرور قاعدة البيانات',
                'db_password_placeholder' => 'كلمة مرور قاعدة البيانات',
                'buttons' => [
                    'install' => 'تثبيت',
                ],
                'db_host_helper' => 'إذا كنت تستخدم Laravel Sail، فما عليك سوى تغيير DB_HOST إلى DB_HOST=mysql. في بعض الاستضافات، يمكن أن يكون DB_HOST هو localhost بدلاً من 127.0.0.1',
                'db_connections' => [
                    'mysql' => 'MySQL',
                    'sqlite' => 'SQLite',
                    'pgsql' => 'PostgreSQL',
                ],
            ],
        ],
        'success' => 'تم حفظ إعدادات ملف .env الخاص بك.',
        'errors' => 'تعذر حفظ ملف .env، يرجى إنشائه يدويًا.',
    ],
    'theme' => [
        'title' => 'اختر قالبًا',
        'message' => 'اختر قالبًا لتخصيص مظهر موقعك الإلكتروني. سيؤدي هذا الاختيار أيضًا إلى استيراد بيانات نموذجية مصممة خصيصًا للقالب المختار.',
    ],
    'createAccount' => [
        'title' => 'إنشاء حساب',
        'form' => [
            'first_name' => 'الاسم الأول',
            'last_name' => 'اسم العائلة',
            'username' => 'اسم المستخدم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'create' => 'إنشاء',
        ],
    ],
    'license' => [
        'title' => 'تفعيل الترخيص',
        'skip' => 'تخطي الآن',
    ],
    'final' => [
        'pageTitle' => 'اكتمل التثبيت',
        'title' => 'تم',
        'message' => 'تم تثبيت التطبيق بنجاح.',
        'exit' => 'الانتقال إلى لوحة تحكم المشرف',
    ],
    'install_step_title' => 'التثبيت - الخطوة :step: :title',
    'theme_preset' => [
        'title' => 'اختر إعداد القالب',
        'message' => 'اختر إعدادًا مسبقًا للقالب لتخصيص مظهر موقعك الإلكتروني. سيؤدي هذا الاختيار أيضًا إلى استيراد بيانات نموذجية مصممة خصيصًا للقالب المختار.',
    ],
];
