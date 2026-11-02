import { useState, useEffect } from "react";
import "bootstrap/dist/css/bootstrap.min.css";
import "./App.css";

import PredictionForm    from "./components/PredictionForm";
import ResultPanel       from "./components/ResultPanel";
import MapView           from "./components/MapView";
import PredictionHistory from "./components/PredictionHistory";
import { fetchLocations, fetchRoute, fetchHistory, savePrediction } from "./api";

/**
 * App Component — Traffic Level Prediction System
 * BCA 6th Semester Academic Project
 *
 * Flow:
 *  1. On mount: load locations (dropdowns + map) and prediction history.
 *  2. User fills the form and clicks "Predict Traffic & Find Route".
 *  3. Calls route.php → Decision Tree prediction + Dijkstra route.
 *  4. Saves the destination's prediction to the predictions table.
 *  5. Refreshes prediction history table.
 *  6. Map updates with coloured markers + route polyline.
 */
export default function App() {
  // ── Locations (for dropdowns and map) ──────────────────────────────────────
  const [locations,      setLocations]      = useState([]);
  const [locationsError, setLocationsError] = useState("");

  // ── Form state ──────────────────────────────────────────────────────────────
  const [formData, setFormData] = useState({
    start:       "",
    destination: "",
    day:         "",
    time_period: "",
    weather:     "",
  });

  // ── Results ─────────────────────────────────────────────────────────────────
  const [loading, setLoading] = useState(false);
  const [result,  setResult]  = useState(null);
  const [error,   setError]   = useState("");

  // ── Prediction history ──────────────────────────────────────────────────────
  const [history,        setHistory]        = useState([]);
  const [historyLoading, setHistoryLoading] = useState(true);

  // ── Load locations on mount ─────────────────────────────────────────────────
  useEffect(() => {
    fetchLocations()
      .then(setLocations)
      .catch(() =>
        setLocationsError("Could not load locations. Make sure XAMPP Apache is running.")
      );
  }, []);

  // ── Load prediction history on mount ────────────────────────────────────────
  useEffect(() => {
    loadHistory();
  }, []);

  async function loadHistory() {
    setHistoryLoading(true);
    try {
      const data = await fetchHistory(20);
      setHistory(data);
    } catch {
      // History is non-critical — fail silently
    } finally {
      setHistoryLoading(false);
    }
  }

  // ── Form field changes ──────────────────────────────────────────────────────
  function handleChange(e) {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  }

  // ── Form submit ─────────────────────────────────────────────────────────────
  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setResult(null);

    if (formData.start === formData.destination) {
      setError("Start location and destination cannot be the same.");
      return;
    }

    setLoading(true);
    try {
      // Step 1: Get route + traffic predictions
      const data = await fetchRoute(formData);
      setResult(data);

      // Step 2: Save the destination's prediction to the predictions table.
      // We look up the destination's location_id from the locations array.
      const destInfo  = data.all_traffic_levels?.[formData.destination];
      const destLoc   = locations.find((l) => l.location_name === formData.destination);

      if (destLoc && destInfo) {
        await savePrediction({
          location_id:     destLoc.id,
          day:             formData.day,
          time_period:     formData.time_period,
          weather:         formData.weather,
          predicted_level: destInfo.traffic_level,
        });
      }

      // Step 3: Refresh history table
      await loadHistory();

    } catch (err) {
      setError(err.message || "Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  // ── Render ──────────────────────────────────────────────────────────────────
  return (
    <div className="min-vh-100 bg-light">

      {/* ── Header ─────────────────────────────────────────────────────────── */}
      <header className="bg-primary text-white py-3 shadow-sm">
        <div className="container">
          <h1 className="h4 mb-0 fw-bold">
            🚦 Traffic Level Prediction &amp; Route Optimization
          </h1>
          <p className="mb-0 mt-1" style={{ fontSize: "0.85rem", opacity: 0.85 }}>
            BCA 6th Semester Academic Project &nbsp;·&nbsp;
            Decision Tree + Dijkstra&apos;s Algorithm &nbsp;·&nbsp;
            Synthetic/demo data only
          </p>
        </div>
      </header>

      {/* ── Main ───────────────────────────────────────────────────────────── */}
      <main className="container py-4">

        {locationsError && (
          <div className="alert alert-danger">{locationsError}</div>
        )}

        {/* Form (left) + Map (right) */}
        <div className="row g-4">
          <div className="col-lg-4">
            <div className="card shadow-sm h-100">
              <div className="card-header bg-white fw-semibold">
                Enter Journey Details
              </div>
              <div className="card-body">
                <PredictionForm
                  locations={locations}
                  formData={formData}
                  onChange={handleChange}
                  onSubmit={handleSubmit}
                  loading={loading}
                />
              </div>
            </div>
          </div>

          <div className="col-lg-8">
            <div className="card shadow-sm">
              <div className="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
                <span>📍 Interactive Map</span>
                {result ? (
                  <small className="text-muted fw-normal">
                    {result.start} → {result.destination}
                  </small>
                ) : (
                  <small className="text-muted fw-normal">
                    Submit the form to see traffic colours and route
                  </small>
                )}
              </div>
              <div className="card-body p-2">
                <MapView locations={locations} result={result} />
              </div>
            </div>
          </div>
        </div>

        {/* Error */}
        {error && (
          <div className="alert alert-danger mt-3 d-flex align-items-center">
            <span className="me-2">⚠️</span> {error}
          </div>
        )}

        {/* Results */}
        <ResultPanel result={result} />

        {/* Prediction History */}
        <PredictionHistory history={history} loading={historyLoading} />

      </main>

      {/* ── Footer ─────────────────────────────────────────────────────────── */}
      <footer className="bg-white border-top py-3 mt-4">
        <div className="container text-center text-muted small">
          Traffic Level Prediction System &nbsp;·&nbsp; BCA 6th Semester
          &nbsp;·&nbsp; Rubix ML Decision Tree + Dijkstra&apos;s Algorithm
          &nbsp;·&nbsp; Map © OpenStreetMap contributors
        </div>
      </footer>

    </div>
  );
}
