import { useState, useEffect } from "react";
import "bootstrap/dist/css/bootstrap.min.css";
import "./App.css";

import PredictionForm    from "./components/PredictionForm";
import ResultPanel       from "./components/ResultPanel";
import MapView           from "./components/MapView";
import PredictionHistory from "./components/PredictionHistory";
import AuthForm          from "./components/AuthForm";
import {
  fetchLocations, fetchRoute,
  fetchHistory,   savePrediction,
  fetchMe,        logout,
} from "./api";

/**
 * App Component — Traffic Level Prediction System
 * BCA 6th Semester Academic Project
 *
 * Phase 8 additions:
 *  - Session check on mount (fetchMe)
 *  - Login / Register form (AuthForm)
 *  - User info + Logout in header
 *  - History saving only for logged-in users
 */
export default function App() {
  // ── Auth state ──────────────────────────────────────────────────────────────
  const [user,        setUser]        = useState(null);   // null = not logged in
  const [authChecked, setAuthChecked] = useState(false);  // hide UI until check done
  const [showAuth,    setShowAuth]    = useState(false);  // toggle auth form panel

  // ── Locations ───────────────────────────────────────────────────────────────
  const [locations,      setLocations]      = useState([]);
  const [locationsError, setLocationsError] = useState("");

  // ── Form ────────────────────────────────────────────────────────────────────
  const [formData, setFormData] = useState({
    start: "", destination: "", day: "", time_period: "", weather: "",
  });
  const [loading, setLoading] = useState(false);
  const [result,  setResult]  = useState(null);
  const [error,   setError]   = useState("");

  // ── History ─────────────────────────────────────────────────────────────────
  const [history,        setHistory]        = useState([]);
  const [historyLoading, setHistoryLoading] = useState(true);

  // ── On mount: check session + load locations + load history ─────────────────
  useEffect(() => {
    // Check if user is already logged in (PHP session)
    fetchMe()
      .then((data) => {
        if (data.logged_in) setUser(data.user);
      })
      .finally(() => setAuthChecked(true));

    // Load locations for dropdowns + map
    fetchLocations()
      .then(setLocations)
      .catch(() => setLocationsError("Could not load locations. Make sure XAMPP Apache is running."));

    // Load prediction history
    loadHistory();
  }, []);

  async function loadHistory() {
    setHistoryLoading(true);
    try {
      const data = await fetchHistory(20);
      setHistory(data);
    } catch {
      // non-critical — fail silently
    } finally {
      setHistoryLoading(false);
    }
  }

  // ── Auth handlers ───────────────────────────────────────────────────────────
  function handleAuthSuccess(loggedInUser) {
    setUser(loggedInUser);
    setShowAuth(false);
    loadHistory(); // refresh history after login
  }

  async function handleLogout() {
    await logout();
    setUser(null);
    setShowAuth(false);
  }

  // ── Form handlers ───────────────────────────────────────────────────────────
  function handleChange(e) {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  }

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
      // Get route + traffic predictions
      const data = await fetchRoute(formData);
      setResult(data);

      // Save prediction to DB only if user is logged in
      if (user) {
        const destInfo = data.all_traffic_levels?.[formData.destination];
        const destLoc  = locations.find((l) => l.location_name === formData.destination);
        if (destLoc && destInfo) {
          await savePrediction({
            location_id:     destLoc.id,
            day:             formData.day,
            time_period:     formData.time_period,
            weather:         formData.weather,
            predicted_level: destInfo.traffic_level,
          });
          loadHistory(); // refresh history
        }
      }
    } catch (err) {
      setError(err.message || "Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  // Don't render anything until session check is complete
  if (!authChecked) {
    return (
      <div className="d-flex justify-content-center align-items-center min-vh-100">
        <div className="text-center text-muted">
          <div className="spinner-border mb-3" role="status" />
          <p>Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-vh-100 bg-light">

      {/* ── Header ─────────────────────────────────────────────────────────── */}
      <header className="bg-primary text-white py-3 shadow-sm">
        <div className="container d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h1 className="h4 mb-0 fw-bold">
              🚦 Traffic Level Prediction &amp; Route Optimization
            </h1>
            <p className="mb-0 mt-1" style={{ fontSize: "0.82rem", opacity: 0.85 }}>
              BCA 6th Semester · Decision Tree + Dijkstra · Synthetic/demo data only
            </p>
          </div>

          {/* Auth area in header */}
          <div className="d-flex align-items-center gap-2">
            {user ? (
              <>
                <span className="text-white-50 small d-none d-sm-inline">
                  👤 {user.username}
                </span>
                <button
                  className="btn btn-outline-light btn-sm"
                  onClick={handleLogout}
                >
                  Logout
                </button>
              </>
            ) : (
              <button
                className="btn btn-outline-light btn-sm"
                onClick={() => setShowAuth((v) => !v)}
              >
                {showAuth ? "✕ Close" : "Login / Register"}
              </button>
            )}
          </div>
        </div>
      </header>

      {/* ── Auth panel (shown when not logged in and Login button clicked) ─── */}
      {!user && showAuth && (
        <div className="bg-white border-bottom py-4">
          <div className="container">
            <AuthForm onAuthSuccess={handleAuthSuccess} />
          </div>
        </div>
      )}

      {/* ── Main ───────────────────────────────────────────────────────────── */}
      <main className="container py-4">

        {locationsError && (
          <div className="alert alert-danger">{locationsError}</div>
        )}

        {/* Form + Map side-by-side */}
        <div className="row g-4">

          {/* LEFT — Form */}
          <div className="col-lg-4">
            <div className="card shadow-sm h-100">
              <div className="card-header bg-white fw-semibold">Enter Journey Details</div>
              <div className="card-body">
                <PredictionForm
                  locations={locations}
                  formData={formData}
                  onChange={handleChange}
                  onSubmit={handleSubmit}
                  loading={loading}
                />
                {/* Nudge to login if not logged in */}
                {!user && (
                  <p className="text-muted small mt-3 mb-0 text-center">
                    💡{" "}
                    <button
                      className="btn btn-link btn-sm p-0"
                      onClick={() => setShowAuth(true)}
                    >
                      Log in
                    </button>{" "}
                    to save predictions to history.
                  </p>
                )}
              </div>
            </div>
          </div>

          {/* RIGHT — Map */}
          <div className="col-lg-8">
            <div className="card shadow-sm">
              <div className="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
                <span>📍 Interactive Map</span>
                {result ? (
                  <small className="text-muted fw-normal">{result.start} → {result.destination}</small>
                ) : (
                  <small className="text-muted fw-normal">Submit form to see traffic colours and route</small>
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

        {/* Route + Traffic results */}
        <ResultPanel result={result} />

        {/* History */}
        <PredictionHistory history={history} loading={historyLoading} />

        {/* Login nudge below history (only if not logged in and there are results) */}
        {!user && result && (
          <div className="alert alert-info mt-2 small">
            💾 Your prediction was not saved.{" "}
            <button
              className="btn btn-link btn-sm p-0 align-baseline"
              onClick={() => { setShowAuth(true); window.scrollTo({ top: 0, behavior: "smooth" }); }}
            >
              Log in or register
            </button>{" "}
            to save your prediction history.
          </div>
        )}

      </main>

      {/* ── Footer ─────────────────────────────────────────────────────────── */}
      <footer className="bg-white border-top py-3 mt-4">
        <div className="container text-center text-muted small">
          Traffic Level Prediction System · BCA 6th Semester ·
          Rubix ML Decision Tree + Dijkstra · Map © OpenStreetMap contributors
        </div>
      </footer>

    </div>
  );
}
