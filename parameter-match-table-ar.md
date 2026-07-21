# جدول مطابقة الباراميترات مع ملف المتطلبات

هذا الملف يوضح أين وُضع كل باراميتر بعد إعادة الضبط، مع ملاحظة مهمة:

- تم الالتزام بالوثيقة حرفيًا قدر الإمكان
- يوجد طلبان خاصان منك تم أخذهما بعين الاعتبار:
  - `name_ar` و `name_en` على `Product` بدل `SharedProductDetail`
  - إنشاء `manufacturers` و `brands` ككيانات مستقلة مع حقول أسماء

## 1. الباراميترات المشتركة

| الباراميتر | مكانه الحالي | الحالة |
|---|---|---|
| `product_id` | `SharedProductDetail` | مطابق |
| `product_type` | Accessor في `SharedProductDetail` من `Product -> Category -> type` | مطابق وظيفيًا |
| `name_ar` | `Product` | مطابق بطلبك |
| `name_en` | `Product` | مطابق بطلبك |
| `commercial_name` | `SharedProductDetail` | مطابق |
| `aliases` | `SharedProductDetail` | مطابق |
| `barcode` | `SharedProductDetail` | مطابق |
| `sku` | `SharedProductDetail` | مطابق |
| `category_id` | `Product` و Accessor في `SharedProductDetail` | مطابق وظيفيًا |
| `manufacturer_id` | `SharedProductDetail` | مطابق |
| `brand_id` | `SharedProductDetail` | مطابق |
| `country_of_origin` | `SharedProductDetail` | مطابق |
| `registration_number` | `SharedProductDetail` | مطابق |
| `registration_status` | `SharedProductDetail` | مطابق |
| `package_size` | `SharedProductDetail` | مطابق |
| `package_unit` | `SharedProductDetail` | مطابق |
| `short_description` | `SharedProductDetail` | مطابق |
| `approved_description` | `SharedProductDetail` | مطابق |
| `status` | `Product` و Accessor في `SharedProductDetail` | مطابق وظيفيًا |
| `deleted_at` | `SharedProductDetail` | مطابق |

## 2. ملاحظات خاصة بالمشترك

- الوثيقة ذكرت `barcode` مع ملاحظة أنه قد يكون متعددًا حسب حجم العبوة
- لذلك تم الاحتفاظ أيضًا بحقل `barcodes` داخل `SharedProductDetail` كدعم عملي لهذا السيناريو

## 3. جدول `manufacturers`

الكيان المضاف:

- `Manufacturer`

الجدول:

- `manufacturers`

الحقول الحالية:

- `id`
- `name`
- `name_ar`
- `name_en`
- `country`
- `website`
- `status`

## 4. جدول `brands`

الكيان المضاف:

- `Brand`

الجدول:

- `brands`

الحقول الحالية:

- `id`
- `manufacturer_id`
- `name`
- `name_ar`
- `name_en`
- `status`

## 5. العلاقات المرجعية

- `SharedProductDetail` يرتبط مع `Manufacturer` عبر `manufacturer_id`
- `SharedProductDetail` يرتبط مع `Brand` عبر `brand_id`
- `Brand` يرتبط مع `Manufacturer` عبر `manufacturer_id`

## 6. الباراميترات الزراعية

كل الحقول التالية موجودة داخل `AgriculturalProductDetail`:

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
- `pesticide_type`
- `chemical_group`
- `mode_of_action`
- `target_pests`
- `pre_harvest_intervals`
- `re_entry_interval_hours`
- `toxicity_class`
- `environmental_hazards`
- `resistance_management`
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

الحالة:

- مطابق على مستوى الأسماء والبنية العامة

## 7. الباراميترات البيطرية

كل الحقول التالية موجودة داخل `VeterinaryProductDetail`:

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

الحالة:

- مطابق على مستوى الأسماء والبنية العامة

## 8. الخلاصة

بعد إعادة الضبط الحالية:

- تم إرجاع `manufacturer_id` و `brand_id`
- تم إنشاء كيانين مستقلين لهما
- تم الحفاظ على `name_ar` و `name_en` في `Product` بطلب منك
- تم الحفاظ على بنية `مشترك / زراعي / بيطري`
- تم توضيح المطابقة في هذا الملف بشكل مباشر
