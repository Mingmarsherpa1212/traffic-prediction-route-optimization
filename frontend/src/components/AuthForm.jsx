import { useState } from "react";
import { login, register } from "../api";

/**
 * AuthForm Component — Phase 8
 *
 * Displays a Login / Register tab card.
 * On success, calls onAuthSuccess(user) so App can update state.
 *
 * Props:
 *   onAuthSuccess(user) — called with the logged-in user object
 */
export default function AuthForm({ onAuthSuccess }) {
  const [tab,     setTab]     = useState("login");     // "login" | "register"
  const [loading, setLoading] = useState(false);
  const [error,   setError]   = useState("");
  const [success, setSuccess] = useState("");

  // Login form state
  const [loginData, setLoginData] = useState({ email: "", password: "" });

  // Register form state
  const [regData, setRegData] = useState({ username: "", email: "", password: "", confirm: "" });

  function handleTabSwitch(t) {
    setTab(t);
    setError("");
    setSuccess("");
  }

  // ── Login submit ────────────────────────────────────────────────────────────
  async function handleLogin(e) {
    e.preventDefault();
    setError(""); setSuccess("");
    setLoading(true);
    try {
      const data = await login(loginData);
      setSuccess(`Welcome back, ${data.user.username}!`);
      setTimeout(() => onAuthSuccess(data.user), 600);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  // ── Register submit ─────────────────────────────────────────────────────────
  async function handleRegister(e) {
    e.preventDefault();
    setError(""); setSuccess("");

    if (regData.password !== regData.confirm) {
      setError("Passwords do not match.");
      return;
    }

    setLoading(true);
    try {
      const data = await register({
        username: regData.username,
        email:    regData.email,
        password: regData.password,
      });
      setSuccess(`Account created! Welcome, ${data.user.username}!`);
      setTimeout(() => onAuthSuccess(data.user), 600);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card shadow-sm" style={{ maxWidth: 440, margin: "0 auto" }}>

      {/* Tabs */}
      <div className="card-header bg-white p-0">
        <ul className="nav nav-tabs card-header-tabs">
          <li className="nav-item">
            <button
              className={`nav-link ${tab === "login" ? "active" : ""}`}
              onClick={() => handleTabSwitch("login")}
            >
              Login
            </button>
          </li>
          <li className="nav-item">
            <button
              className={`nav-link ${tab === "register" ? "active" : ""}`}
              onClick={() => handleTabSwitch("register")}
            >
              Register
            </button>
          </li>
        </ul>
      </div>

      <div className="card-body">
        {/* Error / success messages */}
        {error   && <div className="alert alert-danger  py-2 small">{error}</div>}
        {success && <div className="alert alert-success py-2 small">{success}</div>}

        {/* ── Login form ── */}
        {tab === "login" && (
          <form onSubmit={handleLogin}>
            <div className="mb-3">
              <label className="form-label fw-semibold">Email</label>
              <input
                type="email"
                className="form-control"
                placeholder="you@example.com"
                value={loginData.email}
                onChange={(e) => setLoginData((p) => ({ ...p, email: e.target.value }))}
                required
                autoComplete="email"
              />
            </div>
            <div className="mb-3">
              <label className="form-label fw-semibold">Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="Your password"
                value={loginData.password}
                onChange={(e) => setLoginData((p) => ({ ...p, password: e.target.value }))}
                required
                autoComplete="current-password"
              />
            </div>
            <div className="d-grid">
              <button type="submit" className="btn btn-primary" disabled={loading}>
                {loading ? (
                  <><span className="spinner-border spinner-border-sm me-2" />Logging in...</>
                ) : "Login"}
              </button>
            </div>
            <p className="text-center text-muted mt-3 mb-0 small">
              Don&apos;t have an account?{" "}
              <button type="button" className="btn btn-link btn-sm p-0" onClick={() => handleTabSwitch("register")}>
                Register here
              </button>
            </p>
          </form>
        )}

        {/* ── Register form ── */}
        {tab === "register" && (
          <form onSubmit={handleRegister}>
            <div className="mb-3">
              <label className="form-label fw-semibold">Username</label>
              <input
                type="text"
                className="form-control"
                placeholder="At least 3 characters"
                value={regData.username}
                onChange={(e) => setRegData((p) => ({ ...p, username: e.target.value }))}
                required
                autoComplete="username"
              />
            </div>
            <div className="mb-3">
              <label className="form-label fw-semibold">Email</label>
              <input
                type="email"
                className="form-control"
                placeholder="you@example.com"
                value={regData.email}
                onChange={(e) => setRegData((p) => ({ ...p, email: e.target.value }))}
                required
                autoComplete="email"
              />
            </div>
            <div className="mb-3">
              <label className="form-label fw-semibold">Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="At least 6 characters"
                value={regData.password}
                onChange={(e) => setRegData((p) => ({ ...p, password: e.target.value }))}
                required
                autoComplete="new-password"
              />
            </div>
            <div className="mb-3">
              <label className="form-label fw-semibold">Confirm Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="Repeat your password"
                value={regData.confirm}
                onChange={(e) => setRegData((p) => ({ ...p, confirm: e.target.value }))}
                required
                autoComplete="new-password"
              />
            </div>
            <div className="d-grid">
              <button type="submit" className="btn btn-success" disabled={loading}>
                {loading ? (
                  <><span className="spinner-border spinner-border-sm me-2" />Creating account...</>
                ) : "Create Account"}
              </button>
            </div>
            <p className="text-center text-muted mt-3 mb-0 small">
              Already have an account?{" "}
              <button type="button" className="btn btn-link btn-sm p-0" onClick={() => handleTabSwitch("login")}>
                Login here
              </button>
            </p>
          </form>
        )}
      </div>
    </div>
  );
}
