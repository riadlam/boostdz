const RTL_LOCALES = new Set(['ar']);

export function syncDocumentLocale(language) {
    const lng = (language || 'en').split('-')[0];
    const root = document.documentElement;
    root.lang = lng;
    root.dir = RTL_LOCALES.has(lng) ? 'rtl' : 'ltr';
    root.classList.toggle('locale-ar', lng === 'ar');
}
