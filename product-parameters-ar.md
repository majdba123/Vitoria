# باراميترات المنتجات حسب المتطلبات

هذا الملف يلخص الباراميترات المطلوبة في الوثيقة، ويقسمها إلى:

- باراميترات مشتركة بين جميع المنتجات
- باراميترات خاصة بالمنتجات الزراعية
- باراميترات خاصة بالمنتجات البيطرية

## 1. الباراميترات المشتركة بين جميع المنتجات

هذه الحقول تنطبق على كل منتج مهما كان نوعه:

| الاسم البرمجي | الشرح |
|---|---|
| `product_id` | معرف ثابت وفريد للمنتج |
| `product_type` | نوع المنتج العام مثل بيطري أو مبيد أو سماد أو بذور |
| `name_ar` | الاسم العربي |
| `name_en` | الاسم الإنجليزي |
| `commercial_name` | الاسم التجاري الظاهر على العبوة |
| `aliases` | أسماء بديلة أو اختصارات أو كتابات مختلفة |
| `barcode` | الباركود المعتمد للمنتج |
| `sku` | رمز SKU الداخلي |
| `category_id` | التصنيف الرئيسي |
| `subcategory_id` | التصنيف الفرعي |
| `manufacturer_id` | الشركة المصنعة |
| `brand_id` | العلامة التجارية |
| `country_of_origin` | بلد المنشأ |
| `registration_number` | رقم التسجيل أو الترخيص |
| `registration_status` | حالة التسجيل |
| `package_size` | حجم العبوة كرقم |
| `package_unit` | وحدة العبوة مثل ml أو L أو g أو kg |
| `short_description` | وصف مختصر |
| `approved_description` | الوصف المعتمد الذي يستخدم في RAG |
| `keywords` | كلمات مفتاحية للبحث |
| `status` | حالة المنتج مثل active أو inactive |
| `created_at` | تاريخ الإنشاء |
| `updated_at` | تاريخ آخر تحديث |
| `deleted_at` | تاريخ الحذف المنطقي إن وجد |

## 2. الباراميترات الخاصة بالمنتجات الزراعية

هذه الحقول تستخدم عندما يكون المنتج زراعيًا.

### 2.1 باراميترات زراعية مشتركة

| الاسم البرمجي | الشرح |
|---|---|
| `agricultural_product_type` | نوع المنتج الزراعي مثل مبيد أو سماد أو بذور أو محسن تربة |
| `active_ingredients` | المواد الفعالة مع الاسم والنسبة أو الوحدة |
| `formulation` | التركيبة مثل EC أو WP أو SC أو WG أو SL |
| `target_crops` | المحاصيل المستهدفة |
| `approved_uses` | الاستخدامات المعتمدة حسب المحصول والهدف |
| `application_methods` | طرق التطبيق مثل رش أو تربة أو تنقيط أو معاملة بذور |
| `application_rates` | معدلات الاستخدام بالقيمة والوحدة |
| `max_applications` | الحد الأقصى لعدد مرات الاستخدام |
| `application_interval_days` | الفاصل بين التطبيقات |
| `storage_conditions` | شروط التخزين |
| `warnings` | التحذيرات الصحية أو البيئية أو التشغيلية |
| `ppe_requirements` | معدات الوقاية الشخصية |
| `first_aid` | الإسعافات الأولية |
| `container_disposal` | طريقة التخلص من العبوة |
| `compatibility` | التوافق أو الخلط مع منتجات أخرى |

### 2.2 باراميترات خاصة إذا كان المنتج مبيدًا

تستخدم عندما يكون:
`agricultural_product_type = pesticide`

| الاسم البرمجي | الشرح |
|---|---|
| `pesticide_type` | نوع المبيد مثل حشري أو فطري أو عشبي |
| `chemical_group` | المجموعة الكيميائية |
| `mode_of_action` | آلية التأثير |
| `target_pests` | الآفات المستهدفة |
| `pre_harvest_intervals` | فترة الأمان قبل الحصاد |
| `re_entry_interval_hours` | فترة إعادة الدخول للحقل |
| `toxicity_class` | درجة السمية |
| `environmental_hazards` | الأثر أو المخاطر البيئية |
| `resistance_management` | إدارة المقاومة |

### 2.3 باراميترات خاصة إذا كان المنتج سمادًا

تستخدم عندما يكون:
`agricultural_product_type = fertilizer`

| الاسم البرمجي | الشرح |
|---|---|
| `fertilizer_type` | نوع السماد |
| `nutrient_n_percent` | نسبة النيتروجين |
| `nutrient_p_percent` | نسبة الفوسفور |
| `nutrient_k_percent` | نسبة البوتاسيوم |
| `micronutrients` | العناصر الصغرى |
| `organic_matter_percent` | نسبة المادة العضوية |
| `ph_value` | درجة الحموضة |
| `solubility` | الذوبانية |
| `growth_stages` | مراحل النمو المناسبة |
| `fertilization_methods` | طرق التسميد |

### 2.4 باراميترات خاصة إذا كان المنتج بذورًا

تستخدم عندما يكون:
`agricultural_product_type = seed`

| الاسم البرمجي | الشرح |
|---|---|
| `crop_id` | نوع المحصول |
| `variety_name` | اسم الصنف |
| `variety_type` | نوع الصنف |
| `germination_percent` | نسبة الإنبات |
| `purity_percent` | نسبة النقاوة |
| `seed_treatment` | معاملة البذور |
| `disease_resistance` | مقاومة الأمراض |
| `planting_windows` | مواعيد الزراعة |
| `seeding_rate` | معدل البذار |
| `planting_depth` | عمق الزراعة |
| `plant_spacing` | المسافات بين النباتات والخطوط |
| `maturity_days` | مدة النضج |
| `expected_yield` | الإنتاج المتوقع |

## 3. الباراميترات الخاصة بالمنتجات البيطرية

هذه الحقول تستخدم عندما يكون:
`product_type = veterinary_medicine`

| الاسم البرمجي | الشرح |
|---|---|
| `active_ingredients` | المواد الفعالة مع الاسم والتركيز والوحدة |
| `concentration` | تركيز المنتج مثل 15% أو 100 mg/ml |
| `dosage_form` | الشكل الدوائي مثل injection أو tablet أو powder |
| `routes_of_administration` | طرق الإعطاء مثل oral أو IM أو IV أو SC أو topical |
| `target_species` | الحيوانات المستهدفة |
| `indications` | دواعي الاستعمال |
| `dosage_instructions` | الجرعات حسب الحيوان والمدة والتكرار وطريق الإعطاء |
| `treatment_duration` | مدة العلاج |
| `contraindications` | موانع الاستعمال |
| `warnings` | التحذيرات |
| `special_precautions` | الاحتياطات الخاصة |
| `adverse_reactions` | الآثار الجانبية |
| `drug_interactions` | التداخلات الدوائية |
| `pregnancy_lactation_use` | الاستخدام أثناء الحمل والإرضاع |
| `withdrawal_meat_days` | فترة سحب اللحوم |
| `withdrawal_milk_days` | فترة سحب الحليب |
| `withdrawal_eggs_days` | فترة سحب البيض |
| `storage_conditions` | شروط التخزين |
| `shelf_life_after_opening` | مدة الصلاحية بعد الفتح |
| `overdose_information` | معلومات الجرعة الزائدة |
| `disposal_instructions` | التخلص الآمن من المنتج أو العبوة |
| `medical_disclaimer` | تنبيه طبي بأن المحتوى لا يغني عن الطبيب البيطري |

## 4. الفرق السريع بين الزراعي والبيطري

- المنتج البيطري يركز على: الحيوان، المرض، الجرعة، طريقة الإعطاء، وفترات السحب.
- المنتج الزراعي يركز على: المحصول، الهدف الزراعي، طريقة التطبيق، المعدل، والسلامة الزراعية.
- بعض الحقول تتكرر بين النوعين مثل `active_ingredients` و`warnings` و`storage_conditions` لكن معنى الاستخدام يختلف حسب المجال.
