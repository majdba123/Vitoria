import { Component } from 'react';
import { useI18n } from '@/hooks/use-i18n';

function ErrorFallback() {
    const { common } = useI18n();

    return (
        <div className="page-shell flex min-h-[60vh] flex-col items-center justify-center text-center">
            <h1 className="commerce-title">{common.generic_error}</h1>
            <p className="commerce-copy">{common.reload_page_hint}</p>
            <button type="button" className="btn-primary mt-6" onClick={() => window.location.reload()}>
                {common.reload_page}
            </button>
        </div>
    );
}

export class ErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Unhandled error in application render tree:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return <ErrorFallback />;
        }

        return this.props.children;
    }
}
