@props(['showVendorSelect' => false, 'vendors' => []])

{{-- Vendor Selection (Admin Only) --}}
@if($showVendorSelect)
<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Assign to Vendor</h2>
        <p class="mt-0.5 text-sm text-gray-500">Select the vendor who owns this product.</p>
    </div>
    <div class="card-body">
        <div>
            <label for="vendor_id" class="form-label">Vendor <span class="text-red-500">*</span></label>
            <select id="vendor_id" name="vendor_id" class="form-input">
                <option value="">Loading vendors...</option>
            </select>
            <p class="form-error" id="vendor_id-error"></p>
        </div>
    </div>
</div>
@endif

{{-- Product Details Card --}}
<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Product Details</h2>
        <p class="mt-0.5 text-sm text-gray-500">{{ $showVendorSelect ? 'Enter product information below.' : 'Add your product information below.' }}</p>
    </div>

    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="category_id" class="form-label">Category <span class="text-red-500">*</span></label>
                <select id="category_id" name="category_id" class="form-input">
                    <option value="">Select category...</option>
                </select>
                <p class="form-error" id="category_id-error"></p>
            </div>
            <div>
                <label for="subcategory_id" class="form-label">Subcategory <span class="text-red-500">*</span></label>
                <select id="subcategory_id" name="subcategory_id" class="form-input" disabled>
                    <option value="">Select category first...</option>
                </select>
                <p class="form-error" id="subcategory_id-error"></p>
            </div>
            <div class="sm:col-span-2">
                <x-form.input name="name" label="Product Name" placeholder="Enter product name" :required="true" />
            </div>
            <x-form.input name="price" label="Price ($)" type="number" placeholder="0.00" :required="true" />
            <div>
                <label for="discount_percentage" class="form-label">Discount (%)</label>
                <input id="discount_percentage" name="discount_percentage" type="number" step="0.01" min="0" max="100" class="form-input" placeholder="Optional">
                <p class="form-error" id="discount_percentage-error"></p>
            </div>
            <x-form.input name="quantity" label="Quantity" type="number" placeholder="0" :required="true" />
            <div>
                <label for="discount_starts_at" class="form-label">Discount Start</label>
                <input id="discount_starts_at" name="discount_starts_at" type="date" class="form-input">
                <p class="form-error" id="discount_starts_at-error"></p>
            </div>
            <div>
                <label for="discount_ends_at" class="form-label">Discount End</label>
                <input id="discount_ends_at" name="discount_ends_at" type="date" class="form-input">
                <p class="form-error" id="discount_ends_at-error"></p>
            </div>
        </div>

        <div>
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Describe the product in detail (optional)" class="form-textarea"></textarea>
            <p class="form-error" id="description-error"></p>
        </div>

        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">Active Status</p>
                <p class="text-xs text-gray-500">Product will be visible to customers when active.</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>

    </div>
</div>

