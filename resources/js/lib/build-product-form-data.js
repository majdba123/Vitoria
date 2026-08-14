/**
 * Assembles the multipart FormData a product create/edit submit sends:
 * top-level scalars, the three nested detail buckets (only the bucket that
 * matches the selected category type is included, mirroring the original
 * form disabling out-of-scope sections before submit), and the photo
 * gallery. Mirrors appendDetailFields()/the submit handler in
 * admin/products/create.blade.php.
 */
export function buildProductFormData({ core, categoryType, sharedDetail, agriculturalDetail, veterinaryDetail, photos, method }) {
    const formData = new FormData();

    if (method) {
        formData.append('_method', method);
    }

    Object.entries(core).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        formData.append(key, value);
    });

    appendDetailBucket(formData, 'shared_detail', sharedDetail);

    if (categoryType === 'agriculture') {
        appendDetailBucket(formData, 'agricultural_detail', agriculturalDetail);
    }

    if (categoryType === 'veterinary') {
        appendDetailBucket(formData, 'veterinary_detail', veterinaryDetail);
    }

    photos.forEach((photo) => {
        formData.append('photos[]', photo.file);
        formData.append('photo_types[]', photo.image_type);
        formData.append('photo_sort_orders[]', photo.sort_order);
    });

    return formData;
}

function appendDetailBucket(formData, bucket, values) {
    if (!values) return;

    Object.entries(values).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.filter((item) => item && item.trim() !== '').forEach((item) => {
                formData.append(`${bucket}[${key}][]`, item);
            });
            return;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            formData.append(`${bucket}[${key}]`, value.trim());
        }
    });
}
