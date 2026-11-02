import React from "react";

/**
 * ResultPanel Component
 *
 * Displays the Dijkstra route result and traffic prediction.
 *
 * Props:
 *  - result : the JSON response from route.php
 */

// Bootstrap badge colour class based on traffic level
function trafficBadgeClass(level) {
  switch (level) {
    case "Low":    return "bg-success";
    case "Medium": return "bg-warning text-dark";
    case "High":   return "bg-danger";
    default:       return "bg-secondary";
  }
}

// Hex colour for solid background tiles
function trafficColor(level) {
  switch (level) {
    case "Low":    return "#198754";
    case "Medium": return "#ffc107";
    case "High":   return "#dc3545";
    default:       return "#6c757d";
  }
}

export default function ResultPanel({ result }) {
  if (!result) return null;

  const destTraffic = result.all_traffic_levels?.[result.destination];

  return (
    <div className="mt-4">

      {/* ── Predicted Traffic Level for Destination ─────────────── */}
      <div className="card mb-3 shadow-sm">
        <div className="card-header fw-semibold">
          Predicted Traffic Level — {result.destination}
        </div>
        <div className="card-body text-center py-4">
          {destTraffic ? (
            <>
              <span
                className={`badge fs-3 px-4 py-3 ${trafficBadgeClass(destTraffic.traffic_level)}`}
              >
                {destTraffic.traffic_level}
              </span>
              <p className="text-muted mt-2 mb-0" style={{ fontSize: "0.85rem" }}>
                Traffic weight: ×{destTraffic.traffic_weight}&nbsp;|&nbsp;
                {result.day} · {result.time_period} · {result.weather}
              </p>
            </>
          ) : (
            <span className="text-muted">No prediction available</span>
          )}
        </div>
      </div>

      {/* ── Recommended Route ───────────────────────────────────── */}
      <div className="card mb-3 shadow-sm">
        <div className="card-header fw-semibold">
          Recommended Route&nbsp;
          <small className="fw-normal text-muted">(Dijkstra's Algorithm)</small>
        </div>
        <div className="card-body">

          {/* Coloured route badges with arrows */}
          <div className="d-flex flex-wrap align-items-center gap-1 mb-3">
            {result.route.map((loc, idx) => {
              const info  = result.all_traffic_levels?.[loc];
              const level = info?.traffic_level ?? "Unknown";
              return (
                <React.Fragment key={idx}>
                  <span className={`badge px-3 py-2 ${trafficBadgeClass(level)}`}>
                    {loc}
                  </span>
                  {idx < result.route.length - 1 && (
                    <span className="text-muted fw-bold">→</span>
                  )}
                </React.Fragment>
              );
            })}
          </div>

          {/* Summary tiles */}
          <div className="row text-center g-2">
            <div className="col-6 col-md-4">
              <div className="border rounded p-2 bg-light">
                <div className="fw-bold text-primary fs-5">{result.total_distance_km} km</div>
                <div className="text-muted" style={{ fontSize: "0.8rem" }}>Total Distance</div>
              </div>
            </div>
            <div className="col-6 col-md-4">
              <div className="border rounded p-2 bg-light">
                <div className="fw-bold text-secondary fs-5">{result.total_cost}</div>
                <div className="text-muted" style={{ fontSize: "0.8rem" }}>Weighted Cost</div>
              </div>
            </div>
            <div className="col-12 col-md-4">
              <div className="border rounded p-2 bg-light">
                <div className="fw-bold text-dark fs-5">{result.route.length} stops</div>
                <div className="text-muted" style={{ fontSize: "0.8rem" }}>Locations</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Step-by-Step Breakdown Table ────────────────────────── */}
      <div className="card mb-3 shadow-sm">
        <div className="card-header fw-semibold">Step-by-Step Breakdown</div>
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-sm table-hover mb-0">
              <thead className="table-light">
                <tr>
                  <th>#</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Distance</th>
                  <th>Traffic Level</th>
                  <th>Weight</th>
                  <th>Edge Cost</th>
                </tr>
              </thead>
              <tbody>
                {result.steps.map((step, idx) => (
                  <tr key={idx}>
                    <td className="text-muted">{idx + 1}</td>
                    <td>{step.from}</td>
                    <td>{step.to}</td>
                    <td>{step.distance_km} km</td>
                    <td>
                      <span className={`badge ${trafficBadgeClass(step.traffic_level)}`}>
                        {step.traffic_level}
                      </span>
                    </td>
                    <td>×{step.traffic_weight}</td>
                    <td className="fw-semibold">{step.edge_cost}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="table-light">
                <tr>
                  <td colSpan={3} className="fw-semibold text-end">Total</td>
                  <td className="fw-semibold">{result.total_distance_km} km</td>
                  <td colSpan={2}></td>
                  <td className="fw-semibold">{result.total_cost}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {/* ── All Locations Traffic Overview ──────────────────────── */}
      <div className="card mb-3 shadow-sm">
        <div className="card-header fw-semibold">
          All Location Traffic Levels&nbsp;
          <small className="fw-normal text-muted">
            ({result.day} · {result.time_period} · {result.weather})
          </small>
        </div>
        <div className="card-body">
          <div className="row g-2">
            {Object.entries(result.all_traffic_levels).map(([name, info]) => (
              <div key={name} className="col-6 col-md-4">
                <div
                  className="rounded p-2 text-center text-white"
                  style={{ backgroundColor: trafficColor(info.traffic_level) }}
                >
                  <div className="fw-semibold" style={{ fontSize: "0.9rem" }}>{name}</div>
                  <div style={{ fontSize: "0.8rem" }}>{info.traffic_level}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* ── Disclaimer ──────────────────────────────────────────── */}
      <div className="alert alert-light border text-muted small">
        ⚠️ <strong>Note:</strong> All traffic predictions are based on{" "}
        <strong>synthetic/demo data</strong> only. This is NOT a real-time
        traffic system. Route costs use academic prototype weights
        (Low = 1, Medium = 3, High = 6).
      </div>
    </div>
  );
}
