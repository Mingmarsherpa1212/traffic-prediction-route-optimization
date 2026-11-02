/**
 * PredictionHistory Component — Phase 7
 *
 * Displays recent predictions from the `predictions` table.
 * Each row shows: location, day, time period, weather,
 * predicted traffic level (colour-coded badge), and timestamp.
 *
 * Props:
 *  history  – array of prediction rows from history.php
 *  loading  – bool, shows spinner while fetching
 *  onClear  – optional callback when user clears results (future use)
 */

function trafficBadgeClass(level) {
  switch (level) {
    case "Low":    return "bg-success";
    case "Medium": return "bg-warning text-dark";
    case "High":   return "bg-danger";
    default:       return "bg-secondary";
  }
}

// Format "2024-01-15 10:30:00" → "Jan 15, 10:30 AM"
function formatDate(dateStr) {
  if (!dateStr) return "—";
  const d = new Date(dateStr.replace(" ", "T")); // make ISO-compatible
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleString("en-US", {
    month: "short",
    day:   "numeric",
    hour:  "2-digit",
    minute:"2-digit",
  });
}

export default function PredictionHistory({ history, loading }) {
  return (
    <div className="card shadow-sm mt-4">
      <div className="card-header bg-white d-flex align-items-center justify-content-between">
        <span className="fw-semibold">🕓 Prediction History</span>
        <small className="text-muted fw-normal">
          {loading
            ? "Loading..."
            : history.length > 0
            ? `${history.length} recent prediction${history.length !== 1 ? "s" : ""}`
            : "No predictions yet"}
        </small>
      </div>

      <div className="card-body p-0">
        {loading ? (
          <div className="text-center py-4 text-muted">
            <div className="spinner-border spinner-border-sm me-2" role="status" />
            Loading history...
          </div>
        ) : history.length === 0 ? (
          <div className="text-center py-4 text-muted">
            <div style={{ fontSize: "2rem" }}>📋</div>
            <p className="mb-0 mt-1">
              No predictions saved yet. Make your first prediction above!
            </p>
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table table-sm table-hover mb-0">
              <thead className="table-light">
                <tr>
                  <th>#</th>
                  <th>Location</th>
                  <th>Day</th>
                  <th>Time Period</th>
                  <th>Weather</th>
                  <th>Predicted Level</th>
                  <th>When</th>
                </tr>
              </thead>
              <tbody>
                {history.map((row, idx) => (
                  <tr key={row.id}>
                    <td className="text-muted" style={{ fontSize: "0.8rem" }}>
                      {idx + 1}
                    </td>
                    <td className="fw-semibold">{row.location_name}</td>
                    <td>{row.day}</td>
                    <td>{row.time_period}</td>
                    <td>{row.weather}</td>
                    <td>
                      <span className={`badge ${trafficBadgeClass(row.predicted_level)}`}>
                        {row.predicted_level}
                      </span>
                    </td>
                    <td className="text-muted" style={{ fontSize: "0.8rem" }}>
                      {formatDate(row.created_at)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {history.length > 0 && (
        <div className="card-footer bg-white text-muted" style={{ fontSize: "0.75rem" }}>
          Showing the {history.length} most recent predictions.
          All predictions are based on synthetic/demo data only.
        </div>
      )}
    </div>
  );
}
