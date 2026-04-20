import React from 'react';
import BookingPage from './BookingPage';
import PlumberDashboard from './PlumberDashboard';
import CustomerProposalList from './CustomerProposalList';
import type { AuthUser } from '../types';

interface DashboardPageProps {
  user: AuthUser;
  onLogout: () => void;
}

const DashboardPage: React.FC<DashboardPageProps> = ({ user, onLogout }) => {
  return (
    <div className="min-h-screen bg-slate-50 p-4">
      <div className="mx-auto max-w-6xl space-y-6">
        <header className="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
          <div className="space-y-3">
            <div>
              <p className="text-sm text-slate-500">Signed in as</p>
              <h1 className="text-2xl font-semibold text-slate-900">{user.name}</h1>
              <p className="text-sm text-slate-600">Role: {user.role.replace('_', ' ')}</p>
            </div>
            <div className="grid gap-2 sm:grid-cols-3">
              <div className="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <p className="text-xs uppercase tracking-wide text-slate-500">Email</p>
                <p className="mt-1 font-medium text-slate-900">{user.email}</p>
              </div>
              <div className="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <p className="text-xs uppercase tracking-wide text-slate-500">Phone</p>
                <p className="mt-1 font-medium text-slate-900">{user.phone}</p>
              </div>
              <div className="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <p className="text-xs uppercase tracking-wide text-slate-500">Locale</p>
                <p className="mt-1 font-medium text-slate-900">{user.locale.toUpperCase()}</p>
              </div>
              {user.location ? (
                <div className="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                  <p className="text-xs uppercase tracking-wide text-slate-500">Location</p>
                  <p className="mt-1 font-medium text-slate-900">
                    {user.location.address ?? user.location.description ?? `${user.location.latitude.toFixed(4)}, ${user.location.longitude.toFixed(4)}`}
                  </p>
                  <p className="text-xs text-slate-500">
                    {user.location.latitude.toFixed(4)}, {user.location.longitude.toFixed(4)}
                  </p>
                </div>
              ) : null}
            </div>
          </div>
          <button
            type="button"
            onClick={onLogout}
            className="rounded bg-slate-800 px-4 py-2 text-white hover:bg-slate-900"
          >
            Logout
          </button>
        </header>

        <section className="rounded-xl bg-white p-6 shadow-sm">
          <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 className="text-xl font-semibold">Request Service</h2>
              <p className="text-sm text-slate-600">
                Use this form to create a new service booking from any role. Customers, plumbers, and partners can all request work from the dashboard.
              </p>
            </div>
            <span className="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
              Role: {user.role.replace('_', ' ')}
            </span>
          </div>
          <BookingPage />
        </section>

        {user.role === 'customer' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <CustomerProposalList />
          </section>
        )}

        {user.role === 'plumber' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Plumber Open Requests</h2>
            <PlumberDashboard />
          </section>
        )}

        {user.role === 'service_provider' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Service Provider Dashboard</h2>
            <p className="text-slate-600">
              Manage service requests, monitor plumber availability, and review service performance.
            </p>
          </section>
        )}

        {user.role === 'shop_keeper' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Shop Keeper Dashboard</h2>
            <p className="text-slate-600">
              View orders, track inventory, and support plumbers with required tools and parts.
            </p>
          </section>
        )}

        {user.role === 'admin' && (
          <section className="rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-xl font-semibold">Admin Dashboard</h2>
            <p className="text-slate-600">
              Manage users, review plumber verifications, and oversee booking operations.
            </p>
          </section>
        )}
      </div>
    </div>
  );
};

export default DashboardPage;
