# مراجعة integration — 12 أكتوبر 2026

> هذا سجل التحضير الأول عند 57965ec. أُعيد بناء integration فوق أحدث all-project عند 3c123746، الذي يتضمن Google Login وإصلاح robots. النتائج والقرار الحاليان في STAGING-CHECKLIST.md وملف staging-readiness.md المرفق؛ لا تستخدم الأرقام القديمة أدناه كحالة الفرع الحالية.

## النطاق والمراجع

- المستودع: Marowies/B-W-sahara-hotel-.
- الأصل: all-project عند 57965ec51330949cbeeaa56b8cb566e3470328f2.
- مصدر SEO: seo/page-sitemap-noindex عند 30efadbb8b0e1404b42c7a360be18fbd351ea316.
- فرع العمل المحلي: integration/all-project-seo-2026-10-12، مبني من all-project فقط؛ لا merge بين تاريخين منفصلين.
- git merge-base لم يجد ancestor مشتركًا. المسار المقابل لـ public_html في مصدر SEO هو BW-Sahara-Sky-Hotel/public_html في الأصل.
- 8fedb18e هو commit الاستيراد في تاريخ SEO، استُخدم لتحديد التعديلات التي أضافها هذا التاريخ. **ليس دليلًا أنه نسخة Production الحالية أو النسخة التي أُخذ منها Staging.** لا يوجد snapshot موثّق للسيرفر حتى الآن.
- main نفسه عند 8fedb18e398f1af67a5365ff4cf53efcbca34d4b أثناء الفحص. backend عند 1a3f3ec0c230477caca30a3b395ad882cc1903d5؛ لم يُستخدم أي منهما كبديل مفترض عن snapshot السيرفر.

## الجرد

ملف seo-inventory.csv المرفق يغطي 111 مسارًا تغيرت داخل تطبيق Laravel بين commit الاستيراد ورأس SEO. بالمقارنة مع all-project: 90 مطابقًا بالـ blob SHA، 14 مختلفًا تحتاج مراجعة، 3 ملفات بقيت على نسخة الاستيراد، 3 ملفات ناقصة، وملف حذف واحد (robots.txt). ملف file-inventory.csv يغطي جميع المسارات في تطبيق Laravel المقابل، بما فيها اللغات والأصول القديمة؛ الاختلاف الكامل بين الشجرتين أكبر بكثير من تغييرات SEO المحددة.

هذا الجرد يثبت تطابق ملفات محددة، ولا يثبت تشغيل كل الميزات أو تطابق بيانات CMS. برانش all-project يحتوي أيضًا frontend مستقلًا وتصميمًا ووثائق وحزم deploy، لا تقابلها نسخة تطبيق Laravel وحدها في برانش SEO.

الجرد الكامل بعد توحيد مسار Laravel وجد 13,491 ملف تطبيق مطابقًا، و66 مختلفًا، و43 في all-project فقط، و3 في SEO فقط (ملفات CanonicalHost التي نُقلت). يوجد أيضًا 3,450 ملف ترجمة إضافيًا في SEO ونسخة old أرشيفية من 18,290 ملفًا وصورة theme screenshot إضافية؛ لم تُنقل تلقائيًا. commit backend بعنوان Remove archived application copies and redundant localization files يوضح أن إزالة الأرشيف والترجمات الإضافية كانت اختيارًا في تاريخ العمل الآخر. تبقى هذه الملفات في برانش SEO والجرد؛ إعادة اللغات الإضافية تحتاج تحديد اللغات التي يراد دعمها، ولا نخلط النسخة old مع التطبيق الفعلي.

## ما نُقل

1. Page sitemap listener: تحميل metadata مع slugable واستبعاد Published Pages التي تحمل seo_meta[index]=noindex. تبقى صفحات index/default وتبقى شروط published الحالية. لم تتغير حالة أي صفحة أو بيانات CMS.
2. اختبار sitemap بقاعدة SQLite مصطنعة: published/index، published/noindex، default، draft، pending، وعدم التعامل مع مفاتيح sitemap أخرى. ربط Page source صراحةً في harness مع الإبقاء على ضبط UTC الموجود في all-project.
3. CanonicalHost middleware وconfig والاختبارات: معطل افتراضيًا؛ 302 افتراضي؛ 301 يتطلب تأكيدًا؛ يتخطى POST/Cookies/Auth/API/الحجز/الدفع والاستدعاءات الحساسة؛ يمنع استهداف دومين Production من بيئة غير Production. أُضيف تسجيل middleware مع الحفاظ على تسجيل AppServiceProvider الموجود في all-project.
4. حماية trailing-slash redirects في ملفي .htaccess بنقل الشروط المضافة على ملفات كانت مطابقة لنسخة استيراد SEO، وإكمالها باستثناء Authorization وX-XSRF وpayment_intent_client_secret وcode وorder_id.
5. إعدادات canonical الاختيارية في .env.example دون استبدال قيم الحماية الحالية.
6. اختبارات روابط صور وعناوين فئات الغرف، ومدخلات الحجز، مع تكييف الاختبار لمدخلات all-project الحالية بدل إعادة قوالب SEO الأقدم.
7. workflow فحص مستقل في جذر المستودع بمسار التطبيق الصحيح، PHP 8.2/8.3، بلا secrets أو نشر. وجوده محليًا لا يعني أن CI شُغّل.

تم إرفاق وثيقتي canonical-host-staging وpage-sitemap-noindex-review من برانش SEO. ما تذكره الوثيقة الثانية من فحص موقع سابق هو وصف مصدرها؛ لم أكرر ذلك الفحص على السيرفر في هذه المهمة.

## اختلافات حُفظت بعد المراجعة

| المسار داخل تطبيق Laravel | سبب الاحتفاظ بنسخة all-project |
|---|---|
| routes/web.php | يحتوي API rooms/availability/session/locale/contact وOTP/refresh/logout/eligibility. نسخة SEO ستزيل هذه المسارات. robots route موجود بالفعل. |
| platform/plugins/hotel/src/Http/Controllers/PublicController.php | يحتوي تغييرات تحقق العميل، التسعير، التوفر، checkout والدفع. استبداله يرجع هذه التغييرات؛ لم يُستبدل. تغييرات SEO موجودة ضمنه وتغطيها اختبارات SEO القائمة. |
| platform/themes/riorelax/functions/shortcodes.php | RoomSearchParams/GetRoomService وتدفق all-rooms الحالي؛ لا إعادة خوارزمية التوفر القديمة. |
| platform/themes/riorelax/partials/rooms/item.blade.php | يحافظ على تواريخ الحجز ككائنات دون تحويلها أثناء تكرار القالب، ويستخدم RoomSearchLink. |
| platform/themes/riorelax/views/hotel/rooms.blade.php | يعيد استخدام النتائج والمتغيرات المحسوبة بدل استدعاء shortcode ليحسبها ثانيةً. |
| platform/themes/riorelax/views/hotel/booking.blade.php | يحافظ على chunk بحجم 1 على الأقل عندما لا توجد خدمات. |
| platform/themes/riorelax/partials/gallery/galleries.blade.php | فلاتر ID ثابتة بدل أسماء الألبومات التي قد تكسر CSS selectors؛ اختبارات هذا التحسين بقيت. |
| tests/Seo/ListingTemplateCases.php وSafeGapCases.php | اختبارات تناسب controller/gallery الحاليين وضبط UTC؛ لم تُستبدل باختبارات المصدر الأقدم. |
| public/robots.txt | حذف المصدر لم يُنقل. static override قد يتجاوز robots route الديناميكي؛ يلزم جرد قواعد السيرفر وتخزينها ومراجعتها قبل أي تغيير. |
| composer.json وcomposer.lock وconfig/session.php | نسخ all-project بقيت دون تبديل إصدار Laravel أو إعدادات الجلسة. |

ملفات OTP/auth والمهاجرات والحجز والدفع ولوحة الإدارة والواجهة المستقلة بقيت من all-project. يمكن التحقق من ذلك عبر diff بين فرع integration والأصل؛ **هذا حفظ للملفات لا ادعاء بأن كل الوظائف نجحت.**

## حدود الجاهزية والقرارات المطلوبة

- مطلوب commit/tag أو snapshot ملفات يطابق Production القديم الذي نُسخ منه Staging، مع بيانات إعدادات CMS ذات الصلة (homepage/locales/menus) بدون أسرار. لا يمكن تأكيد أن all-project يجمع كل عمل السيرفر بدونه.
- اختيار خطة الخدمة العامة: واجهة frontend مستقلة مع Laravel للإدارة/API، أم قوالب riorelax. إصلاح Page sitemap يخص Laravel؛ sitemap الواجهة المستقلة نظام آخر. يجب اختبار المصدر الذي سيخدم كل URL بالفعل.
- تأجيل إزالة robots static أو تغيير indexability أو unpublish لأي صفحة حتى مراجعة قواعد السيرفر والقوائم والترجمات. Staging يحتاج منع فهرسة مستقلًا في طبقة الاستضافة والواجهة؛ canonical middleware لا يمنع فهرسة Staging.
- تحويلات .htaccess تحتاج اختبار Apache فعليًا؛ اختبارات middleware لا تثبت إعداد Apache/CDN. يجب مراجعة admin path المخصص إن تغير ADMIN_DIR.
- اختبار الحجوزات والدفع وOTP والبريد وwebhooks وظيفيًا على بيئة مصطنعة منفصلة مع خدمات تجريبية، دون بيانات أو مفاتيح Production. لا تُعتبر اختبارات الوحدات بديلًا لهذه الجولة.
- حزم DEPLOY-READY-2026-10-12 المضغوطة سابقة لهذا integration؛ لا تحتوي هذه التعديلات تلقائيًا، ولا تستخدم كحزمة نهائية لهذا الفرع. وثيقة deploy في الأصل تذكر مشكلة 500 بإعدادات General؛ لم تُصلح ضمن SEO وتحتاج تحققًا منفصلًا.
- لا merge إلى all-project/main ولا نشر أو تعديل Production/Staging. الرفع إلى GitHub نفسه رفضته المراجعة الآلية؛ الفرع محلي حتى موافقة صريحة.

## التحقق

| الفحص | النتيجة الفعلية |
|---|---|
| PHP syntax | 5,859 ملفًا ناجحًا؛ فحص إضافي للملفات المعدلة لاحقًا ناجح. |
| Composer manifest/lock | صالح؛ تحذيرات قيود *@dev القديمة. لا تغييرات على lock أو إصدار Laravel. |
| SEO standalone | 101 ناجح من 102؛ الفشل الوحيد Static robots bypasses Laravel، لأن حذف الملف مؤجل عمدًا. اختبار noindex الجديد ناجح. |
| CanonicalHost | 50/50 ناجح؛ real Laravel requests وsigned URLs، أما Apache فهي مراجعة source وليست اختبار خادم Apache. |
| Security/auth/booking standalone | 63/63 ناجح باستخدام SQLite :memory: وبيانات مصطنعة. |
| Integration/calendar/payment/checkout | 151/154 ناجح. الثلاثة الباقية تتطلب InnoDB حقيقيًا لا SQLite؛ لا يُدّعى نجاح التزامن أو منع overselling تحت التنافس. تشمل المجموعة اختبارات Security السابقة، فلا تُجمع الأعداد كاختبارات مستقلة. |
| PHPUnit | 3 اختبارات Unit ناجحة؛ 6 Feature tests تعثرت أثناء Laravel boot بسبب Windows sandbox rename Access denied في packages.php. لم يصل التنفيذ إلى تحقق سلوكها. |
| Tracking JavaScript | 8/8 ناجح. |
| Frontend deployment configuration | 3/3 ناجح. لم يُنفذ browser smoke أو بناء release نهائي. |
| حفظ الملفات | 16,885 ملفًا أصليًا: 16,877 مطابقة للـblob SHA و8 معدلة؛ صفر حذف، مع 7 إضافات. الجرد لا يثبت صحة بيانات CMS أو runtime. |

تنزيل Composer المحلي واجه قيود كتابة ثم اكتمل استخراج الحزم؛ أُوقف إنشاء classmap المحسن الطويل واستُخدم manifest مؤقت لتوليد autoload عادي، بدون تغيير composer.json/lock الأصليين أو تشغيل app scripts/plugins. حُذف manifest المؤقت. تم تعطيل PHP network transports أثناء اختبارات PHP. PHPUnit شُغّل عبر bootstrap مباشر مؤقت بعد أن أبلغ is_readable عن false رغم نجاح القراءة؛ استمرت مشكلة rename في cache. لم تُعدّل مكتبات Laravel أو PHPUnit لإجبار نجاح الاختبارات.

حالة الفرع: **مراجعة محلية غير جاهزة للنشر**. لم تُشغّل GitHub CI ولم يُرفع الفرع. نتائج JSON والجرد والـpatch مرفقة بالتسليم.
