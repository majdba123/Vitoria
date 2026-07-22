@php
    $isArabic = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en): string => $isArabic ? $ar : $en;
    $sharedRepeaterFields = [
        [
            'field' => 'barcodes',
            'label' => $t('الباركودات', 'Barcodes'),
            'button' => $t('إضافة باركود', 'Add barcode'),
            'placeholder' => $t('أدخل باركود المنتج', 'Enter product barcode'),
            'hint' => $t('يمكنك إدخال أكثر من باركود لنفس المنتج بدون باركود مفرد مستقل.', 'You can enter multiple barcodes for the same product without a separate single barcode field.'),
        ],
        [
            'field' => 'aliases',
            'label' => $t('الأسماء البديلة', 'Aliases'),
            'button' => $t('إضافة اسم بديل', 'Add alias'),
            'placeholder' => $t('أدخل اسمًا بديلًا', 'Enter alias'),
            'hint' => null,
        ],
        [
            'field' => 'keywords',
            'label' => $t('الكلمات المفتاحية', 'Keywords'),
            'button' => $t('إضافة كلمة مفتاحية', 'Add keyword'),
            'placeholder' => $t('أدخل كلمة مفتاحية', 'Enter keyword'),
            'hint' => null,
        ],
    ];

    $agriculturalTypeOptions = [
        '' => $t('اختر النوع الزراعي', 'Select agricultural type'),
        'pesticide' => $t('مبيد', 'Pesticide'),
        'fertilizer' => $t('سماد', 'Fertilizer'),
        'seed' => $t('بذور', 'Seeds'),
        'soil_amendment' => $t('محسن تربة', 'Soil Amendment'),
        'growth_regulator' => $t('منظم نمو', 'Growth Regulator'),
        'other' => $t('أخرى', 'Other'),
    ];
@endphp

<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('البيانات المشتركة', 'Shared Parameters') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $t('هذه الحقول تظهر لكل منتج مهما كان نوعه.', 'These fields apply to every product regardless of type.') }}</p>
    </div>
    <div class="card-body space-y-6">
        <div class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2" data-agri-subtype="pesticide fertilizer soil_amendment growth_regulator other">
            @foreach ([
                ['commercial_name', $t('الاسم التجاري', 'Commercial Name')],
                ['sku', 'SKU'],
                ['manufacturer_name_ar', $t('اسم الشركة المصنعة بالعربي', 'Manufacturer Name (AR)')],
                ['manufacturer_name_en', $t('اسم الشركة المصنعة بالإنجليزي', 'Manufacturer Name (EN)')],
                ['brand_name_ar', $t('اسم العلامة بالعربي', 'Brand Name (AR)')],
                ['brand_name_en', $t('اسم العلامة بالإنجليزي', 'Brand Name (EN)')],
                ['country_of_origin', $t('بلد المنشأ', 'Country Of Origin')],
                ['registration_number', $t('رقم التسجيل', 'Registration Number')],
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

            <div>
                <label for="shared_registration_status" class="form-label">{{ $t('حالة التسجيل', 'Registration Status') }}</label>
                <select id="shared_registration_status" data-request-key="shared_detail[registration_status]" class="form-select">
                    <option value="">{{ $t('اختر الحالة', 'Select status') }}</option>
                    <option value="registered">{{ $t('مسجل', 'Registered') }}</option>
                    <option value="pending">{{ $t('قيد المراجعة', 'Pending Review') }}</option>
                    <option value="expired">{{ $t('منتهي', 'Expired') }}</option>
                    <option value="unregistered">{{ $t('غير مسجل', 'Unregistered') }}</option>
                </select>
                <p class="form-error" data-error-key="shared_detail.registration_status"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ($sharedRepeaterFields as $arrayField)
                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4">
                    <label class="form-label">{{ $arrayField['label'] }}</label>
                    @if ($arrayField['hint'])
                        <p class="mb-3 text-xs text-gray-500">{{ $arrayField['hint'] }}</p>
                    @endif
                    <div class="space-y-3" data-array-list="shared_detail[{{ $arrayField['field'] }}]" data-array-placeholder="{{ $arrayField['placeholder'] }}">
                        <div class="space-y-2" data-array-items></div>
                        <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $arrayField['button'] }}</button>
                    </div>
                    <p class="form-error" data-error-key="shared_detail.{{ $arrayField['field'] }}"></p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ([
                ['short_description', $t('وصف مختصر', 'Short Description')],
                ['approved_description', $t('الوصف المعتمد', 'Approved Description')],
            ] as [$field, $label])
                <div>
                    <label for="shared_{{ $field }}" class="form-label">{{ $label }}</label>
                    <textarea id="shared_{{ $field }}" data-request-key="shared_detail[{{ $field }}]" rows="4" class="form-textarea"></textarea>
                    <p class="form-error" data-error-key="shared_detail.{{ $field }}"></p>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card" data-detail-section="agriculture">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('تفاصيل المنتجات الزراعية', 'Agricultural Parameters') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $t('اختر نوع المنتج الزراعي لإظهار الحقول المناسبة فقط.', 'Select the agricultural product type to reveal only the relevant fields.') }}</p>
    </div>
    <div class="card-body space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="hidden">
                <label for="agricultural_agricultural_product_type" class="form-label">{{ $t('نوع المنتج الزراعي', 'Agricultural Product Type') }}</label>
                <select id="agricultural_agricultural_product_type" data-request-key="agricultural_detail[agricultural_product_type]" class="form-select">
                    @foreach ($agriculturalTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="form-error" data-error-key="agricultural_detail.agricultural_product_type"></p>
            </div>
            <div>
                <label for="agricultural_formulation" class="form-label">{{ $t('التركيبة / الصيغة', 'Formulation') }}</label>
                <input id="agricultural_formulation" data-request-key="agricultural_detail[formulation]" type="text" class="form-input">
                <p class="form-error" data-error-key="agricultural_detail.formulation"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-agri-subtype="pesticide fertilizer soil_amendment growth_regulator other">
            @foreach ([
                ['approved_uses', $t('الاستخدامات المعتمدة', 'Approved Uses'), $t('إضافة استخدام', 'Add use'), $t('أدخل استخدامًا معتمدًا', 'Enter approved use')],
                ['application_methods', $t('طرق التطبيق', 'Application Methods'), $t('إضافة طريقة', 'Add method'), $t('أدخل طريقة التطبيق', 'Enter application method')],
                ['application_rates', $t('معدلات التطبيق', 'Application Rates'), $t('إضافة معدل', 'Add rate'), $t('أدخل معدل التطبيق', 'Enter application rate')],
                ['storage_conditions', $t('ظروف التخزين', 'Storage Conditions'), $t('إضافة شرط', 'Add condition'), $t('أدخل شرط التخزين', 'Enter storage condition')],
                ['warnings', $t('التحذيرات', 'Warnings'), $t('إضافة تحذير', 'Add warning'), $t('أدخل تحذيرًا', 'Enter warning')],
                ['growth_stages', $t('مراحل النمو المناسبة', 'Growth Stages'), $t('إضافة مرحلة', 'Add stage'), $t('أدخل مرحلة نمو', 'Enter growth stage')],
            ] as [$field, $label, $button, $placeholder])
                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4">
                    <label class="form-label">{{ $label }}</label>
                    <div class="space-y-3" data-array-list="agricultural_detail[{{ $field }}]" data-array-placeholder="{{ $placeholder }}">
                        <div class="space-y-2" data-array-items></div>
                        <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $button }}</button>
                    </div>
                    <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                </div>
            @endforeach
        </div>

        <div class="space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4" data-agri-subtype="pesticide">
            <div>
                <h3 class="text-base font-bold text-gray-900">{{ $t('حقول المبيدات', 'Pesticide Fields') }}</h3>
                <p class="text-sm text-gray-500">{{ $t('تظهر عند اختيار نوع المنتج: مبيد.', 'Visible when the agricultural product type is pesticide.') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ([
                    ['pesticide_type', $t('نوع المبيد', 'Pesticide Type')],
                    ['chemical_group', $t('المجموعة الكيميائية', 'Chemical Group')],
                    ['re_entry_interval_hours', $t('مدة إعادة الدخول بالساعات', 'Re Entry Interval Hours'), 'number'],
                    ['toxicity_class', $t('درجة السمية', 'Toxicity Class')],
                    ['max_applications', $t('أقصى عدد للتطبيقات', 'Max Applications')],
                    ['application_interval_days', $t('الفاصل بين التطبيقات بالأيام', 'Application Interval Days')],
                ] as $fieldConfig)
                    @php
                        [$field, $label] = $fieldConfig;
                        $type = $fieldConfig[2] ?? 'text';
                    @endphp
                    <div>
                        <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                        <input id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" type="{{ $type }}" step="{{ $type === 'number' ? '1' : '' }}" class="form-input">
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ([
                    ['active_ingredients', $t('المواد الفعالة', 'Active Ingredients'), $t('إضافة مادة فعالة', 'Add active ingredient'), $t('أدخل المادة الفعالة', 'Enter active ingredient')],
                    ['target_crops', $t('المحاصيل المستهدفة', 'Target Crops'), $t('إضافة محصول', 'Add crop'), $t('أدخل المحصول المستهدف', 'Enter target crop')],
                    ['target_pests', $t('الآفات المستهدفة', 'Target Pests'), $t('إضافة آفة', 'Add pest'), $t('أدخل الآفة المستهدفة', 'Enter target pest')],
                    ['pre_harvest_intervals', $t('فترات ما قبل الحصاد', 'Pre Harvest Intervals'), $t('إضافة فترة', 'Add interval'), $t('أدخل فترة ما قبل الحصاد', 'Enter pre-harvest interval')],
                    ['ppe_requirements', $t('معدات الوقاية المطلوبة', 'PPE Requirements'), $t('إضافة متطلب', 'Add requirement'), $t('أدخل متطلب الوقاية', 'Enter PPE requirement')],
                    ['first_aid', $t('الإسعافات الأولية', 'First Aid'), $t('إضافة إسعاف', 'Add first aid step'), $t('أدخل إجراء إسعاف', 'Enter first aid step')],
                    ['compatibility', $t('التوافق', 'Compatibility'), $t('إضافة عنصر', 'Add item'), $t('أدخل تفاصيل التوافق', 'Enter compatibility detail')],
                    ['environmental_hazards', $t('المخاطر البيئية', 'Environmental Hazards'), $t('إضافة خطر', 'Add hazard'), $t('أدخل خطرًا بيئيًا', 'Enter environmental hazard')],
                ] as [$field, $label, $button, $placeholder])
                    <div class="rounded-2xl border border-white bg-white p-4">
                        <label class="form-label">{{ $label }}</label>
                        <div class="space-y-3" data-array-list="agricultural_detail[{{ $field }}]" data-array-placeholder="{{ $placeholder }}">
                            <div class="space-y-2" data-array-items></div>
                            <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $button }}</button>
                        </div>
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-4">
                @foreach ([
                    ['mode_of_action', $t('آلية التأثير', 'Mode Of Action')],
                    ['resistance_management', $t('إدارة المقاومة', 'Resistance Management')],
                    ['container_disposal', $t('التخلص من العبوة', 'Container Disposal')],
                ] as [$field, $label])
                    <div>
                        <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                        <textarea id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" rows="3" class="form-textarea"></textarea>
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-sky-200 bg-sky-50/60 p-4" data-agri-subtype="fertilizer soil_amendment growth_regulator">
            <div>
                <h3 class="text-base font-bold text-gray-900">{{ $t('حقول الأسمدة ومحسنات التربة', 'Fertilizer and Soil Fields') }}</h3>
                <p class="text-sm text-gray-500">{{ $t('تُستخدم للأسمدة ومحسنات التربة ومنظمات النمو عند الحاجة.', 'Used for fertilizers, soil amendments, and growth regulators when needed.') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ([
                    ['fertilizer_type', $t('نوع السماد', 'Fertilizer Type')],
                    ['organic_matter_percent', $t('نسبة المادة العضوية %', 'Organic Matter %'), 'number'],
                    ['ph_value', $t('قيمة PH', 'PH Value')],
                    ['solubility', $t('الذوبانية', 'Solubility')],
                    ['nutrient_n_percent', $t('نسبة النيتروجين %', 'Nitrogen %'), 'number'],
                    ['nutrient_p_percent', $t('نسبة الفوسفور %', 'Phosphorus %'), 'number'],
                    ['nutrient_k_percent', $t('نسبة البوتاسيوم %', 'Potassium %'), 'number'],
                ] as $fieldConfig)
                    @php
                        [$field, $label] = $fieldConfig;
                        $type = $fieldConfig[2] ?? 'text';
                    @endphp
                    <div>
                        <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                        <input id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" type="{{ $type }}" step="{{ $type === 'number' ? '0.01' : '' }}" class="form-input">
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ([
                    ['micronutrients', $t('العناصر الصغرى', 'Micronutrients'), $t('إضافة عنصر', 'Add nutrient'), $t('أدخل عنصرًا دقيقًا', 'Enter micronutrient')],
                    ['fertilization_methods', $t('طرق التسميد', 'Fertilization Methods'), $t('إضافة طريقة', 'Add method'), $t('أدخل طريقة تسميد', 'Enter fertilization method')],
                ] as [$field, $label, $button, $placeholder])
                    <div class="rounded-2xl border border-white bg-white p-4">
                        <label class="form-label">{{ $label }}</label>
                        <div class="space-y-3" data-array-list="agricultural_detail[{{ $field }}]" data-array-placeholder="{{ $placeholder }}">
                            <div class="space-y-2" data-array-items></div>
                            <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $button }}</button>
                        </div>
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-amber-200 bg-amber-50/60 p-4" data-agri-subtype="seed">
            <div>
                <h3 class="text-base font-bold text-gray-900">{{ $t('حقول البذور', 'Seed Fields') }}</h3>
                <p class="text-sm text-gray-500">{{ $t('تظهر عند اختيار نوع المنتج: بذور.', 'Visible when the agricultural product type is seeds.') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ([
                    ['crop_name_ar', $t('اسم المحصول بالعربي', 'Crop Name (AR)')],
                    ['crop_name_en', $t('اسم المحصول بالإنجليزي', 'Crop Name (EN)')],
                    ['variety_name', $t('اسم الصنف', 'Variety Name')],
                    ['variety_type', $t('نوع الصنف', 'Variety Type')],
                    ['germination_percent', $t('نسبة الإنبات %', 'Germination %'), 'number'],
                    ['purity_percent', $t('نسبة النقاوة %', 'Purity %'), 'number'],
                    ['maturity_days', $t('أيام النضج', 'Maturity Days')],
                ] as $fieldConfig)
                    @php
                        [$field, $label] = $fieldConfig;
                        $type = $fieldConfig[2] ?? 'text';
                    @endphp
                    <div>
                        <label for="agricultural_{{ $field }}" class="form-label">{{ $label }}</label>
                        <input id="agricultural_{{ $field }}" data-request-key="agricultural_detail[{{ $field }}]" type="{{ $type }}" step="{{ $type === 'number' ? '0.01' : '' }}" class="form-input">
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ([
                    ['seed_treatment', $t('معالجة البذور', 'Seed Treatment'), $t('إضافة معالجة', 'Add treatment'), $t('أدخل معالجة البذور', 'Enter seed treatment')],
                    ['disease_resistance', $t('مقاومة الأمراض', 'Disease Resistance'), $t('إضافة مقاومة', 'Add resistance'), $t('أدخل مقاومة المرض', 'Enter disease resistance')],
                    ['planting_windows', $t('مواعيد الزراعة', 'Planting Windows'), $t('إضافة موعد', 'Add window'), $t('أدخل موعد الزراعة', 'Enter planting window')],
                    ['seeding_rate', $t('معدل البذار', 'Seeding Rate'), $t('إضافة معدل', 'Add rate'), $t('أدخل معدل البذار', 'Enter seeding rate')],
                    ['planting_depth', $t('عمق الزراعة', 'Planting Depth'), $t('إضافة عمق', 'Add depth'), $t('أدخل عمق الزراعة', 'Enter planting depth')],
                    ['plant_spacing', $t('تباعد النباتات', 'Plant Spacing'), $t('إضافة تباعد', 'Add spacing'), $t('أدخل تباعد النباتات', 'Enter plant spacing')],
                    ['expected_yield', $t('الإنتاج المتوقع', 'Expected Yield'), $t('إضافة قيمة', 'Add value'), $t('أدخل الإنتاج المتوقع', 'Enter expected yield')],
                ] as [$field, $label, $button, $placeholder])
                    <div class="rounded-2xl border border-white bg-white p-4">
                        <label class="form-label">{{ $label }}</label>
                        <div class="space-y-3" data-array-list="agricultural_detail[{{ $field }}]" data-array-placeholder="{{ $placeholder }}">
                            <div class="space-y-2" data-array-items></div>
                            <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $button }}</button>
                        </div>
                        <p class="form-error" data-error-key="agricultural_detail.{{ $field }}"></p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card" data-detail-section="veterinary">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('تفاصيل المنتجات البيطرية', 'Veterinary Parameters') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $t('حقول منظمة للأدوية واللقاحات والمستلزمات البيطرية.', 'Organized fields for medicines, vaccines, and veterinary supplies.') }}</p>
    </div>
    <div class="card-body space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ([
                ['concentration', $t('التركيز', 'Concentration')],
                ['dosage_form', $t('الشكل الدوائي', 'Dosage Form')],
                ['treatment_duration', $t('مدة العلاج', 'Treatment Duration')],
                ['withdrawal_meat_days', $t('فترة سحب اللحم', 'Withdrawal Meat Days'), 'number'],
                ['withdrawal_milk_days', $t('فترة سحب الحليب', 'Withdrawal Milk Days'), 'number'],
                ['withdrawal_eggs_days', $t('فترة سحب البيض', 'Withdrawal Eggs Days'), 'number'],
                ['shelf_life_after_opening', $t('مدة الصلاحية بعد الفتح', 'Shelf Life After Opening')],
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
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ([
                ['active_ingredients', $t('المواد الفعالة', 'Active Ingredients'), $t('إضافة مادة فعالة', 'Add active ingredient'), $t('أدخل المادة الفعالة', 'Enter active ingredient')],
                ['routes_of_administration', $t('طرق الإعطاء', 'Routes Of Administration'), $t('إضافة طريقة إعطاء', 'Add administration route'), $t('أدخل طريقة الإعطاء', 'Enter administration route')],
                ['target_species', $t('الأنواع المستهدفة', 'Target Species'), $t('إضافة نوع مستهدف', 'Add target species'), $t('أدخل النوع المستهدف', 'Enter target species')],
                ['indications', $t('دواعي الاستعمال', 'Indications'), $t('إضافة داعي استعمال', 'Add indication'), $t('أدخل داعي الاستعمال', 'Enter indication')],
                ['dosage_instructions', $t('تعليمات الجرعة', 'Dosage Instructions'), $t('إضافة تعليمات جرعة', 'Add dosage instruction'), $t('أدخل تعليمات الجرعة', 'Enter dosage instruction')],
                ['contraindications', $t('موانع الاستعمال', 'Contraindications'), $t('إضافة مانع استعمال', 'Add contraindication'), $t('أدخل مانع الاستعمال', 'Enter contraindication')],
                ['warnings', $t('التحذيرات', 'Warnings'), $t('إضافة تحذير', 'Add warning'), $t('أدخل التحذير', 'Enter warning')],
                ['adverse_reactions', $t('الآثار الجانبية', 'Adverse Reactions'), $t('إضافة أثر جانبي', 'Add adverse reaction'), $t('أدخل الأثر الجانبي', 'Enter adverse reaction')],
                ['drug_interactions', $t('التداخلات الدوائية', 'Drug Interactions'), $t('إضافة تداخل دوائي', 'Add drug interaction'), $t('أدخل التداخل الدوائي', 'Enter drug interaction')],
                ['storage_conditions', $t('ظروف التخزين', 'Storage Conditions'), $t('إضافة شرط تخزين', 'Add storage condition'), $t('أدخل شرط التخزين', 'Enter storage condition')],
            ] as [$field, $label, $button, $placeholder])
                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4">
                    <label class="form-label">{{ $label }}</label>
                    <div class="space-y-3" data-array-list="veterinary_detail[{{ $field }}]" data-array-placeholder="{{ $placeholder }}">
                        <div class="space-y-2" data-array-items></div>
                        <button type="button" class="btn-secondary btn-xs" data-array-add>{{ $button }}</button>
                    </div>
                    <p class="form-error" data-error-key="veterinary_detail.{{ $field }}"></p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4">
            @foreach ([
                ['pregnancy_lactation_use', $t('الاستخدام أثناء الحمل والإرضاع', 'Pregnancy Lactation Use')],
                ['special_precautions', $t('احتياطات خاصة', 'Special Precautions')],
                ['overdose_information', $t('معلومات الجرعة الزائدة', 'Overdose Information')],
                ['disposal_instructions', $t('تعليمات التخلص', 'Disposal Instructions')],
                ['medical_disclaimer', $t('إخلاء المسؤولية الطبية', 'Medical Disclaimer')],
            ] as [$field, $label])
                <div>
                    <label for="veterinary_{{ $field }}" class="form-label">{{ $label }}</label>
                    <textarea id="veterinary_{{ $field }}" data-request-key="veterinary_detail[{{ $field }}]" rows="3" class="form-textarea"></textarea>
                    <p class="form-error" data-error-key="veterinary_detail.{{ $field }}"></p>
                </div>
            @endforeach
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('agricultural_agricultural_product_type');

            if (!typeSelect) {
                return;
            }

            const syncSubtypeSections = () => {
                const selectedValue = typeSelect.value || '';

                document.querySelectorAll('[data-agri-subtype]').forEach((section) => {
                    const allowedTypes = (section.dataset.agriSubtype || '').split(' ').filter(Boolean);
                    const visible = selectedValue !== '' && allowedTypes.includes(selectedValue);

                    section.classList.toggle('hidden', !visible);
                    section.querySelectorAll('input, textarea, select').forEach((field) => {
                        field.disabled = !visible;
                    });
                });
            };

            typeSelect.addEventListener('change', syncSubtypeSections);
            syncSubtypeSections();
        });
        </script>
    @endpush
@endonce
