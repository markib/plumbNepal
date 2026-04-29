import React, { useState } from 'react';

interface AiResult {
  issue_type: string;
  urgency: string;
  estimated_price_min: number;
  estimated_price_max: number;
  recommended_service: string;
  confidence: number;
  summary: string;
}

interface ApiResponse {
  status: string;
  data: AiResult;
  message?: string;
}

interface AIRequestInputProps {
  onAnalysisComplete?: (result: AiResult) => void;
}

export const AIRequestInput: React.FC<AIRequestInputProps> = ({ onAnalysisComplete }) => {
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<AiResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleAnalyze = async () => {
    setLoading(true);
    setError(null);
    setResult(null);

    try {
      const response = await fetch('/api/v1/ai/diagnose', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ message }),
      });

      const data: ApiResponse = await response.json();

      if (!response.ok) {
        // Now 'data' is defined, so we can safely access data.message
        throw new Error(data.message || 'Failed to analyze issue.');
      }

      // Handle the successful data wrap { status: 'success', data: { ... } }
      const analysisResult = data.data;
      setResult(analysisResult);

      // Trigger callback to pre-fill the booking form if provided
      if (onAnalysisComplete) {
        onAnalysisComplete(analysisResult);
      }
    } catch (err: any) {
      console.error('AI Diagnosis Error:', err);
      setError(err.message || 'An unexpected error occurred. Please try again.');
    } finally {
      // Correctly reset loading state here
      setLoading(false);
    }
  };

  return (
    <div className="p-6 bg-white rounded-xl shadow-sm border border-slate-200">
      <h2 className="text-xl font-bold mb-2 text-slate-800">Plumbing AI Diagnosis</h2>
      <p className="text-sm text-slate-500 mb-4">
        Describe the issue and our local AI will estimate the repair cost.
      </p>

      <textarea
        className="w-full p-3 border rounded-lg mb-4 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
        rows={4}
        value={message}
        onChange={(e) => setMessage(e.target.value)}
        placeholder="Example: My kitchen sink is leaking from the joint and there's water on the floor..."
      />

      <button
        className="w-full sm:w-auto px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        onClick={handleAnalyze}
        disabled={loading || !message.trim()}
      >
        {loading && (
          <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        )}
        {loading ? 'Analyzing...' : 'Analyze Issue'}
      </button>

      {error && (
        <div className="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
          {error}
        </div>
      )}

      {result && (
        <div className="mt-6 p-5 bg-slate-50 border border-slate-200 rounded-xl animate-in fade-in duration-300">
          <div className="flex justify-between items-start mb-4">
            <h3 className="font-bold text-lg text-slate-900">Diagnosis Result</h3>
            <span className={`px-2 py-1 rounded text-xs font-bold uppercase ${result.urgency === 'High' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}`}>
              {result.urgency} Priority
            </span>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <p><strong>Issue Type:</strong> {result.issue_type}</p>
            <p><strong>Service:</strong> {result.recommended_service}</p>
            <p className="md:col-span-2">
              <strong>Estimated Price:</strong>
              <span className="text-lg font-bold ml-2">NPR {result.estimated_price_min} - {result.estimated_price_max}</span>
            </p>
          </div>

          <div className="mt-4 pt-4 border-t border-slate-200">
            <p className="text-slate-700 italic">"{result.summary}"</p>
            <p className="text-xs text-slate-400 mt-3">Confidence: {(result.confidence * 100).toFixed(0)}%</p>
          </div>
        </div>
      )}
    </div>
  );
};