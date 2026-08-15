import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField, TextareaField } from '@/Components/admin/form/FormField';
import { ProductDetailFields } from '@/Components/products/ProductDetailFields';
import { PhotoUpload } from '@/Components/products/PhotoUpload';
import { ExistingPhotoGrid } from '@/Components/products/ExistingPhotoGrid';
import { Card, CardContent } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';
import { emptyDetailValues, mergeKnownDetailValues, SHARED_FIELDS, SHARED_REPEATERS, SHARED_TEXTAREAS, AGRI_COMMON_REPEATERS, PESTICIDE_FIELDS, PESTICIDE_REPEATERS, PESTICIDE_TEXTAREAS, FERTILIZER_FIELDS, FERTILIZER_REPEATERS, SEED_FIELDS, SEED_REPEATERS, VETERINARY_FIELDS, VETERINARY_REPEATERS, VETERINARY_TEXTAREAS } from '@/lib/product-detail-schema';
import { buildProductFormData } from '@/lib/build-product-form-data';

function baseSharedDetail() {
    return { ...emptyDetailValues(SHARED_FIELDS, SHARED_REPEATERS, SHARED_TEXTAREAS), registration_status: '' };
}
function baseAgriculturalDetail() {
    return {
        formulation: '',
        ...emptyDetailValues([], AGRI_COMMON_REPEATERS, []),
        ...emptyDetailValues(PESTICIDE_FIELDS, PESTICIDE_REPEATERS, PESTICIDE_TEXTAREAS),
        ...emptyDetailValues(FERTILIZER_FIELDS, FERTILIZER_REPEATERS, []),
        ...emptyDetailValues(SEED_FIELDS, SEED_REPEATERS, []),
    };
}
function baseVeterinaryDetail() {
    return emptyDetailValues(VETERINARY_FIELDS, VETERINARY_REPEATERS, VETERINARY_TEXTAREAS);
}

export default function ProductsEdit({ productId }) {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [vendorLabel, setVendorLabel] = useState('-');

    const [categories, setCategories] = useState([]);
    const [categoryId, setCategoryId] = useState('');
    const [subcategoryId, setSubcategoryId] = useState('');
    const [agriculturalProductType, setAgriculturalProductType] = useState('');

    const [core, setCore] = useState({ name_ar: '', name_en: '', price: '', discount_percentage: '', quantity: '', minimum_order_quantity: '1', discount_starts_at: '', discount_ends_at: '', description: '' });
    const [isActive, setIsActive] = useState(true);
    const [sharedDetail, setSharedDetail] = useState(baseSharedDetail());
    const [agriculturalDetail, setAgriculturalDetail] = useState(baseAgriculturalDetail());
    const [veterinaryDetail, setVeterinaryDetail] = useState(baseVeterinaryDetail());
    const [successMessage, setSuccessMessage] = useState(null);

    // Existing-photo management (separate save action, matching the Blade original)
    const [photos, setPhotos] = useState([]);
    const [removedPhotoIds, setRemovedPhotoIds] = useState([]);
    const [primaryPhotoId, setPrimaryPhotoId] = useState(null);
    const [photoEdits, setPhotoEdits] = useState({});
    const [newPhotos, setNewPhotos] = useState([]);
    const [isSavingPhotos, setIsSavingPhotos] = useState(false);
    const [photoMessage, setPhotoMessage] = useState(null);

    const loadProduct = () => {
        return Promise.all([
            window.axios.get('/api/admin/categories', { silent: true }),
            window.axios.get(`/api/admin/products/${productId}`, { silent: true }),
        ]).then(([categoriesRes, productRes]) => {
            setCategories(categoriesRes.data?.data ?? []);
            const p = productRes.data.data;
            setCore({
                name_ar: p.name_ar ?? '',
                name_en: p.name_en ?? '',
                price: String(p.price ?? ''),
                discount_percentage: p.discount_percentage != null ? String(p.discount_percentage) : '',
                quantity: String(p.quantity ?? 0),
                minimum_order_quantity: String(p.minimum_order_quantity ?? 1),
                discount_starts_at: toDateInput(p.discount_starts_at),
                discount_ends_at: toDateInput(p.discount_ends_at),
                description: p.description ?? '',
            });
            setCategoryId(p.category_id ? String(p.category_id) : '');
            setSubcategoryId(p.subcategory_id ? String(p.subcategory_id) : '');
            setIsActive(!!p.is_active);
            setSharedDetail(mergeKnownDetailValues(baseSharedDetail(), p.shared_detail));
            setAgriculturalDetail(mergeKnownDetailValues(baseAgriculturalDetail(), p.agricultural_detail));
            setVeterinaryDetail(mergeKnownDetailValues(baseVeterinaryDetail(), p.veterinary_detail));
            setAgriculturalProductType(p.agricultural_detail?.agricultural_product_type ?? '');
            setPhotos(p.photos ?? []);
            setVendorLabel(p.vendor ? `${p.vendor.store_name}${p.vendor.user?.name ? ' - ' + p.vendor.user.name : ''}` : '-');
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(() => { loadProduct(); }, [productId]);

    const selectedCategory = categories.find((c) => String(c.id) === String(categoryId));
    const categoryType = selectedCategory?.type ?? '';
    const subcategories = selectedCategory?.subcategories ?? [];

    const setField = (key) => (value) => setCore((c) => ({ ...c, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const formData = buildProductFormData({
            core: {
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
            photos: [],
            method: 'PUT',
        });

        try {
            await submit('post', `/api/admin/products/${productId}`, formData, { isMultipart: 'raw' });
            setSuccessMessage('Product updated successfully.');
            // Re-fetch rather than trusting local form state — the server may
            // normalize values (discount_status, name fallback, ...), and this
            // page also has a separate photo-save action below it, so unlike
            // Categories/Edit.jsx a full navigate-away would break that flow.
            await loadProduct();
        } catch {
            // handled by hook
        }
    };

    const savePhotos = () => {
        setIsSavingPhotos(true);
        setPhotoMessage(null);
        const formData = new FormData();
        removedPhotoIds.forEach((id) => formData.append('photo_ids_to_remove[]', id));
        photos.forEach((photo) => {
            const edit = photoEdits[photo.id] ?? { image_type: photo.image_type || 'front', sort_order: photo.sort_order || 1 };
            formData.append('photo_ids[]', photo.id);
            formData.append('existing_photo_types[]', edit.image_type);
            formData.append('existing_photo_sort_orders[]', edit.sort_order);
        });
        newPhotos.forEach((photo) => {
            formData.append('photos[]', photo.file);
            formData.append('photo_types[]', photo.image_type);
            formData.append('photo_sort_orders[]', photo.sort_order);
        });
        if (primaryPhotoId) formData.append('primary_photo_id', primaryPhotoId);

        window.axios.post(`/api/admin/products/${productId}/photos/update`, formData, { headers: { 'Content-Type': 'multipart/form-data' }, silent: true })
            .then((res) => {
                setPhotos(res.data?.data?.photos ?? []);
                setRemovedPhotoIds([]);
                setPrimaryPhotoId(null);
                setPhotoEdits({});
                setNewPhotos([]);
                setPhotoMessage({ tone: 'success', text: 'Photo changes saved successfully!' });
                setIsSavingPhotos(false);
            })
            .catch((error) => {
                setPhotoMessage({ tone: 'danger', text: error.response?.data?.message ?? 'Failed to save photo changes.' });
                setIsSavingPhotos(false);
            });
    };

    const hasPhotoChanges = removedPhotoIds.length > 0 || primaryPhotoId !== null || newPhotos.length > 0 || Object.keys(photoEdits).length > 0;

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading ?? 'Loading...'}>
                <Skeleton className="h-96 w-full max-w-3xl" />
            </AdminLayout>
        );
    }

    if (status === 'error') {
        return (
            <AdminLayout title={admin.manage_products_title}>
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load product.</p>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title="Edit product">
            <PageHeader breadcrumb={[{ label: admin.manage_products_title, href: route('admin.products.index') }, { label: common.edit ?? 'Edit' }]} title="Edit product" />

            <div className="rounded-md border border-border bg-muted/40 px-4 py-3">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Vendor</p>
                <p className="mt-1 text-sm font-semibold text-foreground">{vendorLabel}</p>
            </div>

            {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
            {successMessage && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

            <form onSubmit={handleSubmit} className="space-y-4">
                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-5 p-5 sm:p-6">
                        <h2 className="text-base font-bold text-foreground">Product details</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <SelectField id="category_id" label="Category" required value={categoryId} onValueChange={setCategoryId} options={categories.map((c) => ({ value: c.id, label: c.name }))} error={errors.category_id} />
                            {subcategories.length > 0 && (
                                <SelectField id="subcategory_id" label="Subcategory" value={subcategoryId} onValueChange={setSubcategoryId} options={subcategories.map((s) => ({ value: s.id, label: s.name_ar || s.name_en }))} error={errors.subcategory_id} />
                            )}
                            <TextField id="name_ar" label="Arabic name" required dir="rtl" value={core.name_ar} onChange={(e) => setField('name_ar')(e.target.value)} error={errors.name_ar} />
                            <TextField id="name_en" label="English name" required value={core.name_en} onChange={(e) => setField('name_en')(e.target.value)} error={errors.name_en} />
                            <TextField id="price" label="Price (SYP)" type="number" step="0.01" required value={core.price} onChange={(e) => setField('price')(e.target.value)} error={errors.price} />
                            <TextField id="discount_percentage" label="Discount (%)" type="number" step="0.01" min="0" max="100" value={core.discount_percentage} onChange={(e) => setField('discount_percentage')(e.target.value)} error={errors.discount_percentage} />
                            <TextField id="quantity" label="Quantity" type="number" required value={core.quantity} onChange={(e) => setField('quantity')(e.target.value)} error={errors.quantity} />
                            <TextField id="minimum_order_quantity" label="Minimum order quantity" type="number" min="1" value={core.minimum_order_quantity} onChange={(e) => setField('minimum_order_quantity')(e.target.value)} error={errors.minimum_order_quantity} />
                            <TextField id="discount_starts_at" label="Discount start" type="date" value={core.discount_starts_at} onChange={(e) => setField('discount_starts_at')(e.target.value)} error={errors.discount_starts_at} />
                            <TextField id="discount_ends_at" label="Discount end" type="date" value={core.discount_ends_at} onChange={(e) => setField('discount_ends_at')(e.target.value)} error={errors.discount_ends_at} />
                        </div>
                        <TextareaField id="description" label="Description" rows={4} value={core.description} onChange={(e) => setField('description')(e.target.value)} error={errors.description} />
                        <div className="flex items-center justify-between rounded-md bg-muted px-4 py-3">
                            <div>
                                <p className="text-sm font-medium text-foreground">Active status</p>
                                <p className="text-xs text-muted-foreground">The product becomes visible to customers when active.</p>
                            </div>
                            <Switch checked={isActive} onCheckedChange={setIsActive} />
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

                <div className="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" onClick={() => router.visit(route('admin.products.index'))}>
                        {common.cancel ?? 'Cancel'}
                    </Button>
                    <Button type="submit" disabled={isSubmitting}>
                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                        {common.save_changes ?? 'Save changes'}
                    </Button>
                </div>
            </form>

            <Card className="border-border/80 shadow-none">
                <CardContent className="space-y-4 p-5 sm:p-6">
                    <div>
                        <h2 className="text-base font-bold text-foreground">Product photos</h2>
                        <p className="text-sm text-muted-foreground">Update image type, order, primary image, and add more photos from here.</p>
                    </div>

                    {photoMessage && (
                        <p className={`rounded-md border px-4 py-2.5 text-sm font-medium ${photoMessage.tone === 'success' ? 'border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]'}`}>
                            {photoMessage.text}
                        </p>
                    )}

                    <ExistingPhotoGrid
                        photos={photos}
                        removedIds={removedPhotoIds}
                        onToggleRemove={(id) => setRemovedPhotoIds((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]))}
                        primaryId={primaryPhotoId}
                        onTogglePrimary={(id) => setPrimaryPhotoId((current) => (current === id ? null : id))}
                        edits={photoEdits}
                        onEditChange={(id, edit) => setPhotoEdits((e) => ({ ...e, [id]: edit }))}
                    />

                    <div className="border-t border-border pt-4">
                        <p className="mb-2 text-sm font-medium text-foreground">Add more photos</p>
                        <PhotoUpload photos={newPhotos} onChange={setNewPhotos} />
                    </div>

                    <div className="flex justify-end border-t border-border pt-4">
                        <Button type="button" onClick={savePhotos} disabled={!hasPhotoChanges || isSavingPhotos}>
                            {isSavingPhotos && <Loader2 className="size-4 animate-spin" />}
                            Save photo changes
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}

function toDateInput(value) {
    if (!value) return '';
    if (typeof value === 'string') {
        const match = value.match(/^\d{4}-\d{2}-\d{2}/);
        if (match) return match[0];
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}
