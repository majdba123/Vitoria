@props(['showVendorSelect' => false, 'showLegacyMediaFields' => false, 'vendors' => []])

@php
    $isArabic = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en): string => $isArabic ? $ar : $en;
@endphp

@if ($showVendorSelect)
<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('ربط المنتج بالبائع', 'Assign to Vendor') }}</h2>
        <p class="mt-0.5 text-sm text-gray-500">{{ $t('اختر البائع الذي يملك هذا المنتج.', 'Select the vendor who owns this product.') }}</p>
    </div>
    <div class="card-body">
        <div>
            <label for="vendor_id" class="form-label">{{ $t('البائع', 'Vendor') }} <span class="text-red-500">*</span></label>
            <select id="vendor_id" name="vendor_id" class="form-select">
                <option value="">{{ $t('جاري تحميل البائعين...', 'Loading vendors...') }}</option>
            </select>
            <p class="form-error" id="vendor_id-error"></p>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('بيانات المنتج', 'Product Details') }}</h2>
        <p class="mt-0.5 text-sm text-gray-500">
            {{ $showVendorSelect ? $t('أدخل معلومات المنتج الأساسية ثم أكمل تفاصيله حسب النوع.', 'Enter the core product data, then complete the type-specific details.') : $t('حدّث بيانات المنتج الأساسية ثم أكمل تفاصيله حسب النوع.', 'Update the core product data, then complete the type-specific details.') }}
        </p>
    </div>

    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="category_id" class="form-label">{{ $t('الفئة', 'Category') }} <span class="text-red-500">*</span></label>
                <select id="category_id" name="category_id" class="form-select">
                    <option value="">{{ $t('اختر الفئة...', 'Select category...') }}</option>
                </select>
                <p class="form-error" id="category_id-error"></p>
            </div>
            <div id="subcategory-field-wrap" class="hidden">
                <label for="subcategory_id" class="form-label">{{ $t('الفئة الفرعية', 'Subcategory') }}</label>
                <select id="subcategory_id" name="subcategory_id" class="form-select">
                    <option value="">{{ $t('اختر الفئة الفرعية...', 'Select subcategory...') }}</option>
                </select>
                <p class="form-error" id="subcategory_id-error"></p>
            </div>
            <div id="category-type-field-wrap" class="hidden">
                <label for="category_type_display" class="form-label">{{ $t('نوع المنتج', 'Product Type') }}</label>
                <input id="category_type_display" type="text" class="form-input bg-gray-50" readonly>
                <p class="text-xs text-gray-500">{{ $t('يتم تحديده تلقائيًا حسب الفئة المختارة.', 'This is filled automatically from the selected category.') }}</p>
            </div>
            <div id="product-type-proxy-wrap" class="hidden sm:col-span-2">
                <label for="product_type_proxy" class="form-label">{{ $t('قائمة نوع المنتج', 'Product Type List') }}</label>
                <select id="product_type_proxy" class="form-select">
                    <option value="">{{ $t('اختر نوع المنتج من القائمة', 'Select product type from the list') }}</option>
                </select>
                <p class="text-xs text-gray-500">{{ $t('تظهر هذه القائمة للمنتجات الزراعية فقط، حسب متطلبات الوثيقة.', 'This list appears only for agricultural products, based on the document requirements.') }}</p>
            </div>
            <div>
                <x-form.input name="name_ar" :label="$t('الاسم العربي', 'Arabic Name')" :placeholder="$t('أدخل اسم المنتج بالعربية', 'Enter Arabic product name')" :required="true" />
            </div>
            <div>
                <x-form.input name="name_en" :label="$t('الاسم الإنجليزي', 'English Name')" :placeholder="$t('أدخل اسم المنتج بالإنجليزية', 'Enter English product name')" :required="true" />
            </div>
            <x-form.input name="price" :label="$t('السعر ($)', 'Price ($)')" type="number" placeholder="0.00" :required="true" />
            <div>
                <label for="discount_percentage" class="form-label">{{ $t('الخصم (%)', 'Discount (%)') }}</label>
                <input id="discount_percentage" name="discount_percentage" type="number" step="0.01" min="0" max="100" class="form-input" placeholder="{{ $t('اختياري', 'Optional') }}">
                <p class="form-error" id="discount_percentage-error"></p>
            </div>
            <x-form.input name="quantity" :label="$t('الكمية', 'Quantity')" type="number" placeholder="0" :required="true" />
            <div>
                <label for="discount_starts_at" class="form-label">{{ $t('بداية الخصم', 'Discount Start') }}</label>
                <input id="discount_starts_at" name="discount_starts_at" type="date" class="form-input">
                <p class="form-error" id="discount_starts_at-error"></p>
            </div>
            <div>
                <label for="discount_ends_at" class="form-label">{{ $t('نهاية الخصم', 'Discount End') }}</label>
                <input id="discount_ends_at" name="discount_ends_at" type="date" class="form-input">
                <p class="form-error" id="discount_ends_at-error"></p>
            </div>
        </div>

        <div>
            <label for="description" class="form-label">{{ $t('الوصف', 'Description') }}</label>
            <textarea id="description" name="description" rows="4" placeholder="{{ $t('اكتب وصف المنتج بشكل واضح ومفيد (اختياري)', 'Write a clear helpful product description (optional)') }}" class="form-textarea"></textarea>
            <p class="form-error" id="description-error"></p>
        </div>

        <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('حالة التفعيل', 'Active Status') }}</p>
                <p class="text-xs text-gray-500">{{ $t('سيظهر المنتج للعملاء عند تفعيله.', 'The product becomes visible to customers when active.') }}</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
</div>

<x-products.detail-fields />
