import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { ThemeProvider } from './components/ThemeProvider';
import { GuestOnly, RequireAuth } from './components/auth/RequireAuth';
import { AuthProvider } from './context/AuthContext';
import Home from './pages/Home';
import SignIn from './pages/auth/SignIn';
import DashboardLayout from './pages/dashboard/DashboardLayout';
import DashboardHome from './pages/dashboard/DashboardHome';
import DashboardPlaceholder from './pages/dashboard/DashboardPlaceholder';
import CreateOrder from './pages/dashboard/CreateOrder';
import OrderHistory from './pages/dashboard/OrderHistory';
import Pricing from './pages/dashboard/Pricing';
import Billing from './pages/dashboard/Billing';
import FaqsHelp from './pages/dashboard/FaqsHelp';
import Checkout from './pages/dashboard/Checkout';
import CheckoutCcpBaridimob from './pages/dashboard/CheckoutCcpBaridimob';

createRoot(document.getElementById('root')).render(
    <ThemeProvider>
        <BrowserRouter>
            <AuthProvider>
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route
                        path="/auth/sign-in"
                        element={
                            <GuestOnly>
                                <SignIn />
                            </GuestOnly>
                        }
                    />
                    <Route
                        path="/dashboard"
                        element={
                            <RequireAuth>
                                <DashboardLayout />
                            </RequireAuth>
                        }
                    >
                        <Route index element={<DashboardHome />} />
                        <Route path="orders/create" element={<CreateOrder />} />
                        <Route path="orders/history" element={<OrderHistory />} />
                        <Route
                            path="orders/repeated"
                            element={
                                <DashboardPlaceholder
                                    title="Repeated Orders"
                                    description="Manage auto-refill and recurring boosts for posts that need ongoing engagement."
                                />
                            }
                        />
                        <Route path="pricing" element={<Pricing />} />
                        <Route path="billing" element={<Billing />} />
                        <Route path="faqs" element={<FaqsHelp />} />
                        <Route path="*" element={<Navigate to="/dashboard" replace />} />
                    </Route>
                    <Route
                        path="/checkout"
                        element={
                            <RequireAuth>
                                <DashboardLayout />
                            </RequireAuth>
                        }
                    >
                        <Route index element={<Checkout />} />
                        <Route path="ccp-baridimob" element={<CheckoutCcpBaridimob />} />
                    </Route>
                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </AuthProvider>
        </BrowserRouter>
    </ThemeProvider>,
);
