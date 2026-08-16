import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField, TextareaField } from '@/Components/admin/form/FormField';
import { ProductDetailFields } from '@/Components/products/ProductDetailFields';
import { PhotoUpload } from '@/Components/products/PhotoUpload';
import { Card, CardContent } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';
import { emptyDetailValues, SHARED_FIELDS, SHARED_REPEATERS, SHARED_TEXTAREAS, AGRI_COMMON_REPEATERS, PESTICIDE_FIELDS, PESTICIDE_REPEATERS, PESTICIDE_TEXTAREAS, FERTILIZER_FIELDS, FERTILIZER_REPEATERS, SEED_FIELDS, SEED_REPEATERS, VETERINARY_FIELDS, VETERINARY_REPEATERS, VETERINARY_TEXTAREAS } from '@/lib/product-detail-schema';
import { buildProductFormData } from '@/lib/build-product-form-data';

function emptySharedDetail() {
    return { ...emptyDetailValues(SHARED_FIELDS, SHARED_REPEATERS, SHARED_TEXTAREAS), registration_status: '' };
}

function emptyAgriculturalDetail() {
    return {
        formulation: '',
        ...emptyDetailValues([], AGRI_COMMON_REPEATERS, []),
        ...emptyDetailValues(PESTICIDE_FIELDS, PESTICIDE_REPEATERS, PESTICIDE_TEXTAREAS),
        ...emptyDetailValues(FERTILIZER_FIELDS, FERTILIZER_REPEATERS, []),
        ...emptyDetailValues(SEED_FIELDS, SEED_REPEATERS, []),
    };
}

function emptyVeterinaryDetail() {
    return emptyDetailValues(VETERINARY_FIELDS, VETERINARY_REPEATERS, VETERINARY_TEXTAREAS);
}

export default function ProductsCreate() {
    const { admin, common, products } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();

    const [vendors, setVendors] = useState([]);
    const [vendorId, setVendorId] = useState('');
    const [categories, setCategories] = useState([]);
    const [categoryId, setCategoryId] = useState('');
    const [subcategoryId, setSubcategoryId] = useState('');
    const [agriculturalProductType, setAgriculturalProductType] = useState('');

    const [core, setCore] = useState({ name_ar: '', name_en: '', price: '', discount_percentage: '', quantity: '', minimum_order_quantity: '1', discount_starts_at: '', discount_ends_at: '', description: '' });
    const [isActive, setIsActive] = useState(true);
    const [sharedDetail, setSharedDetail] = useState(emptySharedDetail());
    const [agriculturalDetail, setAgriculturalDetail] = useState(emptyAgriculturalDetail());
    const [veterinaryDetail, setVeterinaryDetail] = useState(emptyVeterinaryDetail());
    const [photos, setPhotos] = useState([]);

    useEffect(() => {
        window.axios.get('/api/admin/vendors', { params: { per_page: 100 }, silent: true }).then((res) => {
            setVendors((res.data?.data ?? []).filter((v) => v.is_active));
        });
    }, []);

    useEffect(() => {
        setCategoryId('');
        setSubcategoryId('');
        if (!vendorId) {
            setCategories([]);
            return;
        }
        window.axios.get(`/api/admin/vendors/${vendorId}`, { silent: true }).then((res) => setCategories(res.data?.data?.categories ?? []));
    }, [vendorId]);

    const selectedCategory = categories.find((c) => String(c.id) === String(categoryId));
    const categoryType = selectedCategory?.type ?? '';
    const subcategories = selectedCategory?.subcategories ?? [];

    const setField = (key) => (value) => setCore((c) => ({ ...c, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        const formData = buildProductFormData({
            core: {
                vendor_id: vendorId,
                category_id: categoryId,
                subcategory_id: subcategoryId,
                name_ar: core.name_ar.trim(),
                name_en: core.name_en.trim(),
                price: parseFloat(core.price) || 0,
                discount_percentage: core.discount_percentage !== '' ? parseFloat(core.discount_percentage) || 0 : undefined,
                quantity: parseInt(core.quantity, 10) || 0,
                minimum_order_quantity: parseInt(core.minimum_order_quantity, 10) || 1,
                is_active: isActive ? '1' : '0',
                description: core.description.trim() || undefined,
                discount_starts_at: core.discount_starts_at || undefined,
                discount_ends_at: core.discount_ends_at || undefined,
            },
            categoryType,
            sharedDetail,
            agriculturalDetail,
            veterinaryDetail,
            photos,
        });

        try {
            await submit('post', '/api/admin/products', formData, { isMultipart: 'raw' });
            router.visit(route('admin.products.index'));
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.add_product}>
            <PageHeader breadcrumb={[{ label: admin.manage_products_title, href: route('admin.products.index') }, { label: admin.add_product }]} title={admin.add_product} />

            {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

            <form onSubmit={handleSubmit} className="space-y-4">
                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-4 p-5 sm:p-6">
                        <h2 className="text-base font-bold text-foreground">Assign to vendor</h2>
                        <SelectField id="vendor_id" label="Vendor" required value={vendorId} onValueChange={setVendorId} placeholder="Select a vendor..." options={vendors.map((v) => ({ value: v.id, label: `${v.store_name} - ${v.user?.name ?? 'N/A'}` }))} error={errors.vendor_id} />
                    </CardContent>
                </Card>

                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-5 p-5 sm:p-6">
                        <h2 className="text-base font-bold text-foreground">Product details</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <SelectField id="category_id" label="Category" required value={categoryId} onValueChange={setCategoryId} placeholder={vendorId ? 'Select category...' : 'Select a vendor first'} options={categories.map((c) => ({ value: c.id, label: c.name }))} error={errors.category_id} />
                            {subcategories.length > 0 && (
                                <SelectField id="subcategory_id" label="Subcategory" value={subcategoryId} onValueChange={setSubcategoryId} placeholder="Select subcategory..." options={subcategories.map((s) => ({ value: s.id, label: s.name_ar || s.name_en }))} error={errors.subcategory_id} />
                            )}
                            <TextField id="name_ar" label="Arabic name" required dir="rtl" value={core.name_ar} onChange={(e) => setField('name_ar')(e.target.value)} error={errors.name_ar} />
                            <TextField id="name_en" label="English name" required value={core.name_en} onChange={(e) => setField('name_en')(e.target.value)} error={errors.name_en} />
                            <TextField id="price" label="Price (SYP)" type="number" step="0.01" required value={core.price} onChange={(e) => setField('price')(e.target.value)} error={errors.price} />
                            <TextField id="discount_percentage" label="Discount (%)" type="number" step="0.01" min="0" max="100" placeholder="Optional" value={core.discount_percentage} onChange={(e) => setField('discount_percentage')(e.target.value)} error={errors.discount_percentage} />
                            <TextField id="quantity" label="Quantity" type="number" required value={core.quantity} onChange={(e) => setField('quantity')(e.target.value)} error={errors.quantity} />
                            <TextField id="minimum_order_quantity" label="Minimum order quantity" type="number" min="1" value={core.minimum_order_quantity} onChange={(e) => setField('minimum_order_quantity')(e.target.value)} error={errors.minimum_order_quantity} />
                            <TextField id="discount_starts_at" label={products.fields?.discount_starts ?? 'Discount start'} type="date" value={core.discount_starts_at} onChange={(e) => setField('discount_starts_at')(e.target.value)} error={errors.discount_starts_at} />
                            <TextField id="discount_ends_at" label={products.fields?.discount_ends ?? 'Discount end'} type="date" value={core.discount_ends_at} onChange={(e) => setField('discount_ends_at')(e.target.value)} error={errors.discount_ends_at} />
                        </div>
                        <TextareaField id="description" label="Description" rows={4} placeholder="Optional" value={core.description} onChange={(e) => setField('description')(e.target.value)} error={errors.description} />
                        <div className="flex items-center justify-between rounded-md bg-muted px-4 py-3">
                            <div>
                                <p className="text-sm font-medium text-foreground">Active status</p>
                                <p className="text-xs text-muted-foreground">The product becomes visible to customers when active.</p>
                            </div>
                            <Switch checked={isActive} onCheckedChange={setIsActive} aria-label={common.active ?? 'Active status'} />
                        </div>
                    </CardContent>
                </Card>

                {categoryType === 'agriculture' && (
                    <Card className="border-border/80 shadow-none">
                        <CardContent className="p-5 sm:p-6">
                            <SelectField
                                id="product_type_proxy"
                                label="Agricultural product type"
                                value={agriculturalProductType}
                                onValueChange={setAgriculturalProductType}
                                placeholder="Select product type..."
                                options={[
                                    { value: 'pesticide', label: 'Pesticide' },
                                    { value: 'fertilizer', label: 'Fertilizer' },
                                    { value: 'seed', label: 'Seed' },
                                    { value: 'soil_amendment', label: 'Soil amendment' },
                                    { value: 'growth_regulator', label: 'Growth regulator' },
                                    { value: 'other', label: 'Other' },
                                ]}
                            />
                        </CardContent>
                    </Card>
                )}

                <ProductDetailFields
                    categoryType={categoryType}
                    agriculturalProductType={agriculturalProductType}
                    onAgriculturalProductTypeChange={setAgriculturalProductType}
                    sharedDetail={sharedDetail}
                    onSharedChange={(key, value) => setSharedDetail((s) => ({ ...s, [key]: value }))}
                    agriculturalDetail={agriculturalDetail}
                    onAgriculturalChange={(key, value) => setAgriculturalDetail((a) => ({ ...a, [key]: value }))}
                    veterinaryDetail={veterinaryDetail}
                    onVeterinaryChange={(key, value) => setVeterinaryDetail((v) => ({ ...v, [key]: value }))}
                    errors={errors}
                />

                <PhotoUpload photos={photos} onChange={setPhotos} error={errors.photos} />

                <div className="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" onClick={() => router.visit(route('admin.products.index'))}>
                        {common.cancel ?? 'Cancel'}
                    </Button>
                    <Button type="submit" disabled={isSubmitting}>
                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                        {admin.add_product}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
