import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { ApiError, authApi } from '../lib/api';
import { clearSession, getStoredUser, getToken, setSession } from '../lib/authStorage';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(() => getStoredUser());
    const [token, setToken] = useState(() => getToken());
    const [bootstrapping, setBootstrapping] = useState(() => Boolean(getToken()));

    useEffect(() => {
        let cancelled = false;

        async function bootstrap() {
            if (!getToken()) {
                setBootstrapping(false);
                return;
            }

            try {
                const data = await authApi.me();
                if (cancelled) return;
                const nextUser = data.user;
                setUser(nextUser);
                setSession({ user: nextUser });
            } catch {
                if (cancelled) return;
                clearSession();
                setUser(null);
                setToken(null);
            } finally {
                if (!cancelled) setBootstrapping(false);
            }
        }

        bootstrap();
        return () => {
            cancelled = true;
        };
    }, []);

    const login = useCallback(async ({ email, password }) => {
        const data = await authApi.login({
            email,
            password,
            device_name: 'boostdz-web',
        });

        setSession({ token: data.token, user: data.user });
        setToken(data.token);
        setUser(data.user);
        return data.user;
    }, []);

    const logout = useCallback(async () => {
        try {
            if (getToken()) {
                await authApi.logout();
            }
        } catch (error) {
            // Still clear local session even if API logout fails.
            if (!(error instanceof ApiError)) {
                // ignore
            }
        } finally {
            clearSession();
            setToken(null);
            setUser(null);
        }
    }, []);

    const refreshUser = useCallback(async () => {
        const data = await authApi.me();
        setUser(data.user);
        setSession({ user: data.user });
        return data.user;
    }, []);

    const value = useMemo(
        () => ({
            user,
            token,
            isAuthenticated: Boolean(token && user),
            bootstrapping,
            login,
            logout,
            refreshUser,
            setUser,
        }),
        [user, token, bootstrapping, login, logout, refreshUser],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) {
        throw new Error('useAuth must be used within AuthProvider');
    }
    return ctx;
}
