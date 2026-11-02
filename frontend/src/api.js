// API base URL — PHP backend running on XAMPP Apache
const API_BASE = "http://localhost/traffic_prediction/backend/api";

/**
 * Fetch all locations from the locations API.
 * Returns: [{ id, location_name, latitude, longitude }, ...]
 */
export async function fetchLocations() {
  const response = await fetch(`${API_BASE}/locations.php`);
  if (!response.ok) throw new Error("Failed to load locations.");
  return response.json();
}

/**
 * Get the optimal route between two locations.
 * Internally runs Decision Tree prediction + Dijkstra's algorithm.
 * Returns full route result including all_traffic_levels.
 */
export async function fetchRoute({ start, destination, day, time_period, weather }) {
  const params = new URLSearchParams({ start, destination, day, time_period, weather });
  const response = await fetch(`${API_BASE}/route.php?${params}`);
  if (!response.ok) {
    const err = await response.json();
    throw new Error(err.message || "Route calculation failed.");
  }
  return response.json();
}

/**
 * Fetch recent prediction history (newest first).
 * Returns: [{ id, location_name, day, time_period, weather, predicted_level, created_at }, ...]
 */
export async function fetchHistory(limit = 20) {
  const response = await fetch(`${API_BASE}/history.php?limit=${limit}`);
  if (!response.ok) throw new Error("Failed to load prediction history.");
  return response.json();
}

/**
 * Save a prediction record to the database.
 * Called automatically after each successful route+prediction.
 *
 * @param {object} data - { location_id, day, time_period, weather, predicted_level }
 */
export async function savePrediction(data) {
  const response = await fetch(`${API_BASE}/history.php`, {
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify(data),
  });
  // Non-blocking — log error but don't throw (UI should still show results)
  if (!response.ok) {
    console.warn("[TrafficPrediction] Could not save prediction to history:", await response.text());
  }
  return response.json();
}
