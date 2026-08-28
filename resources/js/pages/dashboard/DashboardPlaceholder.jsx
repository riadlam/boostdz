import { Link } from 'react-router-dom';

export default function DashboardPlaceholder({ title, description }) {
    return (
        <div className="flex min-h-[50vh] flex-col items-center justify-center gap-3 px-4 text-center">
            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
            <p className="max-w-md text-sm text-muted-foreground">{description}</p>
            <Link to="/dashboard" className="btn-primary mt-2">
                Back to Dashboard
            </Link>
        </div>
    );
}
