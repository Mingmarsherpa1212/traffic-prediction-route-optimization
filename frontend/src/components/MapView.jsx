import { useEffect, useRef } from "react";
import {
  MapContainer,
  TileLayer,
  CircleMarker,
  Polyline,
  Popup,
  useMap,
} from "react-leaflet";
import "leaflet/dist/leaflet.css";

/**
 * MapView Component — Phase 6
 *
 * Displays an interactive Leaflet / OpenStreetMap map showing:
 *   • Coloured circle markers for every location
 *       Low    → Green  (#198754)
 *       Medium → Yellow (#ffc107)
 *       High   → Red    (#dc3545)
 *       No prediction yet → Grey (#6c757d)
 *   • A blue polyline for the Dijkstra-recommended route
 *
 * IMPORTANT: Marker colours are based on SYNTHETIC/DEMO data predictions.
 * They do NOT represent real-time or live traffic conditions.
 *
 * Props:
 *   locations  – array of { id, location_name, latitude, longitude }
 *   result     – route API response (null before first prediction)
 */

// ── Traffic colour map ────────────────────────────────────────────────────────
const TRAFFIC_COLORS = {
  Low:     "#198754",  // Bootstrap green
  Medium:  "#ffc107",  // Bootstrap yellow
  High:    "#dc3545",  // Bootstrap red
  Unknown: "#6c757d",  // Bootstrap grey — before prediction
};

function getColor(locationName, allTrafficLevels) {
  const info = allTrafficLevels?.[locationName];
  return TRAFFIC_COLORS[info?.traffic_level] ?? TRAFFIC_COLORS.Unknown;
}

// ── Sub-component: auto-fit map bounds when route changes ─────────────────────
function MapBoundsFitter({ coords }) {
  const map = useMap();

  useEffect(() => {
    if (coords && coords.length > 1) {
      map.fitBounds(coords, { padding: [40, 40], maxZoom: 15 });
    }
  }, [coords, map]);

  return null;
}

// ── Main MapView component ────────────────────────────────────────────────────
export default function MapView({ locations, result }) {
  // Map centre — Chabahil area (roughly central among all 6 locations)
  const defaultCenter = [27.715, 85.33];
  const defaultZoom   = 13;

  const allTraffic = result?.all_traffic_levels ?? null;

  // Build route polyline coordinates from the route array
  const routeCoords = [];
  if (result?.route && allTraffic) {
    result.route.forEach((locName) => {
      const info = allTraffic[locName];
      if (info?.latitude && info?.longitude) {
        routeCoords.push([parseFloat(info.latitude), parseFloat(info.longitude)]);
      }
    });
  }

  return (
    <div className="map-wrapper">

      {/* Legend */}
      <div className="map-legend d-flex flex-wrap gap-3 mb-2">
        {[
          { label: "Low Traffic",        color: TRAFFIC_COLORS.Low     },
          { label: "Medium Traffic",     color: TRAFFIC_COLORS.Medium  },
          { label: "High Traffic",       color: TRAFFIC_COLORS.High    },
          { label: "Not yet predicted",  color: TRAFFIC_COLORS.Unknown },
        ].map(({ label, color }) => (
          <span key={label} className="d-flex align-items-center gap-1" style={{ fontSize: "0.8rem" }}>
            <span
              style={{
                display: "inline-block",
                width: 14, height: 14,
                borderRadius: "50%",
                backgroundColor: color,
                border: "1px solid #dee2e6",
              }}
            />
            {label}
          </span>
        ))}
        {routeCoords.length > 1 && (
          <span className="d-flex align-items-center gap-1" style={{ fontSize: "0.8rem" }}>
            <span style={{
              display: "inline-block", width: 20, height: 4,
              backgroundColor: "#0d6efd", borderRadius: 2,
            }} />
            Dijkstra Route
          </span>
        )}
      </div>

      {/* Map */}
      <MapContainer
        center={defaultCenter}
        zoom={defaultZoom}
        style={{ height: "420px", width: "100%", borderRadius: "8px", zIndex: 0 }}
        scrollWheelZoom={true}
      >
        {/* OpenStreetMap tile layer */}
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        {/* Auto-fit map to route bounds when result changes */}
        {routeCoords.length > 1 && <MapBoundsFitter coords={routeCoords} />}

        {/* Location markers — coloured by predicted traffic level */}
        {locations.map((loc) => {
          const lat   = parseFloat(loc.latitude);
          const lng   = parseFloat(loc.longitude);
          const color = getColor(loc.location_name, allTraffic);
          const info  = allTraffic?.[loc.location_name];
          const level = info?.traffic_level ?? "Not predicted yet";
          const weight = info?.traffic_weight;

          // Is this location on the Dijkstra route?
          const onRoute = result?.route?.includes(loc.location_name) ?? false;

          return (
            <CircleMarker
              key={loc.id}
              center={[lat, lng]}
              radius={onRoute ? 14 : 10}
              pathOptions={{
                fillColor:   color,
                color:        onRoute ? "#fff" : "#fff",
                weight:       onRoute ? 3 : 2,
                fillOpacity:  0.92,
                opacity:      1,
              }}
            >
              <Popup>
                <div style={{ minWidth: "140px" }}>
                  <strong style={{ fontSize: "0.95rem" }}>{loc.location_name}</strong>
                  <hr style={{ margin: "4px 0" }} />
                  <div>
                    Traffic Level:{" "}
                    <span style={{ color, fontWeight: 700 }}>{level}</span>
                  </div>
                  {weight !== undefined && (
                    <div style={{ fontSize: "0.8rem", color: "#666" }}>
                      Cost weight: ×{weight}
                    </div>
                  )}
                  {onRoute && (
                    <div
                      style={{
                        marginTop: "4px",
                        fontSize: "0.78rem",
                        backgroundColor: "#0d6efd",
                        color: "#fff",
                        borderRadius: "4px",
                        padding: "2px 6px",
                        display: "inline-block",
                      }}
                    >
                      ✓ On recommended route
                    </div>
                  )}
                  <div style={{ fontSize: "0.72rem", color: "#999", marginTop: "4px" }}>
                    Synthetic/demo data only
                  </div>
                </div>
              </Popup>
            </CircleMarker>
          );
        })}

        {/* Dijkstra route polyline */}
        {routeCoords.length > 1 && (
          <Polyline
            positions={routeCoords}
            pathOptions={{
              color:     "#0d6efd",   // Bootstrap blue
              weight:    6,
              opacity:   0.85,
              dashArray: null,        // solid line
              lineCap:   "round",
              lineJoin:  "round",
            }}
          />
        )}
      </MapContainer>

      {/* Disclaimer */}
      <p className="text-muted mt-1 mb-0" style={{ fontSize: "0.75rem" }}>
        ⚠️ Marker colours represent <strong>synthetic/demo</strong> traffic
        predictions only — not real-time or live traffic conditions.
        Map tiles © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a> contributors.
      </p>
    </div>
  );
}
