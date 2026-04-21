import React, { useEffect, useState } from 'react';
import { apiUrl } from '../utils/api';
import { authHeaders } from '../utils/auth';

interface OpenRequest {
  id: number;
  service_type_name: string;
  landmark?: string;
  ward_number?: string;
  tole_name?: string;
  created_at: string;
  latitude: number;
  longitude: number;
}

interface AssignedJob {
  id: number;
  workflow_status: string;
  contract_start_code?: string;
  contract_terms?: {
    base_fee: number;
    material_cost: number;
    eta_minutes: number;
    details?: Record<string, unknown>;
  };
  job_order_json?: Record<string, unknown>;
  job_started_at?: string;
  service_type_name: string;
  landmark?: string;
  ward_number?: string;
  tole_name?: string;
  customer_name: string;
}

const PlumberDashboard: React.FC = () => {
  const [requests, setRequests] = useState<OpenRequest[]>([]);
  const [assignedJobs, setAssignedJobs] = useState<AssignedJob[]>([]);
  const [selectedRequest, setSelectedRequest] = useState<OpenRequest | null>(null);
  const [selectedJob, setSelectedJob] = useState<AssignedJob | null>(null);
  const [selectedCompleteJob, setSelectedCompleteJob] = useState<AssignedJob | null>(null);
  const [showModal, setShowModal] = useState(false);
  const [showOtpModal, setShowOtpModal] = useState(false);
  const [showCompleteModal, setShowCompleteModal] = useState(false);
  const [baseFee, setBaseFee] = useState('0');
  const [materialCost, setMaterialCost] = useState('0');
  const [etaMinutes, setEtaMinutes] = useState('30');
  const [notes, setNotes] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [otpError, setOtpError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [otpLoading, setOtpLoading] = useState(false);

  useEffect(() => {
    const fetchRequests = async () => {
      try {
        const [openResponse, assignedResponse] = await Promise.all([
          fetch(apiUrl('/api/v1/plumber/open-requests'), { headers: authHeaders() }),
          fetch(apiUrl('/api/v1/plumber/assigned-jobs'), { headers: authHeaders() }),
        ]);

        if (!openResponse.ok) {
          throw new Error('Unable to load open requests');
        }

        if (!assignedResponse.ok) {
          throw new Error('Unable to load assigned jobs');
        }

        const openData = await openResponse.json();
        const assignedData = await assignedResponse.json();
        setRequests(openData.requests || []);
        setAssignedJobs(assignedData.jobs || []);
      } catch (err) {
        console.error(err);
      }
    };

    fetchRequests();
  }, []);

  const openQuoteModal = (request: OpenRequest) => {
    setSelectedRequest(request);
    setBaseFee('1200');
    setMaterialCost('300');
    setEtaMinutes('45');
    setNotes('Include plumbing repair and material estimate');
    setShowModal(true);
    setError(null);
    setSuccess(null);
  };

  const openOtpModal = (job: AssignedJob) => {
    setSelectedJob(job);
    setOtpCode('');
    setOtpError(null);
    setShowOtpModal(true);
  };

  const openCompleteModal = (job: AssignedJob) => {
    setSelectedCompleteJob(job);
    setError(null);
    setShowCompleteModal(true);
  };

  const submitQuote = async () => {
    if (!selectedRequest) return;
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${selectedRequest.id}/proposals`), {
        method: 'POST',
        headers: {
          ...authHeaders(),
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          base_fee: Number(baseFee),
          material_cost: Number(materialCost),
          eta_minutes: Number(etaMinutes),
          proposal_terms: { notes },
        }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to send quote.');
        return;
      }

      setSuccess('Quote sent successfully. The customer has been notified.');
      setRequests((current) => current.filter((item) => item.id !== selectedRequest.id));
      setShowModal(false);
      setSelectedRequest(null);
    } catch (err) {
      setError('Unable to send quote.');
    } finally {
      setLoading(false);
    }
  };

  const submitStartJob = async () => {
    if (!selectedJob) return;
    setOtpLoading(true);
    setOtpError(null);

    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${selectedJob.id}/start-job`), {
        method: 'POST',
        headers: {
          ...authHeaders(),
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ contract_start_code: otpCode }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setOtpError(data?.message || 'Unable to start job.');
        return;
      }

      const updated = await response.json();
      setAssignedJobs((current) => current.map((item) => (item.id === selectedJob.id ? { ...item, workflow_status: 'in_progress', job_started_at: updated.job_started_at } : item)));
      setShowOtpModal(false);
      setSelectedJob(null);
    } catch (err) {
      setOtpError('Unable to start job.');
    } finally {
      setOtpLoading(false);
    }
  };

  const completeJob = async (job: AssignedJob) => {
    setLoading(true);
    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${job.id}/complete-job`), {
        method: 'POST',
        headers: authHeaders(),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to complete job.');
        return;
      }

      setAssignedJobs((current) => current.filter((item) => item.id !== job.id));
    } catch (err) {
      setError('Unable to complete job.');
    } finally {
      setLoading(false);
    }
  };

  const submitCompleteJob = async () => {
    if (!selectedCompleteJob) return;
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(apiUrl(`/api/v1/bookings/${selectedCompleteJob.id}/complete-job`), {
        method: 'POST',
        headers: {
          ...authHeaders(),
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        const data = await response.json().catch(() => null);
        setError(data?.message || 'Unable to complete job.');
        return;
      }

      setAssignedJobs((current) => current.filter((item) => item.id !== selectedCompleteJob.id));
      setShowCompleteModal(false);
      setSelectedCompleteJob(null);
    } catch (err) {
      setError('Unable to complete job.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="rounded-xl bg-white p-6 shadow-sm">
        <h3 className="text-xl font-semibold">Open Requests Near You</h3>
        <p className="mt-2 text-sm text-slate-600">
          Nearby customer requests within your area will appear here. Select one to send a quote.
        </p>
      </div>

      {success && (
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
          {success}
        </div>
      )}

      {assignedJobs.length > 0 && (
        <div className="rounded-xl bg-white p-6 shadow-sm">
          <h3 className="text-xl font-semibold">Assigned Jobs</h3>
          <p className="mt-2 text-sm text-slate-600">
            Contracted jobs assigned to you appear here. Enter the customer start code when you arrive.
          </p>
          <div className="mt-4 grid gap-4">
            {assignedJobs.map((job) => (
              <div key={job.id} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                  <div>
                    <p className="text-sm font-semibold text-slate-900">{job.service_type_name}</p>
                    <p className="mt-1 text-sm text-slate-600">
                      {job.landmark ?? 'No landmark'}, {job.tole_name ?? job.ward_number}
                    </p>
                    <p className="mt-1 text-sm text-slate-500">Status: {job.workflow_status.replace('_', ' ')}</p>
                  </div>
                  {job.workflow_status === 'contracted' ? (
                    <button
                      type="button"
                      onClick={() => openOtpModal(job)}
                      className="rounded bg-cyan-600 px-4 py-2 text-white hover:bg-cyan-700"
                    >
                      Enter Start Code
                    </button>
                  ) : (
                    <button
                      type="button"
                      onClick={() => openCompleteModal(job)}
                      className="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700"
                    >
                      Complete Job
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {requests.length === 0 ? (
        <div className="rounded-xl bg-white p-6 shadow-sm text-slate-600">
          No open requests found within your service radius.
        </div>
      ) : (
        <div className="grid gap-4">
          {requests.map((request) => (
            <div key={request.id} className="rounded-xl bg-white p-6 shadow-sm">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <p className="text-sm font-semibold text-slate-900">{request.service_type_name}</p>
                  <p className="mt-1 text-sm text-slate-600">
                    {request.landmark ?? 'No landmark'}, {request.tole_name ?? request.ward_number}
                  </p>
                  <p className="mt-2 text-sm text-slate-500">Requested {new Date(request.created_at).toLocaleString()}</p>
                </div>
                <button
                  type="button"
                  onClick={() => openQuoteModal(request)}
                  className="rounded bg-cyan-600 px-4 py-2 text-white hover:bg-cyan-700"
                >
                  Send Quote
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {showModal && selectedRequest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
          <div className="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
            <h3 className="text-xl font-semibold">Send Deal Proposal</h3>
            <p className="mt-2 text-sm text-slate-600">Review the request and send a quote with ETA and material cost.</p>

            <div className="mt-6 grid gap-4 md:grid-cols-3">
              <label className="block">
                <span className="text-sm font-medium text-slate-700">Base Fee</span>
                <input
                  type="number"
                  value={baseFee}
                  onChange={(e) => setBaseFee(e.target.value)}
                  className="mt-1 w-full rounded border border-slate-300 px-3 py-2"
                />
              </label>
              <label className="block">
                <span className="text-sm font-medium text-slate-700">Material Cost</span>
                <input
                  type="number"
                  value={materialCost}
                  onChange={(e) => setMaterialCost(e.target.value)}
                  className="mt-1 w-full rounded border border-slate-300 px-3 py-2"
                />
              </label>
              <label className="block">
                <span className="text-sm font-medium text-slate-700">ETA (minutes)</span>
                <input
                  type="number"
                  value={etaMinutes}
                  onChange={(e) => setEtaMinutes(e.target.value)}
                  className="mt-1 w-full rounded border border-slate-300 px-3 py-2"
                />
              </label>
            </div>

            <label className="block mt-4">
              <span className="text-sm font-medium text-slate-700">Proposal Notes</span>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={4}
                className="mt-1 w-full rounded border border-slate-300 px-3 py-2"
              />
            </label>

            {error && (
              <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                {error}
              </div>
            )}

            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={() => setShowModal(false)}
                className="rounded border border-slate-300 px-4 py-2 text-slate-700"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={submitQuote}
                disabled={loading}
                className="rounded bg-cyan-600 px-4 py-2 text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                {loading ? 'Sending...' : 'Send Proposal'}
              </button>
            </div>
          </div>
        </div>
      )}

      {showOtpModal && selectedJob && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
          <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 className="text-xl font-semibold">Enter Customer Start Code</h3>
            <p className="mt-2 text-sm text-slate-600">
              Ask the customer for the 4-digit code to verify arrival and start the job.
            </p>

            <label className="block mt-6">
              <span className="text-sm font-medium text-slate-700">Start Code</span>
              <input
                type="text"
                value={otpCode}
                onChange={(e) => setOtpCode(e.target.value)}
                maxLength={4}
                className="mt-1 w-full rounded border border-slate-300 px-3 py-2"
              />
            </label>

            {otpError && (
              <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                {otpError}
              </div>
            )}

            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={() => setShowOtpModal(false)}
                className="rounded border border-slate-300 px-4 py-2 text-slate-700"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={submitStartJob}
                disabled={otpLoading || otpCode.length !== 4}
                className="rounded bg-cyan-600 px-4 py-2 text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                {otpLoading ? 'Verifying...' : 'Start Job'}
              </button>
            </div>
          </div>
        </div>
      )}

      {showCompleteModal && selectedCompleteJob && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
          <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 className="text-xl font-semibold">Complete Job</h3>
            <p className="mt-2 text-sm text-slate-600">
              Confirm that the job is finished and the customer has approved the work.
            </p>
            <div className="mt-4 rounded-xl bg-slate-50 p-4">
              <p className="text-sm font-semibold text-slate-900">{selectedCompleteJob.service_type_name}</p>
              <p className="mt-1 text-sm text-slate-600">
                {selectedCompleteJob.landmark ?? 'No landmark'}, {selectedCompleteJob.tole_name ?? selectedCompleteJob.ward_number}
              </p>
              <p className="mt-1 text-sm text-slate-500">Current status: {selectedCompleteJob.workflow_status.replace('_', ' ')}</p>
            </div>

            {error && (
              <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-900">
                {error}
              </div>
            )}

            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={() => setShowCompleteModal(false)}
                className="rounded border border-slate-300 px-4 py-2 text-slate-700"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={submitCompleteJob}
                disabled={loading}
                className="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                {loading ? 'Completing...' : 'Confirm Complete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PlumberDashboard;
