# قرار Staging: NO GO حاليًا

الفرع integration/all-project-seo-2026-10-12 يتضمن أحدث all-project عند 3c123746 مع إضافات integration المحدودة. لا merge ولا تعديل Production أو Staging في هذه المهمة.

## قبل تجربة السيرفر

- [ ] نسخة Backup قابلة للاستعادة لملفات وDB النسخة القديمة؛ احتفظ بـStaging الحالي كمرجع قبل إزالته.
- [ ] قاعدة MySQL منفصلة ومستخدم منفصل لا يملك صلاحية Production، مع بيانات اختبار وتنقيح أي بيانات شخصية.
- [ ] .env ومفتاح APP_KEY وcookies وcache/storage/queues مستقلة، APP_ENV=staging وAPP_DEBUG=false وAPP_URL دومين Staging. CANONICAL_HOST_ENABLED=false أثناء البداية.
- [ ] MAIL_MAILER=array؛ راجع SMTP في إعدادات CMS أيضًا، وعطّل العمال/cron/mail retries وإشعارات الحجز والمزامنة الخارجية. لا تكتفِ بتغيير .env إذا CMS يضع إعدادات SMTP.
- [ ] لا مفاتيح Live أو webhooks حقيقية؛ عطّل الدفع بالكامل في الجولة الأولى، ثم استعمل مفاتيح sandbox ومزودات تجريبية فقط. لا تحويلات مالية حقيقية.
- [ ] منع فهرسة Staging في طبقة الاستضافة: حماية دخول وX-Robots-Tag: noindex؛ frontend HOTEL_INDEXABLE=0 وHOTEL_SITE_ORIGIN دومين Staging وHOTEL_BACKEND_MODE=same-origin.
- [ ] اقرأ robots القديم من السيرفر واحتفظ بنسخة؛ reconcile قواعده قبل إزالة static override. نجاح الاختبار المحلي لا يثبت أن ملف السيرفر مطابق.
- [ ] نتائج CI على PHP 8.2/8.3 وMySQL المؤقت ناجحة؛ الاختبارات المحلية الثلاثة للتزامن لا يمكن إثباتها بـSQLite.

## اختبارات القبول بعد السماح بتجربة Staging

- [ ] الرئيسية/الغرف/التفاصيل/المعرض/التواصل بالعربية والإنجليزية والصينية، RTL والموبايل، Console وشبكة الطلبات بلا أخطاء؛ rebuild من المصدر لا تستخدم أرشيف deploy القديم.
- [ ] البحث والتواريخ والضيوف والتوفر والسعر والإجمالي والخدمات والقسائم؛ حجز مصطنع، إلغاء وiCal بمصادر fixtures فقط.
- [ ] OTP من صندوق الاختبار، Google بعميل Staging وcallback صحيح، repeat login نفس customer ID، refresh/logout وcheckout eligibility؛ لا tokens في URL أو localStorage.
- [ ] استضافة static frontend مع Laravel على نفس origin؛ يجب توجيه /api/hotel و/admin و/auth/callback/google إلى PHP. الـcallback الجديد قد يفشل إذا اتُّبع دليل الاستضافة القديم الذي يذكر API/admin فقط.
- [ ] redirects دون loops؛ POST/auth/cookies/signed links/payment callbacks لا تتحول؛ تحقق Apache فعليًا.
- [ ] canonical/hreflang/H1/schema والروابط، sitemap يستبعد Page noindex/draft، robots يُخدم من الطبقة الصحيحة ولا يشير إلى URLs غير مقصودة.
- [ ] لوحة الإدارة والحجوزات والعملاء والإعدادات بلا 500. مشكلة General settings المذكورة سابقًا تحتاج تحققًا فعليًا.

## رفع الفرع لا يعني نشره

Hostinger غير مربوط بالريبو بحسب تأكيد المستخدم. workflow الموجود في جذر الفرع للاختبار فقط، وقاعدة MySQL فيه service مؤقتة بمعلومات اختبار وليست بيانات السيرفر. إعدادات Vercel في جذر هذا الفرع وفي frontend تمنع Git auto-deployment؛ لا يغير ذلك all-project/main. مرجع الإعداد: https://vercel.com/docs/project-configuration/git-configuration.

لم يمكن قراءة repository webhooks عبر صلاحيات connector؛ لا تُعتبر مراجعة الملفات وحدها جردًا كاملًا للخدمات الخارجية. قبل ربط Hostinger: اجعل الربط على Staging منفصلًا وبدون Auto Deploy، وحدد document root وخطة rollback أولًا. لا تحذف Staging الحالي قبل نجاح Backup والتحقق من العزل.
