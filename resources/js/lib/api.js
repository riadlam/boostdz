import { clearSession, getToken } from './authStorage';
import i18n, { resolveLocale } from '../i18n';

const API_BASE = '/api/v1';

export class ApiError extends Error {
    constructor(message, { status, errors, code, payload } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors || {};
        this.code = code;
        this.payload = payload;
    }
}

export async function api(path, { method = 'GET', body, token, headers = {} } = {}) {
    const authToken = token ?? getToken();
    const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;

    const response = await fetch(`${API_BASE}${path}`, {
        method,
        headers: {
            Accept: 'application/json',
            'Accept-Language': resolveLocale(i18n.language),
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
            ...headers,
        },
        body: body == null ? undefined : isFormData ? body : JSON.stringify(body),
    });

    let data = null;
    const text = await response.text();
    if (text) {
        try {
            data = JSON.parse(text);
        } catch {
            data = { message: text };
        }
    }

    if (!response.ok) {
        if (response.status === 401) {
            clearSession();
        }

        const message =
            data?.message ||
            data?.errors?.email?.[0] ||
            Object.values(data?.errors || {})?.[0]?.[0] ||
            'Request failed.';

        throw new ApiError(message, {
            status: response.status,
            errors: data?.errors,
            code: data?.code,
            payload: data,
        });
    }

    return data;
}

export const authApi = {
    login: (payload) => api('/auth/login', { method: 'POST', body: payload }),
    register: (payload) => api('/auth/register', { method: 'POST', body: payload }),
    me: () => api('/auth/me'),
    logout: () => api('/auth/logout', { method: 'POST' }),
};

function toQuery(params = {}) {
    const search = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return;
        search.set(key, String(value));
    });
    const qs = search.toString();
    return qs ? `?${qs}` : '';
}

export const servicesApi = {
    platforms: () => api('/services/platforms'),
    list: (params) => api(`/services${toQuery(params)}`),
    show: (id) => api(`/services/${id}`),
    quote: (id, quantity) => api(`/services/${id}/quote${toQuery({ quantity })}`),
};

export const catalogApi = {
    platforms: () => api('/catalog/platforms'),
    storefront: () => api('/catalog/storefront'),
    categories: (slug) => api(`/catalog/platforms/${encodeURIComponent(slug)}/categories`),
    services: (categoryId, params) => api(`/catalog/categories/${categoryId}/services${toQuery(params)}`),
};

export const contentApi = {
    testimonials: () => api('/content/testimonials'),
    platformCards: () => api('/content/platform-cards'),
};

export const ordersApi = {
    list: (params) => api(`/orders${toQuery(params)}`),
    create: (payload) => api('/orders', { method: 'POST', body: payload }),
    show: (id) => api(`/orders/${id}`),
    refill: (id) => api(`/orders/${id}/refill`, { method: 'POST' }),
};

export const checkoutApi = {
    settings: () => api('/checkout/settings'),
    submitCcpReceipt: (formData) => api('/checkout/ccp-receipt', { method: 'POST', body: formData }),
};

export const depositsApi = {
    list: (params) => api(`/deposits${toQuery(params)}`),
    create: (formData) => api('/deposits', { method: 'POST', body: formData }),
};

export const sofizpayApi = {
    initCheckout: (payload) => api('/payments/sofizpay/checkout', { method: 'POST', body: payload }),
    initTopup: (payload) => api('/payments/sofizpay/topup', { method: 'POST', body: payload }),
    status: (invoiceId) => api(`/payments/sofizpay/${encodeURIComponent(invoiceId)}/status`),
};

export const walletApi = {
    show: () => api('/wallet'),
};
