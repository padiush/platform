import { faExclamationTriangle } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return {
            hasError: true,
            error: error,
        };
    }

    componentDidCatch(error, errorInfo) {
        console.error('ErrorBoundary caught an error', error, errorInfo);

        // Set the error and errorInfo in the state
        this.setState({
            error: error,
            errorInfo: errorInfo,
        });
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex flex-col items-center p-4">
                    <FontAwesomeIcon
                        icon={faExclamationTriangle}
                        className="text-5xl text-yellow-500"
                    />
                    <h1 className="mt-4 text-2xl font-semibold">
                        Ha ocurrido un error inesperado
                    </h1>
                    <div className="mockup-code bg-base-200 text-base-content mt-8 p-4">
                        {/** Pretty print the error info */}
                        <pre>
                            {this.state.error && this.state.error.toString()}
                        </pre>
                        <pre>
                            {this.state.errorInfo &&
                                this.state.errorInfo.componentStack.toString()}
                        </pre>
                    </div>
                    <div className="mt-4">
                        Por favor, recarga la página, si el error persiste,
                        contacta a soporte técnico y proporciona la información
                        mostrada arriba.
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
