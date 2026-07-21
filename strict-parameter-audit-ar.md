# التدقيق الصارم لمطابقة الباراميترات مع وثيقة المتطلبات

هذا الملف هو تدقيق صارم جدًا بين:

- وثيقة المتطلبات المرسلة
- الموديلات الموجودة حاليًا داخل المشروع

الهدف منه تحديد المطابقة الحقيقية بدقة، وليس المجاملة.

## مفاتيح الحالة

- `مطابق حرفيًا`
  الاسم والمكان والنوع العام متوافق مع الوثيقة
- `مطابق وظيفيًا`
  يؤدي نفس الغرض لكن ليس مطابقًا حرفيًا 100%
- `مشتق`
  غير مخزن مباشرة في نفس الكيان، لكنه محسوب أو مقروء من كيان آخر
- `ناقص`
  غير موجود حاليًا
- `جزئي`
  موجود لكن النوع أو البنية الداخلية أو المرجعية ليست مكتملة
- `مستبعد عمدًا`
  غير مطلوب في تطبيقك الحالي وتم استبعاده بقرار صريح منك

## الموديلات الحالية التي تم التدقيق عليها

- `Product`
- `SharedProductDetail`
- `AgriculturalProductDetail`
- `VeterinaryProductDetail`
- `Manufacturer`
- `Brand`

## قرارات معمارية معتمدة منك قبل التدقيق

هذه النقاط تعتبر مقبولة ولا تُحسب كعيب في المطابقة داخل هذا المشروع:

1. `Product` هو فعليًا الطبقة المشتركة الأساسية بدل إنشاء Shared Product منفصل عن العمل الحالي
2. `subcategory_id` مستبعد عمدًا
3. لا حاجة الآن لإنشاء بقية الجداول المرجعية غير الضرورية للتنفيذ الحالي
4. حقول `array<object>` مهمة جدًا ويجب تمثيلها بوضوح

## 1. التدقيق الصارم للباراميترات المشتركة

| الباراميتر | المطلوب في الوثيقة | الموجود حاليًا | المكان الحالي | الحالة | الملاحظة الدقيقة |
|---|---|---|---|---|---|
| `product_id` | UUID/BIGINT | integer | `SharedProductDetail` | `مطابق وظيفيًا` | الاسم موجود لكن النوع الفعلي الحالي في الموديلات integer وليس موثقًا كـ UUID/BIGINT |
| `product_type` | enum | accessor | `SharedProductDetail` | `مطابق وظيفيًا` | مشتق من `Product -> Category -> type` وهذا مقبول ضمن قرارك المعماري |
| `name_ar` | string | موجود | `Product` | `مطابق وظيفيًا` | موجود في الطبقة المشتركة الفعلية وهي `Product` |
| `name_en` | string | موجود | `Product` | `مطابق وظيفيًا` | نفس الملاحظة |
| `commercial_name` | string | موجود | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `aliases` | array<string> | array | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `barcode` | string | موجود | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `sku` | string | موجود | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `category_id` | UUID/BIGINT | accessor | `SharedProductDetail` + `Product` | `مطابق وظيفيًا` | موجود في `Product` ومشتق في `SharedProductDetail` |
| `subcategory_id` | UUID/BIGINT | غير موجود | لا يوجد | `مستبعد عمدًا` | مستبعد بقرار صريح منك |
| `manufacturer_id` | UUID/BIGINT | integer | `SharedProductDetail` | `مطابق وظيفيًا` | موجود كمرجع جيد، لكن النوع غير موثق كـ UUID/BIGINT حرفيًا |
| `brand_id` | UUID/BIGINT | integer | `SharedProductDetail` | `مطابق وظيفيًا` | نفس الملاحظة |
| `country_of_origin` | ISO country code | string | `SharedProductDetail` | `جزئي` | الحقل موجود، لكن لا توجد قيود أو enum أو توثيق يفرض ISO code |
| `registration_number` | string | موجود | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `registration_status` | enum | string | `SharedProductDetail` | `جزئي` | الاسم موجود لكن لا يوجد enum محدد بالقيم `approved/pending/expired/unknown` |
| `package_size` | decimal | decimal:2 | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `package_unit` | enum/string | string | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `short_description` | text | موجود | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `approved_description` | text | موجود | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `keywords` | array<string> | array | `SharedProductDetail` | `مطابق حرفيًا` | جيد |
| `status` | enum | accessor | `SharedProductDetail` + `Product` | `مطابق وظيفيًا` | القيمة موجودة في `Product` لأنه الطبقة المشتركة الفعلية |
| `created_at` | datetime | غير مصرح به في fillable لكنه موجود افتراضيًا | كل الموديلات | `مطابق وظيفيًا` | Laravel يضيفه تلقائيًا إن وجدت الأعمدة |
| `updated_at` | datetime | غير مصرح به في fillable لكنه موجود افتراضيًا | كل الموديلات | `مطابق وظيفيًا` | نفس الملاحظة |
| `deleted_at` | datetime/null | موجود | `SharedProductDetail` | `جزئي` | الحقل موجود لكن الموديل لا يستخدم `SoftDeletes` حاليًا |

## 2. التدقيق الصارم للمرجعيات المشتركة

### `manufacturers`

| الباراميتر | المطلوب في الوثيقة | الموجود حاليًا | الحالة | الملاحظة |
|---|---|---|---|---|
| `manufacturer_id` | موجود | `id` | `مطابق حرفيًا` | جيد |
| `name_ar` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `name_en` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `country` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `website` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `status` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `name` | غير مذكور حرفيًا | موجود | `إضافة خاصة` | أضفناه لتسهيل الاستخدام في نظامك |
| `updated_at` | مطلوب | timestamps | `مطابق وظيفيًا` | جيد |

### `brands`

| الباراميتر | المطلوب في الوثيقة | الموجود حاليًا | الحالة | الملاحظة |
|---|---|---|---|---|
| `brand_id` | موجود | `id` | `مطابق حرفيًا` | جيد |
| `manufacturer_id` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `name_ar` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `name_en` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `status` | مطلوب | موجود | `مطابق حرفيًا` | جيد |
| `name` | غير مذكور حرفيًا | موجود | `إضافة خاصة` | أضفناه لتسهيل الاستخدام في نظامك |

## 3. التدقيق الصارم للباراميترات البيطرية

| الباراميتر | المطلوب في الوثيقة | الموجود حاليًا | الحالة | الملاحظة |
|---|---|---|---|---|
| `active_ingredients` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | تم توثيق shape الداخلي على مستوى الموديل |
| `concentration` | string/object | موجود | `مطابق حرفيًا` | جيد |
| `dosage_form` | enum/string | موجود | `مطابق حرفيًا` | جيد |
| `routes_of_administration` | array<enum> | array | `جزئي` | ما زالت enum values نفسها غير مثبتة كقائمة مغلقة |
| `target_species` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `indications` | array<object>/text | array | `مطابق وظيفيًا` | اخترنا array، وهو مقبول وظيفيًا |
| `dosage_instructions` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `treatment_duration` | string/object | موجود | `مطابق حرفيًا` | جيد |
| `contraindications` | text/array | array | `مطابق وظيفيًا` | جيد |
| `warnings` | text/array | array | `مطابق وظيفيًا` | جيد |
| `special_precautions` | text | موجود | `مطابق حرفيًا` | جيد |
| `adverse_reactions` | text/array | array | `مطابق وظيفيًا` | جيد |
| `drug_interactions` | text/array | array | `مطابق وظيفيًا` | جيد |
| `pregnancy_lactation_use` | text | موجود | `مطابق حرفيًا` | جيد |
| `withdrawal_meat_days` | integer/null | integer | `مطابق حرفيًا` | جيد |
| `withdrawal_milk_days` | integer/null | integer | `مطابق حرفيًا` | جيد |
| `withdrawal_eggs_days` | integer/null | integer | `مطابق حرفيًا` | جيد |
| `storage_conditions` | text/object | موجود | `مطابق حرفيًا` | جيد |
| `shelf_life_after_opening` | string/object | موجود | `مطابق حرفيًا` | جيد |
| `overdose_information` | text | موجود | `مطابق حرفيًا` | جيد |
| `disposal_instructions` | text | موجود | `مطابق حرفيًا` | جيد |
| `medical_disclaimer` | text | موجود | `مطابق حرفيًا` | جيد |

## 4. التدقيق الصارم للباراميترات الزراعية المشتركة

| الباراميتر | المطلوب في الوثيقة | الموجود حاليًا | الحالة | الملاحظة |
|---|---|---|---|---|
| `agricultural_product_type` | enum | موجود | `مطابق حرفيًا` | جيد |
| `active_ingredients` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `formulation` | string/enum | موجود | `مطابق حرفيًا` | جيد |
| `target_crops` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `approved_uses` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `application_methods` | array<enum> | array | `جزئي` | enum values غير موثقة كقائمة مغلقة |
| `application_rates` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `max_applications` | integer/string | موجود | `مطابق حرفيًا` | جيد |
| `application_interval_days` | integer/string | موجود | `مطابق حرفيًا` | جيد |
| `storage_conditions` | text/object | موجود | `مطابق حرفيًا` | جيد |
| `warnings` | text/array | array | `مطابق وظيفيًا` | جيد |
| `ppe_requirements` | array<string> | array | `مطابق حرفيًا` | جيد |
| `first_aid` | text/object | array | `مطابق وظيفيًا` | جيد لكنه ليس text/object حرفيًا |
| `container_disposal` | text | موجود | `مطابق حرفيًا` | جيد |
| `compatibility` | text/array | array | `مطابق وظيفيًا` | جيد |

## 5. التدقيق الصارم لحقول المبيدات

| الباراميتر | المطلوب | الموجود | الحالة | الملاحظة |
|---|---|---|---|---|
| `pesticide_type` | enum | موجود | `مطابق حرفيًا` | جيد |
| `chemical_group` | string | موجود | `مطابق حرفيًا` | جيد |
| `mode_of_action` | text/string | موجود | `مطابق حرفيًا` | جيد |
| `target_pests` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `pre_harvest_intervals` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `re_entry_interval_hours` | integer/null | integer | `مطابق حرفيًا` | جيد |
| `toxicity_class` | string/enum | موجود | `مطابق حرفيًا` | جيد |
| `environmental_hazards` | text/object | array | `مطابق وظيفيًا` | جيد لكنه ليس object/text حرفيًا |
| `resistance_management` | text | موجود | `مطابق حرفيًا` | جيد |

## 6. التدقيق الصارم لحقول الأسمدة

| الباراميتر | المطلوب | الموجود | الحالة | الملاحظة |
|---|---|---|---|---|
| `fertilizer_type` | enum | موجود | `مطابق حرفيًا` | جيد |
| `nutrient_n_percent` | decimal/null | decimal:2 | `مطابق حرفيًا` | جيد |
| `nutrient_p_percent` | decimal/null | decimal:2 | `مطابق حرفيًا` | جيد |
| `nutrient_k_percent` | decimal/null | decimal:2 | `مطابق حرفيًا` | جيد |
| `micronutrients` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `organic_matter_percent` | decimal/null | decimal:2 | `مطابق حرفيًا` | جيد |
| `ph_value` | decimal/range | decimal:2 | `جزئي` | يدعم decimal فقط حاليًا، وليس range |
| `solubility` | string | موجود | `مطابق حرفيًا` | جيد |
| `growth_stages` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `fertilization_methods` | array<enum> | array | `جزئي` | enum values غير موثقة |

## 7. التدقيق الصارم لحقول البذور

| الباراميتر | المطلوب | الموجود | الحالة | الملاحظة |
|---|---|---|---|---|
| `crop_id` | UUID/BIGINT | integer | `مطابق وظيفيًا` | مرجع موجود لكن غير موثق كـ UUID/BIGINT |
| `variety_name` | string | موجود | `مطابق حرفيًا` | جيد |
| `variety_type` | enum | موجود | `مطابق حرفيًا` | جيد |
| `germination_percent` | decimal | decimal:2 | `مطابق حرفيًا` | جيد |
| `purity_percent` | decimal | decimal:2 | `مطابق حرفيًا` | جيد |
| `seed_treatment` | text/object | موجود | `مطابق حرفيًا` | جيد |
| `disease_resistance` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `planting_windows` | array<object> | array + phpdoc shape | `مطابق وظيفيًا` | shape الداخلي موثق الآن |
| `seeding_rate` | object | array | `مطابق وظيفيًا` | جيد لكن ليس object موثق حرفيًا |
| `planting_depth` | object | array | `مطابق وظيفيًا` | نفس الملاحظة |
| `plant_spacing` | object | array | `مطابق وظيفيًا` | نفس الملاحظة |
| `maturity_days` | integer/range | موجود | `مطابق حرفيًا` | جيد |
| `expected_yield` | object/null | array | `مطابق وظيفيًا` | جيد لكن ليس object موثق حرفيًا |

## 8. الفجوات الحاسمة التي تمنع الوصول إلى 100%

هذه هي النقاط الحقيقية التي تمنع قول "المطابقة 100%":

1. لا توجد enums موثقة في الموديلات للقيم المحددة في الوثيقة
2. `deleted_at` موجود بدون `SoftDeletes`
3. `ph_value` لا يدعم `range` حاليًا
4. المرجعيات مثل `crop_id` و`manufacturer_id` و`brand_id` موجودة عمليًا، لكن الأنواع ليست موثقة كـ `UUID/BIGINT`
5. لا توجد حتى الآن موديلات مرجعية مقابلة لكل الجداول المرجعية المذكورة في الوثيقة مثل:
   - `species`
   - `crops`
   - `pests`
   - `active_ingredients`
   - `units`

## 9. النتيجة النهائية الحالية

### من ناحية الأسماء

- المطابقة عالية جدًا

### من ناحية الأنواع العامة

- المطابقة جيدة لكن ليست كاملة

### من ناحية التفاصيل الداخلية للحقل

- المطابقة أصبحت أعلى بعد توثيق shape الداخلي لعدد كبير من حقول `array<object>`

### من ناحية التطابق الحرفي الكامل 100%

- **غير متحقق بعد**

## 10. ما الذي يجب فعله للوصول إلى 100%

إذا أردنا فعليًا الوصول إلى 100%، فهذه هي الخطوات المطلوبة بلا اختصار:

1. إعادة تموضع `name_ar`, `name_en`, `product_type`, `category_id`, `status` إلى نفس النموذج الموحد حرفيًا أو توثيق قرار معماري بديل رسمي
2. إضافة `subcategory_id` أو تثبيت قرار رسمي بإلغائه من النطاق
3. تعريف كل `array<object>` بشكل موثق:
   - ما هي المفاتيح الداخلية
   - ما أنواعها
   - هل هي required أم nullable
4. تعريف enums بشكل واضح في الكود أو عبر Enums فعلية
5. إضافة `SoftDeletes` إن كان `deleted_at` جزءًا فعليًا من التصميم
6. إنشاء بقية الجداول المرجعية الناقصة
7. إنشاء migrations متطابقة مع هذه البنية
8. تحديث Requests وResources وFactories لاحقًا لتلتزم بالبنية

## 11. الحكم النهائي الصريح

الحالة الحالية ليست "مطابقة تمامًا 100%".

الحالة الحالية هي:

- `مطابقة بدرجة عالية على مستوى البنية العامة`
- `مطابقة جيدة جدًا على مستوى أسماء الحقول`
- `غير مكتملة على مستوى التفاصيل الداخلية والمرجعيات وبعض أماكن التخزين`

ولهذا لا يجوز تقنيًا اعتمادها على أنها 100% بعد.
