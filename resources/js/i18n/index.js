import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import { syncDocumentLocale } from './syncDocumentLocale';

import enCommon from './locales/en/common.json';
import enNav from './locales/en/nav.json';
import enAuth from './locales/en/auth.json';
import enDashboard from './locales/en/dashboard.json';
import enBilling from './locales/en/billing.json';
import enPricing from './locales/en/pricing.json';
import enCheckout from './locales/en/checkout.json';
import enOrders from './locales/en/orders.json';
import enCountries from './locales/en/countries.json';
import enValidation from './locales/en/validation.json';
import enLanding from './locales/en/landing.json';
import enFaq from './locales/en/faq.json';

import frCommon from './locales/fr/common.json';
import frNav from './locales/fr/nav.json';
import frAuth from './locales/fr/auth.json';
import frDashboard from './locales/fr/dashboard.json';
import frBilling from './locales/fr/billing.json';
import frPricing from './locales/fr/pricing.json';
import frCheckout from './locales/fr/checkout.json';
import frOrders from './locales/fr/orders.json';
import frCountries from './locales/fr/countries.json';
import frValidation from './locales/fr/validation.json';
import frLanding from './locales/fr/landing.json';
import frFaq from './locales/fr/faq.json';

import arCommon from './locales/ar/common.json';
import arNav from './locales/ar/nav.json';
import arAuth from './locales/ar/auth.json';
import arDashboard from './locales/ar/dashboard.json';
import arBilling from './locales/ar/billing.json';
import arPricing from './locales/ar/pricing.json';
import arCheckout from './locales/ar/checkout.json';
import arOrders from './locales/ar/orders.json';
import arCountries from './locales/ar/countries.json';
import arValidation from './locales/ar/validation.json';
import arLanding from './locales/ar/landing.json';
import arFaq from './locales/ar/faq.json';

const resources = {
    en: {
        common: enCommon,
        nav: enNav,
        auth: enAuth,
        dashboard: enDashboard,
        billing: enBilling,
        pricing: enPricing,
        checkout: enCheckout,
        orders: enOrders,
        countries: enCountries,
        validation: enValidation,
        landing: enLanding,
        faq: enFaq,
    },
    fr: {
        common: frCommon,
        nav: frNav,
        auth: frAuth,
        dashboard: frDashboard,
        billing: frBilling,
        pricing: frPricing,
        checkout: frCheckout,
        orders: frOrders,
        countries: frCountries,
        validation: frValidation,
        landing: frLanding,
        faq: frFaq,
    },
    ar: {
        common: arCommon,
        nav: arNav,
        auth: arAuth,
        dashboard: arDashboard,
        billing: arBilling,
        pricing: arPricing,
        checkout: arCheckout,
        orders: arOrders,
        countries: arCountries,
        validation: arValidation,
        landing: arLanding,
        faq: arFaq,
    },
};

const namespaces = [
    'common',
    'nav',
    'auth',
    'dashboard',
    'billing',
    'pricing',
    'checkout',
    'orders',
    'countries',
    'validation',
    'landing',
    'faq',
];

i18n.use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources,
        fallbackLng: 'en',
        supportedLngs: ['en', 'fr', 'ar'],
        ns: namespaces,
        defaultNS: 'common',
        interpolation: { escapeValue: false },
        detection: {
            order: ['localStorage', 'navigator'],
            caches: ['localStorage'],
            lookupLocalStorage: 'boostdz-locale',
        },
        react: { useSuspense: false },
    });

syncDocumentLocale(i18n.resolvedLanguage || i18n.language);
i18n.on('languageChanged', (lng) => syncDocumentLocale(lng));

export default i18n;

export function resolveLocale(language) {
    const code = String(language || 'en').split('-')[0].toLowerCase();
    return ['en', 'fr', 'ar'].includes(code) ? code : 'en';
}

export function intlLocale(language) {
    const code = resolveLocale(language);
    if (code === 'fr') return 'fr-FR';
    if (code === 'ar') return 'ar-DZ';
    return 'en-GB';
}
