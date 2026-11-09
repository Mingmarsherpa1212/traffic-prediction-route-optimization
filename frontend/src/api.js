// API base URL — PHP backend running on XAMPP Apache
const API_BASE = "http://localhost/traffic_prediction/backend/api";

// All fetch calls that involve cookies (auth) must use credentials: 'include'
const CREDS = { credentials: "include" };

// ── Locations ─────────────────────────────────────────────────────────────────
export async function fetchLocations() {
  const res = await fetch(`${API_BASE}/locations.php`);
  if (!res.ok) throw new Error("Failed to load locations.");
  return res.json();
}

// ── Route + Traffic Prediction ────────────────────────────────────────────────
export async function fetchRoute({ start, destination, day, time_period, weather }) {
  const params = new URLSearchParams({ start, destination, day, time_period, weather });
  const res = await fetch(`${API_BASE}/route.php?${params}`);
  if (!res.ok) {
    const err = await res.json();
    throw new Error(err.message || "Route calculation failed.");
  }
  return res.json();
}

// ── Prediction History ────────────────────────────────────────────────────────
export async function fetchHistory(limit = 20) {
  const res = await fetch(`${API_BASE}/history.php?limit=${limit}`, CREDS);
  if (!res.ok) throw new Error("Failed to load prediction history.");
  return res.json();
}

export async function savePrediction(data) {
  const res = await fetch(`${API_BASE}/history.php`, {
    ...CREDS,
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify(data),
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    // 401 means not logged in — not a crash, just skip saving
    if (res.status === 401) return { success: false, notLoggedIn: true };
    console.warn("[TrafficPrediction] savePrediction failed:", err.message);
  }
  return res.json().catch(() => ({}));
}

// ── Authentication ────────────────────────────────────────────────────────────

/** Check if the user is currently logged in (session check). */
export async function fetchMe() {
  const res = await fetch(`${API_BASE}/auth/me.php`, CREDS);
  if (!res.ok) return { logged_in: false };
  return res.json();
}

/** Register a new account. */
export async function register({ username, email, password }) {
  const res = await fetch(`${API_BASE}/auth/register.php`, {
    ...CREDS,
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify({ username, email, password }),
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.message || "Registration failed.");
  return data;
}

/** Log in with email + password. */
export async function login({ email, password }) {
  const res = await fetch(`${API_BASE}/auth/login.php`, {
    ...CREDS,
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify({ email, password }),
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.message || "Login failed.");
  return data;
}

/** Log out the current user. */
export async function logout() {
  const res = await fetch(`${API_BASE}/auth/logout.php`, {
    ...CREDS,
    method: "POST",
  });
  return res.json();
}
