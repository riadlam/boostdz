import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { BrandLogo } from '../../components/Brand';
import PhoneField, { isValidPhone } from '../../components/auth/PhoneField';
import { useAuth } from '../../context/AuthContext';
import { ApiError } from '../../lib/api';
import { cn } from '../../lib/cn';

export default function SignUp() {
    const { t } = useTranslation(['auth', 'common']);
    const { register } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const from = location.state?.from || '/dashboard';

    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});

    async function handleSubmit(event) {
        event.preventDefault();
        setError('');
        setFieldErrors({});

        if (!isValidPhone(phone)) {
            setFieldErrors({ phone: [t('auth:phoneInvalid')] });
            return;
        }

        setSubmitting(true);

        try {
            await register({
                email: email.trim(),
                phone,
                password,
            });
            navigate(from, { replace: true });
        } catch (err) {
            if (err instanceof ApiError) {
                setFieldErrors(err.errors || {});
                setError(err.message || t('auth:unableToSignUp'));
            } else {
                setError(t('auth:somethingWentWrong'));
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="relative min-h-screen overflow-hidden bg-[var(--color-dash-canvas)]">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0"
                style={{
                    background:
                        'radial-gradient(ellipse 80% 50% at 50% -10%, color-mix(in oklab, var(--color-primary) 18%, transparent), transparent 55%), radial-gradient(ellipse 60% 40% at 100% 100%, color-mix(in oklab, var(--color-primary) 8%, transparent), transparent 50%)',
                }}
            />

            <div className="relative mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-10">
                <div className="mb-8 flex flex-col items-center text-center">
                    <BrandLogo className="h-10" href="/" nameClassName="text-base" />
                    <h1 className="mt-8 text-[1.75rem] font-bold tracking-tight text-foreground">{t('auth:createAccount')}</h1>
                    <p className="mt-2 text-[0.975rem] font-medium leading-relaxed text-muted-foreground">
                        {t('auth:signUpSubtitle')}
                    </p>
                </div>

                <div className="rounded-2xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-6 shadow-sm md:p-8">
                    <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                        {error ? (
                            <div
                                role="alert"
                                className="rounded-xl border border-red-500/20 bg-red-500/8 px-4 py-3 text-[0.9375rem] font-medium text-red-700 dark:text-red-300"
                            >
                                {error}
                            </div>
                        ) : null}

                        <div>
                            <label htmlFor="email" className="dash-field-label">
                                {t('auth:email')}
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                required
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className={cn('dash-input', fieldErrors.email && 'border-red-500/50')}
                                placeholder={t('auth:emailPlaceholder')}
                            />
                            {fieldErrors.email?.[0] ? (
                                <p className="mt-1 text-xs text-red-600 dark:text-red-400">{fieldErrors.email[0]}</p>
                            ) : null}
                        </div>

                        <div>
                            <label htmlFor="phone" className="dash-field-label">
                                {t('auth:phone')}
                            </label>
                            <PhoneField
                                id="phone"
                                value={phone}
                                onChange={setPhone}
                                disabled={submitting}
                                invalid={Boolean(fieldErrors.phone)}
                            />
                            {fieldErrors.phone?.[0] ? (
                                <p className="mt-1 text-xs text-red-600 dark:text-red-400">{fieldErrors.phone[0]}</p>
                            ) : null}
                        </div>

                        <div>
                            <label htmlFor="password" className="dash-field-label">
                                {t('auth:password')}
                            </label>
                            <div className="relative">
                                <input
                                    id="password"
                                    name="password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="new-password"
                                    required
                                    minLength={8}
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    className={cn('dash-input pr-11', fieldErrors.password && 'border-red-500/50')}
                                    placeholder={t('auth:passwordPlaceholder')}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    className="absolute top-1/2 right-2 -translate-y-1/2 rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                    aria-label={showPassword ? t('common:aria.hidePassword') : t('common:aria.showPassword')}
                                >
                                    {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                </button>
                            </div>
                            {fieldErrors.password?.[0] ? (
                                <p className="mt-1 text-xs text-red-600 dark:text-red-400">{fieldErrors.password[0]}</p>
                            ) : null}
                        </div>

                        <button
                            type="submit"
                            disabled={submitting || !isValidPhone(phone)}
                            className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary text-[1rem] font-semibold text-primary-foreground transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {submitting ? (
                                <>
                                    <LoaderCircle className="size-4 animate-spin" />
                                    {t('auth:creatingAccount')}
                                </>
                            ) : (
                                t('auth:createAccount')
                            )}
                        </button>
                    </form>

                    <p className="mt-5 text-center text-sm text-muted-foreground">
                        {t('auth:alreadyHaveAccount')}{' '}
                        <Link to="/auth/sign-in" className="font-semibold text-primary hover:underline">
                            {t('auth:signIn')}
                        </Link>
                    </p>
                </div>

                <p className="mt-6 text-center text-sm text-muted-foreground">
                    <Link to="/" className="font-medium text-foreground/80 transition hover:text-primary">
                        {t('auth:backToHome')}
                    </Link>
                </p>
            </div>
        </div>
    );
}
