import { Navigate, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';

export function RequireAuth({ children }) {
    const { t } = useTranslation('auth');
    const { isAuthenticated, bootstrapping } = useAuth();
    const location = useLocation();

    if (bootstrapping) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-[var(--color-dash-canvas)]">
                <div className="flex flex-col items-center gap-3">
                    <div className="size-8 animate-spin rounded-full border-2 border-primary/25 border-t-primary" />
                    <p className="text-sm text-muted-foreground">{t('checkingSession')}</p>
                </div>
            </div>
        );
    }

    if (!isAuthenticated) {
        return <Navigate to="/auth/sign-in" replace state={{ from: location.pathname }} />;
    }

    return children;
}

export function GuestOnly({ children }) {
    const { isAuthenticated, bootstrapping } = useAuth();

    if (bootstrapping) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-[var(--color-dash-canvas)]">
                <div className="size-8 animate-spin rounded-full border-2 border-primary/25 border-t-primary" />
            </div>
        );
    }

    if (isAuthenticated) {
        return <Navigate to="/dashboard" replace />;
    }

    return children;
}
