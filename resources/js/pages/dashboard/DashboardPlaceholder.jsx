import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

export default function DashboardPlaceholder({ title, description, titleKey, descriptionKey }) {
    const { t } = useTranslation(['nav', 'dashboard']);

    const displayTitle = titleKey ? t(titleKey) : title;
    const displayDescription = descriptionKey ? t(descriptionKey) : description;

    return (
        <div className="flex min-h-[50vh] flex-col items-center justify-center gap-3 px-4 text-center">
            <h1 className="text-2xl font-semibold tracking-tight">{displayTitle}</h1>
            <p className="max-w-md text-sm text-muted-foreground">{displayDescription}</p>
            <Link to="/dashboard" className="btn-primary mt-2">
                {t('dashboard:backToDashboard')}
            </Link>
        </div>
    );
}
