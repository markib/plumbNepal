import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import MapPinAddress from '../components/MapPinAddress';
import { apiUrl } from '../utils/api';
import type { BookingFormValues } from '../types';
import { AIRequestInput } from '../components/AIRequestInput';

interface ServiceTypeOption {
  id: number;
  name: string;
}

interface NearbyPlumber {
  id: number;
  distance_meters?: number;
  user?: {
    name?: string;
    phone?: string;
  };
}

interface AiDiagnosisResult {
  issue_type: string;
  urgency: string;
  estimated_price_min: number;
  estimated_price_max: number;
  recommended_service: string;
  confidence: number;
  summary: string;
  ai_diagnosis_id?: number;
}

type NotificationType = 'success' | 'error';

const BookingPage: React.FC = () => {
  const { t } = useTranslation();
  const [serviceTypes, setServiceTypes] = useState<ServiceTypeOption[]>([]);
  const [isLoadingTypes, setIsLoadingTypes] = useState(true);
  const [notification, setNotification] = useState<
    | { type: NotificationType; message: string }
    | null
  >(null);
  const [nearbyPlumberCount, setNearbyPlumberCount] = useState<number | null>(null);
  const [nearbyPlumbers, setNearbyPlumbers] = useState<NearbyPlumber[]>([]);
  const [createdBookingId, setCreatedBookingId] = useState<number | null>(null);
  const [invitedPlumberId, setInvitedPlumberId] = useState<number | null>(null);
  const [aiDiagnosis, setAiDiagnosis] = useState<AiDiagnosisResult | null>(null);
  const [booking, setBooking] = useState<BookingFormValues>({
    latitude: 27.7172,
    longitude: 85.3240,
    service_type_id: 0,
    is_emergency: false,
    payment_method: 'cod',
  });

  useEffect(() => {
    if (!notification) {
      return;
    }

    const timer = window.setTimeout(() => {
      setNotification(null);
    }, 5000);

    return () => {
      window.clearTimeout(timer);
    };
  }, [notification]);

  useEffect(() => {
    const loadServiceTypes = async () => {
      setIsLoadingTypes(true);

      try {
        const response = await fetch(apiUrl('/api/v1/service-types'));
        if (!response.ok) {
          throw new Error('Unable to load service types');
        }

        const types: ServiceTypeOption[] = await response.json();
        setServiceTypes(types);
        if (types.length > 0 && booking.service_type_id === 0) {
          setBooking((current) => ({ ...current, service_type_id: types[0].id }));
        }
      } catch (error) {
        console.error('Failed to load service types', error);
      } finally {
        setIsLoadingTypes(false);
      }
    };

    loadServiceTypes();
  }, []);

  const parseApiError = async (response: Response) => {
    const contentType = response.headers.get('content-type') ?? '';
    if (contentType.includes('application/json')) {
      const data = await response.json();
      const errorMessage = data.errors
        ? Object.values(data.errors).flat().join(' ')
        : data.message;
      return errorMessage || t('bookingError');
    }

    return t('bookingError');
  };

  const invitePlumber = async (plumberProfileId: number) => {
    if (!createdBookingId) {
      return;
    }

    const response = await fetch(apiUrl(`/api/v1/bookings/${createdBookingId}/invite-plumber`), {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ plumber_profile_id: plumberProfileId }),
    });

    const contentType = response.headers.get('content-type') ?? '';
    if (response.ok && contentType.includes('application/json')) {
      const data = await response.json();
      setInvitedPlumberId(plumberProfileId);
      setNotification({ type: 'success', message: data.message || t('plumberInvitedSuccess') });
      return;
    }

    const message = await parseApiError(response);
    console.error('Invite API returned an error:', response.status, message);
    setNotification({ type: 'error', message });
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const response = await fetch(apiUrl('/api/v1/bookings'), {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(booking),
    });

    const contentType = response.headers.get('content-type') ?? '';
    if (response.ok && contentType.includes('application/json')) {
      const data = await response.json();
      console.log('Booking created', data);
      const plumberCount = Array.isArray(data.nearby_plumbers) ? data.nearby_plumbers.length : null;
      if (plumberCount !== null) {
        setNearbyPlumberCount(plumberCount);
      }
      setCreatedBookingId(data.booking?.id ?? null);
      setNearbyPlumbers(Array.isArray(data.nearby_plumbers) ? data.nearby_plumbers : []);
      setNotification({
        type: 'success',
        message: plumberCount !== null
          ? `${t('bookingSuccess')} Nearest plumbers found: ${plumberCount}`
          : t('bookingSuccess'),
      });
      return;
    }

    const message = await parseApiError(response);
    console.error('Booking API returned an error:', response.status, message);
    setNotification({ type: 'error', message });
  };

  return (
    <>
      {notification && (
        <div
          className={`fixed left-1/2 top-4 z-50 w-[calc(100%-2rem)] max-w-xl -translate-x-1/2 rounded-lg border p-4 shadow-sm ${
            notification.type === 'success'
              ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
              : 'border-rose-200 bg-rose-50 text-rose-900'
          }`}
        >
          {notification.message}
        </div>
      )}
      {nearbyPlumbers.length > 0 && (
        <section className="space-y-4 rounded-xl bg-slate-50 p-6 shadow-sm">
          <div>
            <h2 className="text-xl font-semibold">Nearby Plumbers</h2>
            <p className="mt-2 text-sm text-slate-600">
              We found {nearbyPlumberCount ?? nearbyPlumbers.length} available plumbers near your request.
            </p>
          </div>
          <div className="grid gap-4">
            {nearbyPlumbers.map((plumber) => (
              <div key={plumber.id} className="rounded-xl bg-white p-4 shadow-sm">
                <div className="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center">
                  <div>
                    <p className="text-base font-semibold text-slate-900">{plumber.user?.name ?? 'Plumber'}</p>
                    <p className="text-sm text-slate-600">{plumber.user?.phone ?? 'No phone available'}</p>
                  </div>
                  <div className="flex items-center gap-2">
                    {typeof plumber.distance_meters === 'number' && (
                      <span className="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
                        {(plumber.distance_meters / 1000).toFixed(1)} km
                      </span>
                    )}
                    <button
                      type="button"
                      onClick={() => invitePlumber(plumber.id)}
                      disabled={!createdBookingId || invitedPlumberId === plumber.id}
                      className="rounded bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                    >
                      {invitedPlumberId === plumber.id ? t('invited') : t('invitePlumber')}
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      )}
      <section className="space-y-6 rounded-xl bg-white p-6 shadow-sm">
        <h1 className="text-2xl font-semibold">{t('bookService')}</h1>
        <MapPinAddress value={booking} onChange={setBooking} />

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block">
            <span className="text-sm font-medium text-slate-700">Service Type</span>
            {isLoadingTypes ? (
              <p className="mt-1 text-sm text-slate-500">Loading service types...</p>
            ) : serviceTypes.length > 0 ? (
              <select
                className="mt-1 w-full"
                value={booking.service_type_id}
                onChange={(e) => setBooking({ ...booking, service_type_id: Number(e.target.value) })}
              >
                {serviceTypes.map((type) => (
                  <option key={type.id} value={type.id}>
                    {type.name}
                  </option>
                ))}
              </select>
            ) : (
              <p className="mt-1 text-sm text-red-600">No service types available.</p>
            )}
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">{t('landmark')}</span>
            <input
              type="text"
              className="mt-1 w-full"
              value={booking.landmark ?? ''}
              onChange={(e) => setBooking({ ...booking, landmark: e.target.value })}
            />
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">{t('wardNumber')}</span>
            <input
              type="text"
              className="mt-1 w-full"
              value={booking.ward_number ?? ''}
              onChange={(e) => setBooking({ ...booking, ward_number: e.target.value })}
            />
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">{t('toleName')}</span>
            <input
              type="text"
              className="mt-1 w-full"
              value={booking.tole_name ?? ''}
              onChange={(e) => setBooking({ ...booking, tole_name: e.target.value })}
            />
          </label>

          <label className="block">
            <span className="text-sm font-medium text-slate-700">{t('paymentMethod')}</span>
            <select
              className="mt-1 w-full"
              value={booking.payment_method}
              onChange={(e) => setBooking({ ...booking, payment_method: e.target.value as BookingFormValues['payment_method'] })}
            >
              <option value="cod">{t('cod')}</option>
              <option value="esewa">eSewa</option>
              <option value="khalti">Khalti</option>
              <option value="ime_pay">IME Pay</option>
            </select>
          </label>
        </div>

        <AIRequestInput
          onAnalysisComplete={(result) => {
            setAiDiagnosis(result);
            setBooking((prev) => ({
              ...prev,
              service_notes: result.summary,
              ai_diagnosis_id: result.ai_diagnosis_id,
            }));
          }}
        />

        <label className="flex items-center gap-3">
          <input
            type="checkbox"
            checked={booking.is_emergency}
            onChange={(e) => setBooking({ ...booking, is_emergency: e.target.checked })}
          />
          <span>{t('isEmergency')}</span>
        </label>

        <button
          type="submit"
          className="rounded bg-cyan-600 px-5 py-3 text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
          disabled={isLoadingTypes || serviceTypes.length === 0 || booking.service_type_id === 0 || createdBookingId !== null}
        >
          {createdBookingId ? t('bookingCreated') : t('requestService')}
        </button>
      </form>
    </section>
    </>
  );
};

export default BookingPage;
