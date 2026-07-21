# شرح تفصيلي لفصل باراميترات المنتجات الزراعية والبيطرية عن جدول `products`

هذا الملف يشرح بالتفصيل كيف تجعل الباراميترات الخاصة بالمنتجات الزراعية والبيطرية متوافقة مع مشروعك الحالي، بدون أن تؤثر بشكل سلبي على جدول `products` الموجود أصلًا.

## 1. وضع المشروع الحالي عندك

من خلال الكود الحالي، جدول `products` عندك هو الجدول الأساسي الذي يعتمد عليه النظام في:

- عرض المنتجات
- إدارة منتجات التاجر
- الأسعار
- الكمية
- الصور
- الخصومات
- الطلبات

والحقول الأساسية الحالية الموجودة فيه هي تقريبًا:

- `id`
- `vendor_id`
- `category_id`
- `name`
- `description`
- `icon`
- `image`
- `price`
- `discount_percentage`
- `quantity`
- `is_active`
- `discount_is_active`
- `discount_starts_at`
- `discount_ends_at`
- `discount_status`
- `status`
- `rejection_reason`

وهذا يعني أن جدول `products` عندك حاليًا هو جدول تشغيل أساسي للتجارة والبيع، وليس جدولًا علميًا أو تخصصيًا لتخزين كل تفاصيل المنتجات البيطرية والزراعية.

## 2. لماذا لا ننصح بإضافة كل الباراميترات داخل جدول `products`؟

إذا أضفت كل الحقول الخاصة بالزراعي والبيطري داخل نفس جدول `products` فستظهر عندك مشاكل واضحة:

- سيصبح الجدول ضخمًا جدًا
- كثير من الأعمدة ستكون فارغة `null`
- المنتج البيطري لن يحتاج حقول السماد أو البذور
- المنتج الزراعي لن يحتاج حقول الجرعات البيطرية أو فترات سحب الحليب والبيض
- ستتعقد الفورمات والتحقق `validation`
- ستزداد صعوبة الصيانة والتطوير مستقبلًا
- قد يتأثر الأداء والوضوح في الكود

مثال:

- المنتج البيطري يحتاج `withdrawal_milk_days`
- السماد يحتاج `nutrient_n_percent`
- البذور تحتاج `germination_percent`

من غير المنطقي وضع كل هذه الأعمدة في نفس الجدول الرئيسي.

## 3. الفكرة الصحيحة في حالتك

الحل الأنسب هو:

- يبقى جدول `products` كما هو تقريبًا
- نعتبره الجدول العام والأساسي
- ننشئ جداول وموديلات إضافية للتفاصيل التخصصية

أي أن التصميم يكون على شكل:

1. جدول عام للمنتج
2. جدول تفاصيل زراعية
3. جدول تفاصيل بيطرية

## 4. الشكل المقترح للبنية

### 4.1 جدول `products`

يبقى هذا الجدول مسؤولًا عن البيانات المشتركة والأساسية فقط، مثل:

- اسم المنتج
- الوصف
- السعر
- الكمية
- التصنيف
- التاجر
- حالة المنتج
- الصور العامة
- الخصومات

بمعنى آخر:

هذا الجدول يمثل "المنتج التجاري" داخل المنصة.

### 4.2 جدول `agricultural_product_details`

هذا جدول منفصل يحتوي فقط على التفاصيل الخاصة بالمنتجات الزراعية.

يحتوي مثلًا على:

- `id`
- `product_id`
- `agricultural_product_type`
- `formulation`
- `max_applications`
- `application_interval_days`
- `storage_conditions`
- `warnings`
- `ppe_requirements`
- `first_aid`
- `container_disposal`
- `compatibility`

### 4.3 جدول `veterinary_product_details`

هذا جدول منفصل يحتوي فقط على التفاصيل الخاصة بالمنتجات البيطرية.

يحتوي مثلًا على:

- `id`
- `product_id`
- `concentration`
- `dosage_form`
- `treatment_duration`
- `contraindications`
- `warnings`
- `special_precautions`
- `pregnancy_lactation_use`
- `withdrawal_meat_days`
- `withdrawal_milk_days`
- `withdrawal_eggs_days`
- `storage_conditions`
- `shelf_life_after_opening`
- `overdose_information`
- `disposal_instructions`
- `medical_disclaimer`

## 5. كيف نربط هذه الجداول مع جدول `products`؟

العلاقة الصحيحة هنا هي:

- كل `Product` يمكن أن يملك سجل تفاصيل واحد فقط
- إما تفاصيل زراعية
- أو تفاصيل بيطرية

إذًا العلاقة تكون:

- `products` 1:1 `agricultural_product_details`
- `products` 1:1 `veterinary_product_details`

أي باستخدام `product_id` داخل جدول التفاصيل.

## 6. كيف نعرف إن كان المنتج زراعيًا أم بيطريًا؟

في مشروعك الحالي يوجد أصلًا تمييز عبر `categories.type`

والأنواع الموجودة عندك هي:

- `agriculture`
- `veterinary`

وهذا ممتاز جدًا، لأنه يعني أننا لا نحتاج بالضرورة إلى إضافة عمود جديد داخل `products` اسمه `product_type` في المرحلة الأولى.

بدل ذلك:

- إذا كانت `category.type = agriculture` إذًا المنتج زراعي
- إذا كانت `category.type = veterinary` إذًا المنتج بيطري

وهذا يجعلك منسجمًا مع البنية الحالية بدل إدخال منطق جديد قد يربك المشروع.

## 7. ما الذي يبقى داخل جدول `products`؟

الذي يبقى داخل `products` هو فقط ما يعتبر معلومات مشتركة أو تشغيلية للمنصة.

### 7.1 الحقول التي تبقى كما هي

- `vendor_id`
- `category_id`
- `name`
- `description`
- `icon`
- `image`
- `price`
- `quantity`
- `is_active`
- `status`
- `discount_percentage`
- `discount_is_active`
- `discount_starts_at`
- `discount_ends_at`
- `discount_status`
- `rejection_reason`

### 7.2 ما يمكن إضافته لاحقًا داخل `products` إن احتجت

إذا أردت التوافق أكثر مع وثيقة المتطلبات، يمكن لاحقًا إضافة بعض الحقول المشتركة أيضًا إلى `products` لأنها عامة ومفيدة لكل الأنواع:

- `name_ar`
- `name_en`
- `commercial_name`
- `barcode`
- `sku`
- `manufacturer_id`
- `brand_id`
- `country_of_origin`
- `registration_number`
- `registration_status`
- `package_size`
- `package_unit`
- `short_description`
- `approved_description`

لكن هذه الإضافة ليست شرطًا حتى تبدأ فصل الزراعي والبيطري.

## 8. ما الذي يجب ألا نضعه داخل `products`؟

هذه الحقول لا يفضل وضعها داخل `products` لأنها تخصصية:

### 8.1 حقول خاصة بالبيطري

- `concentration`
- `dosage_form`
- `routes_of_administration`
- `target_species`
- `indications`
- `dosage_instructions`
- `treatment_duration`
- `contraindications`
- `adverse_reactions`
- `drug_interactions`
- `pregnancy_lactation_use`
- `withdrawal_meat_days`
- `withdrawal_milk_days`
- `withdrawal_eggs_days`
- `medical_disclaimer`

### 8.2 حقول خاصة بالزراعي

- `agricultural_product_type`
- `formulation`
- `target_crops`
- `approved_uses`
- `application_methods`
- `application_rates`
- `max_applications`
- `application_interval_days`
- `ppe_requirements`
- `first_aid`
- `container_disposal`
- `compatibility`

### 8.3 حقول خاصة بتفرعات الزراعي

مثل:

- `pesticide_type`
- `chemical_group`
- `mode_of_action`
- `target_pests`
- `pre_harvest_intervals`
- `fertilizer_type`
- `nutrient_n_percent`
- `nutrient_p_percent`
- `nutrient_k_percent`
- `germination_percent`
- `purity_percent`
- `variety_name`

كل هذه يفضل أن تبقى خارج `products`.

## 9. كيف نقسم الباراميترات بشكل عملي؟

### 9.1 المستوى الأول: بيانات عامة داخل `products`

هذه هي البيانات التي تحتاجها أغلب الشاشات:

- الاسم
- السعر
- الكمية
- الحالة
- الصورة
- الفئة
- التاجر

### 9.2 المستوى الثاني: بيانات تخصصية في جدول منفصل

إذا فتح المستخدم صفحة المنتج أو إذا احتاجت شاشة الإدارة بيانات متقدمة، نقوم بتحميل جدول التفاصيل المناسب.

مثال:

- إذا كان المنتج زراعيًا نحمل `agriculturalDetail`
- إذا كان المنتج بيطريًا نحمل `veterinaryDetail`

## 10. كيف تكون العلاقات في الموديلات؟

### 10.1 داخل موديل `Product`

يمكن أن تضيف علاقتين:

```php
public function agriculturalDetail(): HasOne
{
    return $this->hasOne(AgriculturalProductDetail::class);
}

public function veterinaryDetail(): HasOne
{
    return $this->hasOne(VeterinaryProductDetail::class);
}
```

### 10.2 داخل موديل `AgriculturalProductDetail`

```php
public function product(): BelongsTo
{
    return $this->belongsTo(Product::class);
}
```

### 10.3 داخل موديل `VeterinaryProductDetail`

```php
public function product(): BelongsTo
{
    return $this->belongsTo(Product::class);
}
```

## 11. كيف يكون شكل الجداول المقترحة؟

## 11.1 مثال لجدول `agricultural_product_details`

```text
id
product_id
agricultural_product_type
formulation
max_applications
application_interval_days
storage_conditions
warnings
ppe_requirements
first_aid
container_disposal
compatibility
created_at
updated_at
```

## 11.2 مثال لجدول `veterinary_product_details`

```text
id
product_id
concentration
dosage_form
treatment_duration
contraindications
warnings
special_precautions
pregnancy_lactation_use
withdrawal_meat_days
withdrawal_milk_days
withdrawal_eggs_days
storage_conditions
shelf_life_after_opening
overdose_information
disposal_instructions
medical_disclaimer
created_at
updated_at
```

## 12. ماذا عن الحقول المتعددة مثل `active_ingredients` و `target_species`؟

هذه نقطة مهمة جدًا.

بعض الحقول ليست قيمة واحدة، بل قائمة من العناصر. مثل:

- `active_ingredients`
- `target_species`
- `routes_of_administration`
- `target_crops`
- `application_methods`
- `approved_uses`
- `application_rates`

هنا عندك خياران:

### الخيار الأول: تخزينها كـ JSON

وهذا مناسب إذا كنت تريد:

- سرعة في البداية
- مرونة
- عدد جداول أقل
- تنفيذ أسرع

مثال:

- `active_ingredients` كعمود `json`
- `target_species` كعمود `json`
- `application_rates` كعمود `json`

هذا ممتاز كبداية.

### الخيار الثاني: جداول فرعية مستقلة

وهذا مناسب إذا كنت تريد:

- فلترة متقدمة
- بحث دقيق
- تقارير
- علاقات مرجعية قوية

مثال:

- `veterinary_active_ingredients`
- `veterinary_target_species`
- `agricultural_target_crops`
- `agricultural_application_rates`

## 13. ما الخيار الأفضل لك الآن؟

بحسب مشروعك الحالي، الأفضل في المرحلة الأولى:

- `products` يبقى بسيطًا
- إنشاء `AgriculturalProductDetail`
- إنشاء `VeterinaryProductDetail`
- تخزين القوائم المعقدة في أعمدة `json` في البداية

والسبب:

- أسرع تنفيذًا
- أقل مخاطرة على النظام الحالي
- لا يحتاج تفكيكًا كبيرًا للكود
- يسهل لاحقًا التطوير التدريجي

## 14. مثال عملي على التصميم النهائي

### مثال منتج بيطري

### داخل `products`

```text
id = 10
category_id = 4
name = VetAmox
price = 15000
quantity = 20
status = approved
```

### داخل `veterinary_product_details`

```text
product_id = 10
concentration = 15%
dosage_form = injection
warnings = ...
contraindications = ...
withdrawal_meat_days = 14
withdrawal_milk_days = 3
target_species = JSON
dosage_instructions = JSON
active_ingredients = JSON
```

### مثال منتج زراعي

### داخل `products`

```text
id = 11
category_id = 2
name = AgroSafe
price = 22000
quantity = 50
status = approved
```

### داخل `agricultural_product_details`

```text
product_id = 11
agricultural_product_type = pesticide
formulation = EC
warnings = ...
storage_conditions = ...
application_methods = JSON
target_crops = JSON
application_rates = JSON
target_pests = JSON
pre_harvest_intervals = JSON
```

## 15. كيف تنعكس هذه البنية على الفورمات؟

بدل أن تضع كل الحقول في فورم واحد ضخم، يكون عندك منطق أوضح:

### إذا كان المنتج زراعيًا

تعرض:

- الحقول العامة من `products`
- ثم الحقول الزراعية من `agricultural_product_details`

### إذا كان المنتج بيطريًا

تعرض:

- الحقول العامة من `products`
- ثم الحقول البيطرية من `veterinary_product_details`

وهذا يجعل الواجهة أوضح بكثير.

## 16. كيف تنعكس هذه البنية على الـ Validation؟

هذا أيضًا يصبح أوضح:

- التحقق العام يبقى على `StoreProductRequest`
- ويمكن لاحقًا إنشاء:
  - `StoreAgriculturalProductDetailRequest`
  - `StoreVeterinaryProductDetailRequest`

وبذلك:

- لا تختلط قواعد التحقق
- كل نوع يملك قواعده الخاصة
- يقل التعقيد

## 17. كيف تنعكس على الـ API Resource؟

في `ProductResource` الحالي عندك، يمكن لاحقًا أن تضيف:

- `product_kind` أو اعتماد `category.type`
- `agricultural_detail`
- `veterinary_detail`

بحيث تعيد الـ API:

- البيانات العامة دائمًا
- والبيانات التخصصية فقط عند وجودها

## 18. هل يجب إنشاء موديل واحد فقط اسمه `ProductDetail`؟

يمكن ذلك، لكن في مشروعك الحالي لا أنصح به كبداية.

لأن موديل واحد عام سيجعل الجدول نفسه يعود مزدحمًا جدًا بالحقول المتنوعة:

- حقول بيطرية
- حقول زراعية
- حقول مبيدات
- حقول أسمدة
- حقول بذور

وبذلك نعود لنفس المشكلة لكن في جدول آخر.

الأفضل هو فصل منطقي واضح:

- `AgriculturalProductDetail`
- `VeterinaryProductDetail`

## 19. هل نحتاج أكثر من جدول داخل الزراعي؟

ليس في البداية بالضرورة.

يمكن أن تبدأ بجدول واحد:

- `agricultural_product_details`

ثم تضع داخله:

- الحقول العامة للزراعي
- وبعض الحقول المتخصصة كـ JSON

ثم لاحقًا إذا احتجت دقة أعلى:

- تفصل جدولًا للمبيدات
- أو للأسمدة
- أو للبذور

مثال تطوير لاحق:

- `agricultural_pesticide_details`
- `agricultural_fertilizer_details`
- `agricultural_seed_details`

لكن هذا ليس ضروريًا من أول خطوة.

## 20. التوصية النهائية الأنسب لمشروعك

التوصية الأنسب لك الآن هي:

1. لا تغيّر بنية `products` الحالية بشكل جذري
2. أبقِ `products` للبيانات العامة والتجارية
3. استخدم `category.type` لتحديد هل المنتج زراعي أو بيطري
4. أنشئ موديلين منفصلين:
   - `AgriculturalProductDetail`
   - `VeterinaryProductDetail`
5. اربط كل واحد منهما مع `Product` بعلاقة `one-to-one`
6. خزّن القوائم المعقدة في `json` في البداية
7. طوّرها لاحقًا إلى جداول مستقلة إذا احتجت بحثًا وتحليلات أعمق

## 21. النتيجة التي ستحصل عليها

إذا طبقت هذا الأسلوب فستكسب:

- عدم كسر جدول `products` الحالي
- الحفاظ على التوافق مع النظام الموجود
- بنية مرتبة وقابلة للتوسع
- سهولة أكبر في الفورمات والـ validation
- وضوح أكبر في الـ API
- سهولة إضافة متطلبات Vetora لاحقًا بدون فوضى

## 22. خلاصة مختصرة جدًا

`products` عندك يجب أن يبقى الجدول الأساسي العام.

الباراميترات الخاصة:

- الزراعي تذهب إلى `agricultural_product_details`
- البيطري تذهب إلى `veterinary_product_details`

ولا تجعل كل هذه الأعمدة داخل `products` لأن ذلك سيعقد النظام ويؤثر على وضوحه وصيانته.

## 23. إذا أردت التنفيذ لاحقًا

عندما تريد التنفيذ الفعلي في المشروع، الخطوات ستكون:

1. إنشاء Migration لجدول `agricultural_product_details`
2. إنشاء Migration لجدول `veterinary_product_details`
3. إنشاء موديلين جديدين
4. إضافة العلاقات داخل `Product`
5. إضافة Requests خاصة بالتفاصيل
6. تعديل الـ Resource لإرجاع التفاصيل عند وجودها
7. تعديل شاشات الإنشاء أو التعديل لإظهار الحقول حسب نوع المنتج

إذا رغبت، أستطيع في الخطوة التالية أن أنفذ لك هذا فعليًا في الكود داخل المشروع، وليس فقط شرحًا نظريًا.
