@props(['showVendorSelect' => false, 'vendors' => []])

@php
    $isArabic = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en): string => $isArabic ? $ar : $en;
@endphp

{{-- Vendor Selection (Admin Only) --}}
@if($showVendorSelect)
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

{{-- Product Details Card --}}
<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">{{ $t('بيانات المنتج', 'Product Details') }}</h2>
        <p class="mt-0.5 text-sm text-gray-500">{{ $showVendorSelect ? $t('أدخل معلومات المنتج أدناه.', 'Enter product information below.') : $t('أضف معلومات منتجك أدناه.', 'Add your product information below.') }}</p>
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
            <textarea id="description" name="description" rows="4" placeholder="{{ $t('اكتب وصف المنتج بالتفصيل (اختياري)', 'Describe the product in detail (optional)') }}" class="form-textarea"></textarea>
            <p class="form-error" id="description-error"></p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="image" class="form-label">{{ $t('صورة المنتج', 'Product Image') }}</label>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
                <p class="mt-1 text-xs text-gray-500">{{ $t('الصورة الرئيسية للعرض. JPEG أو PNG أو GIF أو WebP. الحد الأقصى 5 ميغابايت.', 'Main display image. JPEG, PNG, GIF, or WebP. Max 5 MB.') }}</p>
                <p class="form-error" id="image-error"></p>
            </div>
            <div>
                <label for="icon" class="form-label">{{ $t('أيقونة المنتج', 'Product Icon') }}</label>
                <input id="icon" name="icon" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
                <p class="mt-1 text-xs text-gray-500">{{ $t('أيقونة صغيرة للمنتج. صورة أو SVG. الحد الأقصى 2 ميغابايت.', 'Small product icon. Image or SVG. Max 2 MB.') }}</p>
                <p class="form-error" id="icon-error"></p>
            </div>
        </div>

        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('حالة التفعيل', 'Active Status') }}</p>
                <p class="text-xs text-gray-500">{{ $t('سيظهر المنتج للعملاء عند تفعيله.', 'Product will be visible to customers when active.') }}</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>

    </div>
</div>

<x-products.detail-fields />
