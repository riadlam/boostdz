export function syncDocumentLocale(language) {
    const lng = (language || 'en').split('-')[0];
    const root = document.documentElement;
    root.lang = lng;
    root.dir = 'ltr';
    root.classList.toggle('locale-ar', lng === 'ar');
}
