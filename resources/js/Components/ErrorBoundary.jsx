import { Component } from 'react';

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
            return (
                <div className="page-shell flex min-h-[60vh] flex-col items-center justify-center text-center">
                    <h1 className="commerce-title">Something went wrong</h1>
                    <p className="commerce-copy">Please reload the page and try again.</p>
                    <button type="button" className="btn-primary mt-6" onClick={() => window.location.reload()}>
                        Reload page
                    </button>
                </div>
            );
        }

        return this.props.children;
    }
}
