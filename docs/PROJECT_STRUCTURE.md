# تنظيم مشروع B&W Sahara Sky Hotel

نسخة العمل الوحيدة هي `public_html/`. النسخ التاريخية والباكب خارج شجرة التطبيق، ولا تُنقل ملفات Botble من أماكنها لأنها مرتبطة بالتحميل التلقائي والـ migrations ونشر الموارد.

| المسار | المسؤولية | قواعد التعديل |
| --- | --- | --- |
| `public_html/app/` | تخصيصات Laravel العامة | الكود الخاص بالتطبيق، وليس نسخًا من vendor |
| `public_html/config/` و`bootstrap/` و`routes/` | إعدادات التطبيق والتشغيل | أسرار التشغيل في `.env` المحلي فقط |
| `public_html/platform/core/` | أساس Botble | تغييرات محدودة موثقة؛ مراجعتها عند تحديث CMS |
| `public_html/platform/packages/` | حزم CMS المحلية | الحفاظ على namespaces ومسارات Composer |
| `public_html/platform/plugins/hotel/src/Http/Requests/` | التحقق من مدخلات الفندق | قواعد المدخلات هنا، قبل الحفظ |
| `public_html/platform/plugins/hotel/src/Http/Controllers/` | تنسيق الطلب والاستجابة | الحقول الحساسة يحددها السيرفر، وحفظ الحجز داخل transaction |
| `public_html/platform/plugins/hotel/src/Services/` | خدمات الفندق | جلب التقويم من خلال `SafeCalendarFetcher` فقط |
| `public_html/platform/plugins/hotel/src/Models/` | علاقات البيانات وسلوكها | عدم حذف fillable اللازمة للإدارة لمعالجة ثغرة في واجهة عامة |
| `public_html/platform/plugins/*/resources/` | الموارد الأصلية لكل إضافة | مصدر الترجمات والقوالب والأصول الخاصة بالإضافة |
| `public_html/platform/themes/riorelax/` | مصدر قالب الفندق | تغييرات التصميم هنا ثم البناء عند الحاجة |
| `public_html/public/` | الملفات التي يخدمها الويب | هذا هو document root؛ ممنوع وضع SQL أو backups أو `.env` هنا |
| `public_html/lang/` | الترجمات العامة والتخصيصات المختلفة | عدم إعادة نسخ الترجمات المطابقة التي أزيلت سابقًا |
| `public_html/tests/Security/` | اختبارات الأمان المعزولة | SQLite في الذاكرة، بلا داتا فندق أو شبكة أو دفع حقيقي |
| `docs/` | المتطلبات والتقارير والقرارات | تحديث التقرير مع أي إصلاح أو قيد تحقق |

ضبط `.editorconfig` على مستوى المستودع يوحد UTF-8 وLF وأربع مسافات ونهاية الملفات. لم يتم عمل تنسيق شامل لآلاف ملفات CMS والترجمات؛ التغييرات تركز على الملفات التي تحتاج إصلاحًا لكي تظل مراجعة الفروقات واضحة.

تثبت `.gitattributes` النصوص على LF عند التعامل مع Git، باستثناء bat/cmd التي تستخدم CRLF. لا يتم إعادة ضغط الصور أو تعديل الملفات الثنائية بسبب تنظيم التنسيق.

لا تُنقل ملفات CSS وJS المنشورة عشوائيًا؛ بعضها ناتج build وله مصدر مستقل. `vendor/` و`node_modules/` وملفات cache/storage التشغيلية ملفات مولدة ومستثناة من Git. لا تُحذف ملفات متشابهة إلا بعد مقارنة المحتوى وفحص المراجع.

## تشغيل اختبارات الأمان

اختبارات التكامل في `public_html/tests/Integration/` تستخدم قاعدة InnoDB مؤقتة وعمليات مستقلة ومحاكاة محلية للمزود. الإعدادات السرية خارج المستودع؛ [تقرير 5 أكتوبر](INTEGRATION_TEST_REPORT_2026-10-05.md) يوضح التشغيل والحالات الفاشلة وحدود التحقق.

اختبارات التكامل في `public_html/tests/Integration/` تستخدم قاعدة InnoDB مؤقتة وعمليات مستقلة ومحاكاة محلية للمزود. الإعدادات السرية خارج المستودع؛ [تقرير 5 أكتوبر](INTEGRATION_TEST_REPORT_2026-10-05.md) يوضح التشغيل والحالات الفاشلة وحدود التحقق.

البحث يستخدم `RoomSearchParams` و`GetRoomService`، والإقامة تعتمد `StayDates` و`Rules/ValidDeparture`، وتسعير الإضافات والكوبون في `BookingPricingService`. التفاصيل والقيود في [تقرير الباك إند](BACKEND_REFACTOR_2026-10-04.md). يتضمن runner الحالي حالات الباك إند في `tests/Security/BackendCases.php` بجانب اختبارات الأمان.

بعد تثبيت الاعتماديات، من داخل `public_html/`:

```powershell
php tests/Security/run.php
```

يمكن تمرير مسار `vendor/autoload.php` خارجي موجود كوسيط ثانٍ لاستخدام الاعتماديات للقراءة فقط. الاختبارات تحمل كود الفندق من نسخة العمل وتستخدم transport double للتقويم، وبالتالي لا تتصل بروابط اختبارات SSRF. هذا runner مستقل ولا يعتمد على PHPUnit؛ اختبارات المتصفح والدفع والتزامن الفعلي في MySQL تحتاج تشغيلًا تكامليًا منفصلًا.
