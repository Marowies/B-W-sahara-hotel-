<?php

return [
    'tools' => [
        'export_import_data' => 'تصدير/استيراد البيانات',
    ],
    'import' => [
        'name' => 'استيراد',
        'heading' => 'استيراد :label',
        'failed_to_read_file' => 'الملف غير صالح أو تالف أو كبير جدًا بحيث لا يمكن قراءته.',
        'form' => [
            'quick_export_message' => 'إذا كنت تريد تصدير بيانات :label، يمكنك القيام بذلك بسرعة بالنقر على :export_csv_link أو :export_excel_link.',
            'quick_export_button' => 'تصدير إلى :format',
            'dropzone_message' => 'اسحب الملف وأفلته هنا أو انقر للرفع',
            'allowed_extensions' => 'اختر ملفًا بأحد الامتدادات التالية: :extensions.',
            'import_button' => 'استيراد',
            'chunk_size' => 'حجم الشريحة',
            'chunk_size_helper' => 'يحدد حجم الشريحة عدد الصفوف التي يتم استيرادها في المرة الواحدة. قم بزيادة هذه القيمة إذا كان لديك ملف كبير وكان استيراد البيانات سريعًا جدًا. وقم بتقليلها إذا واجهت حدودًا للذاكرة أو مشاكل في مهلة البوابة عند استيراد البيانات.',
        ],
        'failures' => [
            'title' => 'حالات الفشل',
            'attribute' => 'السمة',
            'errors' => 'الأخطاء',
        ],
        'example' => [
            'title' => 'مثال',
            'download' => 'تنزيل ملف :type نموذجي',
        ],
        'rules' => [
            'title' => 'القواعد',
            'column' => 'العمود',
        ],
        'uploading_message' => 'بدء رفع الملف...',
        'uploaded_message' => 'تم رفع الملف :file بنجاح. بدء التحقق من البيانات...',
        'validating_message' => 'جارٍ التحقق من :from إلى :to...',
        'importing_message' => 'جارٍ الاستيراد من :from إلى :to...',
        'done_message' => 'تم استيراد :count :label بنجاح.',
        'validating_failed_message' => 'فشل التحقق من الصحة. يرجى مراجعة الأخطاء أدناه.',
        'no_data_message' => 'بياناتك محدّثة بالفعل أو لا توجد بيانات للاستيراد.',
    ],
    'export' => [
        'name' => 'تصدير',
        'heading' => 'تصدير :label',
        'excel_not_supported_for_large_exports' => 'تنسيق Excel غير مدعوم لعمليات التصدير الكبيرة (:count عنصر). يرجى استخدام تنسيق CSV بدلاً من ذلك للحصول على أداء وموثوقية أفضل.',
        'form' => [
            'all_columns_disabled' => 'سيتم تصدير الأعمدة التالية: :columns.',
            'columns' => 'الأعمدة',
            'format' => 'التنسيق',
            'export_button' => 'تصدير',
        ],
        'success_message' => 'تم التصدير بنجاح.',
        'error_message' => 'فشل التصدير.',
        'empty_state' => [
            'title' => 'لا توجد بيانات للتصدير',
            'description' => 'يبدو أنه لا توجد بيانات للتصدير.',
            'back' => 'العودة إلى :page',
        ],
    ],
    'check_all' => 'تحديد الكل',
];
