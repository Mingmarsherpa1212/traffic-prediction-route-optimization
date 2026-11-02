import React from "react";

/**
 * PredictionForm Component
 *
 * Renders the input form for the traffic prediction system.
 * Location dropdowns are populated from the API (not hardcoded).
 *
 * Props:
 *  - locations      : array of location objects from API
 *  - formData       : { start, destination, day, time_period, weather }
 *  - onChange       : handler for input changes
 *  - onSubmit       : handler for form submission
 *  - loading        : bool — shows spinner on button when true
 */
export default function PredictionForm({
  locations,
  formData,
  onChange,
  onSubmit,
  loading,
}) {
  const days = [
    "Monday", "Tuesday", "Wednesday", "Thursday",
    "Friday", "Saturday", "Sunday",
  ];

  const timePeriods = ["Morning", "Afternoon", "Evening", "Night"];
  const weathers    = ["Sunny", "Cloudy", "Rainy"];

  return (
    <form onSubmit={onSubmit}>
      {/* Row 1: Start & Destination */}
      <div className="row g-3 mb-3">
        <div className="col-md-6">
          <label htmlFor="start" className="form-label fw-semibold">
            Start Location
          </label>
          <select
            id="start"
            name="start"
            className="form-select"
            value={formData.start}
            onChange={onChange}
            required
          >
            <option value="">-- Select Start --</option>
            {locations.map((loc) => (
              <option key={loc.id} value={loc.location_name}>
                {loc.location_name}
              </option>
            ))}
          </select>
        </div>

        <div className="col-md-6">
          <label htmlFor="destination" className="form-label fw-semibold">
            Destination
          </label>
          <select
            id="destination"
            name="destination"
            className="form-select"
            value={formData.destination}
            onChange={onChange}
            required
          >
            <option value="">-- Select Destination --</option>
            {locations.map((loc) => (
              <option key={loc.id} value={loc.location_name}>
                {loc.location_name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Row 2: Day, Time Period, Weather */}
      <div className="row g-3 mb-4">
        <div className="col-md-4">
          <label htmlFor="day" className="form-label fw-semibold">
            Day
          </label>
          <select
            id="day"
            name="day"
            className="form-select"
            value={formData.day}
            onChange={onChange}
            required
          >
            <option value="">-- Select Day --</option>
            {days.map((d) => (
              <option key={d} value={d}>{d}</option>
            ))}
          </select>
        </div>

        <div className="col-md-4">
          <label htmlFor="time_period" className="form-label fw-semibold">
            Time Period
          </label>
          <select
            id="time_period"
            name="time_period"
            className="form-select"
            value={formData.time_period}
            onChange={onChange}
            required
          >
            <option value="">-- Select Time --</option>
            {timePeriods.map((t) => (
              <option key={t} value={t}>{t}</option>
            ))}
          </select>
        </div>

        <div className="col-md-4">
          <label htmlFor="weather" className="form-label fw-semibold">
            Weather
          </label>
          <select
            id="weather"
            name="weather"
            className="form-select"
            value={formData.weather}
            onChange={onChange}
            required
          >
            <option value="">-- Select Weather --</option>
            {weathers.map((w) => (
              <option key={w} value={w}>{w}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Submit Button */}
      <div className="d-grid">
        <button
          type="submit"
          id="predict-btn"
          className="btn btn-primary btn-lg"
          disabled={loading}
        >
          {loading ? (
            <>
              <span
                className="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              />
              Calculating...
            </>
          ) : (
            "Predict Traffic & Find Route"
          )}
        </button>
      </div>
    </form>
  );
}
