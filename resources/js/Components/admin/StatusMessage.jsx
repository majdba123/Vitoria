/**
 * Inline success / failure notice for admin forms. Success is announced
 * politely (role="status"); failures interrupt (role="alert").
 */
export function StatusMessage({ tone, children, className = '' }) {
    const isSuccess = tone === 'success';

    return (
        <p
            role={isSuccess ? 'status' : 'alert'}
            className={`rounded-md border px-4 py-2.5 text-sm font-medium ${
                isSuccess
                    ? 'border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]'
                    : 'border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]'
            } ${className}`}
        >
            {children}
        </p>
    );
}
