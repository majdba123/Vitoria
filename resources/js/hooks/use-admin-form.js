import { useState } from 'react';

/**
 * Generic replacement for every admin create/edit form's submit handler:
 * clears field errors, submits (JSON or multipart), maps a 422 response to
 * per-field errors, and surfaces a general message otherwise. Mirrors the
 * exact error contract resources/js/bootstrap.js's ApiErrors already parses.
 */
export function useAdminForm() {
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const submit = async (method, url, payload, { isMultipart = false } = {}) => {
        setErrors({});
        setGeneralError(null);
        setIsSubmitting(true);

        let body = payload;
        const config = { silent: true };

        if (isMultipart === 'raw' && payload instanceof FormData) {
            // Caller already built the FormData (e.g. nested detail-field
            // buckets buildProductFormData knows how to serialize) and
            // embedded any _method override itself.
            body = payload;
            config.headers = { 'Content-Type': 'multipart/form-data' };
            method = 'post';
        } else if (isMultipart) {
            const formData = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                if (value === null || value === undefined) return;
                if (Array.isArray(value)) {
                    value.forEach((item) => formData.append(`${key}[]`, item));
                    return;
                }
                formData.append(key, value);
            });
            body = formData;
            config.headers = { 'Content-Type': 'multipart/form-data' };
            if (method === 'put' || method === 'patch') {
                formData.append('_method', method);
                method = 'post';
            }
        }

        try {
            const response = await window.axios[method](url, body, config);
            setIsSubmitting(false);
            return response.data;
        } catch (error) {
            setIsSubmitting(false);
            if (error.response?.status === 422) {
                const fieldErrors = {};
                Object.entries(error.response.data?.errors ?? {}).forEach(([field, messages]) => {
                    fieldErrors[field] = Array.isArray(messages) ? messages[0] : messages;
                });
                setErrors(fieldErrors);
                setGeneralError(error.response.data?.message ?? null);
            } else {
                setGeneralError(error.response?.data?.message ?? 'Something went wrong.');
            }
            throw error;
        }
    };

    return { submit, errors, generalError, isSubmitting, setGeneralError };
}
