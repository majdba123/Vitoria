@php
    $isArabic = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en): string => $isArabic ? $ar : $en;
@endphp

<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('الباراميترات المشتركة', 'Shared Parameters') }}</h2>
    </div>
    <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ([
            ['commercial_name', $t('الاسم التجاري', 'Commercial Name')],
            ['barcode', $t('الباركود الرئيسي', 'Primary Barcode')],
            ['sku', 'SKU'],
            ['manufacturer_id', $t('المصنّع', 'Manufacturer ID'), 'number'],
            ['brand_id', $t('العلامة التجارية', 'Brand ID'), 'number'],
            ['country_of_origin', $t('بلد المنشأ', 'Country Of Origin')],
            ['registration_number', $t('رقم التسجيل', 'Registration Number')],
            ['registration_status', $t('حالة التسجيل', 'Registration Status')],
            ['package_size', $t('حجم العبوة', 'Package Size'), 'number'],
            ['package_unit', $t('وحدة العبوة', 'Package Unit')],
        ] as $sharedField)
            @php
                [$field, $label] = $sharedField;
                $type = $sharedField[2] ?? 'text';
            @endphp
            <div>
                <label for="shared_{{ $field }}" class="form-label">{{ $label }}</label>
                <input id="shared_{{ $field }}" data-request-key="shared_detail[{{ $field }}]" type="{{ $type }}" step="{{ $type === 'number' ? '0.01' : '' }}" class="form-input">
                <p class="form-error" data-error-key="shared_detail.{{ $field }}"></p>
            </div>
        @endforeach

        @foreach ([
            [
                'field' => 'aliases',
                'label' => $t('الأسماء البديلة', 'Aliases'),
                'button' => $t('إضافة اسم بديل', 'Add alias'),
                'placeholder' => $t('أدخل الاسم البديل', 'Enter alias'),
            ],
            [
                'field' => 'barcodes',
                'label' => $t('الباركودات الإضافية', 'Additional Barcodes'),
                'button' => $t('إضافة باركود', 'Add barcode'),
                'placeholder' => $t('أدخل الباركود', 'Enter barcode'),
            ],
            [
                'field' => 'keywords',
                'label' => $t('الكلمات المفتاحية', 'Keywords'),
                'button' => $t('إضافة كلمة مفتاحية', 'Add keyword'),
                'placeholder' => $t('أدخل الكلمة المفتاحية', 'Enter keyword'),
            ],
        ] as $arrayField)
            <div class="sm:col-span-2">
                <label class="form-label">{{ $arrayField['label'] }}</label>
                <div class="space-y-3" data-array-list="shared_detail[{{ $arrayField['field'] }}]" data-array-placeholder="{{ $arrayField['placeholder'] }}">
                    <div class="space-y-2" data-array-items></div>
                    <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $arrayField['button'] }}</button>
                </div>
                <p class="form-error" data-error-key="shared_detail.{{ $arrayField['field'] }}"></p>
            </div>
        @endforeach

        @foreach ([
            ['short_description', $t('الوصف المختصر', 'Short Description')],
            ['approved_description', $t('الوصف المعتمد', 'Approved Description')],
        ] as [$field, $label])
            <div class="sm:col-span-2">
                <label for="shared_{{ $field }}" class="form-label">{{ $label }}</label>
                <textarea id="shared_{{ $field }}" data-request-key="shared_detail[{{ $field }}]" rows="3" class="form-textarea"></textarea>
                <p class="form-error" data-error-key="shared_detail.{{ $field }}"></p>
            </div>
        @endforeach
    </div>
</div>

<div class="card" data-detail-section="agriculture">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('باراميترات المنتجات الزراعية', 'Agricultural Parameters') }}</h2>
    </div>
    <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ([
            ['agricultural_product_type', $t('نوع المنتج الزراعي', 'Agricultural Product Type')],
            ['formulation', $t('التركيبة', 'Formulation')],
            ['max_applications', $t('أقصى عدد للتطبيقات', 'Max Applications')],
            ['application_interval_days', $t('الفاصل بين التطبيقات بالأيام', 'Application Interval Days')],
            ['pesticide_type', $t('نوع المبيد', 'Pesticide Type')],
            ['chemical_group', $t('المجموعة الكيميائية', 'Chemical Group')],
            ['re_entry_interval_hours', $t('مدة إعادة الدخول بالساعات', 'Re Entry Interval Hours'), 'number'],
            ['toxicity_class', $t('درجة السمية', 'Toxicity Class')],
            ['fertilizer_type', $t('نوع السماد', 'Fertilizer Type')],
            ['nutrient_n_percent', $t('نسبة النيتروجين %', 'Nitrogen %'), 'number'],
            ['nutrient_p_percent', $t('نسبة الفوسفور %', 'Phosphorus %'), 'number'],
            ['nutrient_k_percent', $t('نسبة البوتاسيوم %', 'Potassium %'), 'number'],
            ['organic_matter_percent', $t('نسبة المادة العضوية %', 'Organic Matter %'), 'number'],
            ['ph_value', $t('قيمة PH', 'PH Value')],
            ['solubility', $t('الذوبانية', 'Solubility')],
            ['crop_id', $t('المحصول', 'Crop ID'), 'number'],
            ['variety_name', $t('اسم الصنف', 'Variety Name')],
            ['variety_type', $t('نوع الصنف', 'Variety Type')],
            ['germination_percent', $t('نسبة الإنبات %', 'Germination %'), 'number'],
            ['purity_percent', $t('نسبة النقاوة %', 'Purity %'), 'number'],
            ['maturity_days', $t('أيام النضج', 'Maturity Days')],
        ] as $agriculturalField)
            @php
                [$field, $label] = $agriculturalField;
                $type = $agriculturalField[2] ?? 'text';
            @endphp
            <div>
                <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                <input id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" type="{{ $type }}" step="{{ $type === 'number' ? '0.01' : '' }}" class="form-input">
                <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
            </div>
        @endforeach

        @foreach ([
            ['active_ingredients', $t('المواد الفعالة (JSON)', 'Active Ingredients (JSON)')],
            ['target_crops', $t('المحاصيل المستهدفة (JSON)', 'Target Crops (JSON)')],
            ['approved_uses', $t('الاستخدامات المعتمدة (JSON)', 'Approved Uses (JSON)')],
            ['application_methods', $t('طرق التطبيق (JSON)', 'Application Methods (JSON)')],
            ['application_rates', $t('معدلات التطبيق (JSON)', 'Application Rates (JSON)')],
            ['storage_conditions', $t('ظروف التخزين (JSON)', 'Storage Conditions (JSON)')],
            ['warnings', $t('التحذيرات (JSON)', 'Warnings (JSON)')],
            ['ppe_requirements', $t('متطلبات معدات الوقاية (JSON)', 'PPE Requirements (JSON)')],
            ['first_aid', $t('الإسعافات الأولية (JSON)', 'First Aid (JSON)')],
            ['compatibility', $t('التوافق (JSON)', 'Compatibility (JSON)')],
            ['target_pests', $t('الآفات المستهدفة (JSON)', 'Target Pests (JSON)')],
            ['pre_harvest_intervals', $t('فترات ما قبل الحصاد (JSON)', 'Pre Harvest Intervals (JSON)')],
            ['environmental_hazards', $t('المخاطر البيئية (JSON)', 'Environmental Hazards (JSON)')],
            ['micronutrients', $t('العناصر الصغرى (JSON)', 'Micronutrients (JSON)')],
            ['growth_stages', $t('مراحل النمو (JSON)', 'Growth Stages (JSON)')],
            ['fertilization_methods', $t('طرق التسميد (JSON)', 'Fertilization Methods (JSON)')],
            ['seed_treatment', $t('معالجة البذور (JSON)', 'Seed Treatment (JSON)')],
            ['disease_resistance', $t('مقاومة الأمراض (JSON)', 'Disease Resistance (JSON)')],
            ['planting_windows', $t('مواعيد الزراعة (JSON)', 'Planting Windows (JSON)')],
            ['seeding_rate', $t('معدل البذار (JSON)', 'Seeding Rate (JSON)')],
            ['planting_depth', $t('عمق الزراعة (JSON)', 'Planting Depth (JSON)')],
            ['plant_spacing', $t('تباعد النباتات (JSON)', 'Plant Spacing (JSON)')],
            ['expected_yield', $t('الإنتاج المتوقع (JSON)', 'Expected Yield (JSON)')],
        ] as [$field, $label])
            <div class="sm:col-span-2">
                <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                <textarea id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" rows="3" class="form-textarea" placeholder='{} or []'></textarea>
                <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
            </div>
        @endforeach

        @foreach ([
            ['container_disposal', $t('التخلص من العبوة', 'Container Disposal')],
            ['mode_of_action', $t('آلية التأثير', 'Mode Of Action')],
            ['resistance_management', $t('إدارة المقاومة', 'Resistance Management')],
        ] as [$field, $label])
            <div class="sm:col-span-2">
                <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                <textarea id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" rows="3" class="form-textarea"></textarea>
                <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
            </div>
        @endforeach
    </div>
</div>

<div class="card" data-detail-section="veterinary">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('باراميترات المنتجات البيطرية', 'Veterinary Parameters') }}</h2>
    </div>
    <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ([
            ['concentration', $t('التركيز', 'Concentration')],
            ['dosage_form', $t('الشكل الدوائي', 'Dosage Form')],
            ['treatment_duration', $t('مدة العلاج', 'Treatment Duration')],
            ['withdrawal_meat_days', $t('فترة سحب اللحم', 'Withdrawal Meat Days'), 'number'],
            ['withdrawal_milk_days', $t('فترة سحب الحليب', 'Withdrawal Milk Days'), 'number'],
            ['withdrawal_eggs_days', $t('فترة سحب البيض', 'Withdrawal Eggs Days'), 'number'],
            ['shelf_life_after_opening', $t('مدة الصلاحية بعد الفتح', 'Shelf Life After Opening')],
            ['pregnancy_lactation_use', $t('الاستخدام أثناء الحمل والإرضاع', 'Pregnancy Lactation Use')],
        ] as $veterinaryField)
            @php
                [$field, $label] = $veterinaryField;
                $type = $veterinaryField[2] ?? 'text';
            @endphp
            <div>
                <label for="veterinary_{{ $field }}" class="form-label">{{ $label }}</label>
                <input id="veterinary_{{ $field }}" data-request-key="veterinary_detail[{{ $field }}]" type="{{ $type }}" class="form-input">
                <p class="form-error" data-error-key="veterinary_detail.{{ $field }}"></p>
            </div>
        @endforeach

        @foreach ([
            [
                'field' => 'active_ingredients',
                'label' => $t('المواد الفعالة', 'Active Ingredients'),
                'button' => $t('إضافة مادة فعالة', 'Add active ingredient'),
                'placeholder' => $t('أدخل المادة الفعالة', 'Enter active ingredient'),
            ],
            [
                'field' => 'routes_of_administration',
                'label' => $t('طرق الإعطاء', 'Routes Of Administration'),
                'button' => $t('إضافة طريقة إعطاء', 'Add administration route'),
                'placeholder' => $t('أدخل طريقة الإعطاء', 'Enter administration route'),
            ],
            [
                'field' => 'target_species',
                'label' => $t('الأنواع المستهدفة', 'Target Species'),
                'button' => $t('إضافة نوع مستهدف', 'Add target species'),
                'placeholder' => $t('أدخل النوع المستهدف', 'Enter target species'),
            ],
            [
                'field' => 'indications',
                'label' => $t('دواعي الاستعمال', 'Indications'),
                'button' => $t('إضافة داعي استعمال', 'Add indication'),
                'placeholder' => $t('أدخل داعي الاستعمال', 'Enter indication'),
            ],
            [
                'field' => 'dosage_instructions',
                'label' => $t('تعليمات الجرعة', 'Dosage Instructions'),
                'button' => $t('إضافة تعليمات جرعة', 'Add dosage instruction'),
                'placeholder' => $t('أدخل تعليمات الجرعة', 'Enter dosage instruction'),
            ],
            [
                'field' => 'contraindications',
                'label' => $t('موانع الاستعمال', 'Contraindications'),
                'button' => $t('إضافة مانع استعمال', 'Add contraindication'),
                'placeholder' => $t('أدخل مانع الاستعمال', 'Enter contraindication'),
            ],
            [
                'field' => 'warnings',
                'label' => $t('التحذيرات', 'Warnings'),
                'button' => $t('إضافة تحذير', 'Add warning'),
                'placeholder' => $t('أدخل التحذير', 'Enter warning'),
            ],
            [
                'field' => 'adverse_reactions',
                'label' => $t('الآثار الجانبية', 'Adverse Reactions'),
                'button' => $t('إضافة أثر جانبي', 'Add adverse reaction'),
                'placeholder' => $t('أدخل الأثر الجانبي', 'Enter adverse reaction'),
            ],
            [
                'field' => 'drug_interactions',
                'label' => $t('التداخلات الدوائية', 'Drug Interactions'),
                'button' => $t('إضافة تداخل دوائي', 'Add drug interaction'),
                'placeholder' => $t('أدخل التداخل الدوائي', 'Enter drug interaction'),
            ],
            [
                'field' => 'storage_conditions',
                'label' => $t('ظروف التخزين', 'Storage Conditions'),
                'button' => $t('إضافة شرط تخزين', 'Add storage condition'),
                'placeholder' => $t('أدخل شرط التخزين', 'Enter storage condition'),
            ],
        ] as $veterinaryArrayField)
            <div class="sm:col-span-2">
                <label class="form-label">{{ $veterinaryArrayField['label'] }}</label>
                <div class="space-y-3" data-array-list="veterinary_detail[{{ $veterinaryArrayField['field'] }}]" data-array-placeholder="{{ $veterinaryArrayField['placeholder'] }}">
                    <div class="space-y-2" data-array-items></div>
                    <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $veterinaryArrayField['button'] }}</button>
                </div>
                <p class="form-error" data-error-key="veterinary_detail.{{ $veterinaryArrayField['field'] }}"></p>
            </div>
        @endforeach

        @foreach ([
            ['special_precautions', $t('احتياطات خاصة', 'Special Precautions')],
            ['overdose_information', $t('معلومات الجرعة الزائدة', 'Overdose Information')],
            ['disposal_instructions', $t('تعليمات التخلص', 'Disposal Instructions')],
            ['medical_disclaimer', $t('إخلاء المسؤولية الطبية', 'Medical Disclaimer')],
        ] as [$field, $label])
            <div class="sm:col-span-2">
                <label for="veterinary_{{ $field }}" class="form-label">{{ $label }}</label>
                <textarea id="veterinary_{{ $field }}" data-request-key="veterinary_detail[{{ $field }}]" rows="3" class="form-textarea"></textarea>
                <p class="form-error" data-error-key="veterinary_detail.{{ $field }}"></p>
            </div>
        @endforeach
    </div>
</div>
