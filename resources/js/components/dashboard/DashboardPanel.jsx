import { cn } from '../../lib/cn';

export function DashboardPanel({ title, action, children, className, bodyClassName }) {
    return (
        <section className={cn('dash-panel overflow-hidden', className)}>
            {title ? (
                <div className="dash-panel-header">
                    <h2 className="dash-panel-title">{title}</h2>
                    {action}
                </div>
            ) : null}
            <div className={cn('dash-panel-body', bodyClassName)}>{children}</div>
        </section>
    );
}

export function DashboardTable({ children, className }) {
    return (
        <div className="dash-table-wrap">
            <table className={cn('dash-table', className)}>{children}</table>
        </div>
    );
}

export function DashboardStatGrid({ children, className }) {
    return <div className={cn('dash-stat-grid', className)}>{children}</div>;
}

export function DashboardStat({ label, value, hint, tone }) {
    return (
        <div className={cn('dash-stat', tone && `dash-stat-${tone}`)}>
            <p className="dash-stat-label">{label}</p>
            <p className="dash-stat-value">{value}</p>
            {hint ? <p className="dash-stat-hint">{hint}</p> : null}
        </div>
    );
}
