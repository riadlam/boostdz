export function canViewServiceCatalog(user) {
    return Boolean(user?.can_view_service_catalog);
}
