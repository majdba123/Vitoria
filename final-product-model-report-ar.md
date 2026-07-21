# التقرير النهائي لإنشاء موديلات الباراميترات

## ما تم إنشاؤه

تم إنشاء 3 موديلات للباراميترات، ثم تم جعلها أكثر اتساقًا مع `Product` الحالي عبر ORM:

1. `SharedProductDetail`
2. `AgriculturalProductDetail`
3. `VeterinaryProductDetail`

هذه الموديلات أصبحت أقرب بكثير إلى وثيقة المتطلبات، مع الإبقاء على طلبك الخاص بأن يكون `name_ar` و`name_en` داخل `Product`.

## الملفات التي تم إنشاؤها أو تجهيزها

- `app/Models/SharedProductDetail.php`
- `app/Models/AgriculturalProductDetail.php`
- `app/Models/VeterinaryProductDetail.php`

## وظيفة كل موديل

### `SharedProductDetail`

يحتوي على الباراميترات المشتركة بين جميع المنتجات، مثل:

- `commercial_name`
- `aliases`
- `barcode`
- `barcodes`
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
- `keywords`

أما القيم التالية فلم تعد مخزنة داخل `SharedProductDetail` لتجنب التكرار:

- `category_id`
- `status`
- `product_type`

وأصبحت تُقرأ كالتالي:

- `category_id` من `Product`
- `status` من `Product`
- `product_type` من `Product -> Category -> type`

كما تم نقل:

- `name_ar`
- `name_en`

إلى `Product` نفسه بدل `SharedProductDetail` حتى لا تبقى مكررة مع الحقل القديم `name`.

كما تمت إعادة:

- `manufacturer_id`
- `brand_id`

وربطهما بكيانين مستقلين:

- `Manufacturer`
- `Brand`

### `AgriculturalProductDetail`

يحتوي على باراميترات المنتجات الزراعية بما فيها الحقول العامة للزراعي وتفرعات المبيدات والأسمدة والبذور، مثل:

- `agricultural_product_type`
- `active_ingredients`
- `formulation`
- `target_crops`
- `approved_uses`
- `application_methods`
- `application_rates`
- `max_applications`
- `application_interval_days`
- `storage_conditions`
- `warnings`
- `ppe_requirements`
- `first_aid`
- `container_disposal`
- `compatibility`

وحقول المبيدات:

- `pesticide_type`
- `chemical_group`
- `mode_of_action`
- `target_pests`
- `pre_harvest_intervals`
- `re_entry_interval_hours`
- `toxicity_class`
- `environmental_hazards`
- `resistance_management`

وحقول الأسمدة:

- `fertilizer_type`
- `nutrient_n_percent`
- `nutrient_p_percent`
- `nutrient_k_percent`
- `micronutrients`
- `organic_matter_percent`
- `ph_value`
- `solubility`
- `growth_stages`
- `fertilization_methods`

وحقول البذور:

- `crop_id`
- `variety_name`
- `variety_type`
- `germination_percent`
- `purity_percent`
- `seed_treatment`
- `disease_resistance`
- `planting_windows`
- `seeding_rate`
- `planting_depth`
- `plant_spacing`
- `maturity_days`
- `expected_yield`

### `VeterinaryProductDetail`

يحتوي على الباراميترات الخاصة بالمنتجات البيطرية، مثل:

- `active_ingredients`
- `concentration`
- `dosage_form`
- `routes_of_administration`
- `target_species`
- `indications`
- `dosage_instructions`
- `treatment_duration`
- `contraindications`
- `warnings`
- `special_precautions`
- `adverse_reactions`
- `drug_interactions`
- `pregnancy_lactation_use`
- `withdrawal_meat_days`
- `withdrawal_milk_days`
- `withdrawal_eggs_days`
- `storage_conditions`
- `shelf_life_after_opening`
- `overdose_information`
- `disposal_instructions`
- `medical_disclaimer`

## كيف تم تجهيز الموديلات

داخل كل موديل تم تجهيز:

- اسم الجدول عبر `protected $table`
- الحقول القابلة للتعبئة عبر `protected $fillable`
- التحويلات المناسبة عبر `casts()`
- العلاقات بين الموديلات عبر Eloquent ORM

## العلاقات التي تم إضافتها

- `SharedProductDetail` يملك علاقة `hasOne` مع `AgriculturalProductDetail`
- `SharedProductDetail` يملك علاقة `hasOne` مع `VeterinaryProductDetail`
- `SharedProductDetail` يملك علاقة `belongsTo` مع `Product`
- `SharedProductDetail` يملك علاقة `belongsTo` مع `Manufacturer`
- `SharedProductDetail` يملك علاقة `belongsTo` مع `Brand`
- `AgriculturalProductDetail` يملك علاقة `belongsTo` مع `SharedProductDetail`
- `VeterinaryProductDetail` يملك علاقة `belongsTo` مع `SharedProductDetail`
- `Product` يملك علاقة `hasOne` مع `SharedProductDetail`
- `Manufacturer` يملك علاقة `hasMany` مع `Brand`
- `Manufacturer` يملك علاقة `hasMany` مع `SharedProductDetail`
- `Brand` يملك علاقة `belongsTo` مع `Manufacturer`
- `Brand` يملك علاقة `hasMany` مع `SharedProductDetail`

المفاتيح المستخدمة في الربط هي:

- `product_id` بين `Product` و `SharedProductDetail`
- `shared_product_detail_id`

## ملاحظات مهمة

- هذه الخطوة أنشأت الموديلات وعدلت `Product` لتحقيق الاتساق.
- تم إنشاء `migrations` لجدولي `manufacturers` و `brands`.
- تم ربط `SharedProductDetail` مع `Product`.
- تم حذف `subcategory_id` لأنه غير موجود في نظامك.
- تم نقل `name_ar` و`name_en` إلى `Product` على مستوى الموديل.
- تم إرجاع `manufacturer_id` و`brand_id` وربطهما بكيانات مرجعية.
- تم الاحتفاظ أيضًا بـ `barcodes` دعمًا لحالة تعدد الأكواد.

## ملاحظات تصميمية

- الحقول التي تمثل قوائم أو عناصر متعددة تم إعدادها كـ `array` في `casts()`
- الحقول الرقمية مثل النسب أو القيم المئوية تم إعدادها كـ `decimal`
- الحقول التي تمثل أرقام أيام أو ساعات تم إعدادها كـ `integer`

## الخطوة التالية المقترحة

إذا أردت في المرحلة القادمة، يمكن تنفيذ واحدة من الخطوتين:

1. إنشاء `migrations` للجداول الثلاثة الأساسية الخاصة بالتفاصيل
2. تعديل Requests و Resources لتستخدم الحقول الجديدة

## الخلاصة

تمت إعادة الضبط بحيث تصبح البنية أقرب ما يمكن إلى الوثيقة، مع الحفاظ فقط على التخصيصات التي طلبتها أنت مباشرة داخل `Product` والجداول المرجعية.
