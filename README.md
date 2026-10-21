# Traffic Level Prediction System
**BCA 6th Semester Academic Project**

A web-based traffic prediction system that combines a **Decision Tree classifier** (Rubix ML) with **Dijkstra's shortest path algorithm** to predict traffic levels and recommend optimal routes across six locations in Kathmandu.

---

## Technology Stack

| Layer        | Technology                          |
|--------------|-------------------------------------|
| Frontend     | React + JavaScript + CSS/Bootstrap  |
| Backend      | PHP 8.1                             |
| Database     | MariaDB 10.4 (XAMPP)                |
| ML Library   | Rubix ML (Decision Tree)            |
| Algorithm    | Dijkstra's Shortest Path            |
| Map          | Leaflet.js + OpenStreetMap          |
| Environment  | XAMPP (localhost)                   |

---

## Locations

| ID | Name        | Latitude    | Longitude   |
|----|-------------|-------------|-------------|
| 1  | Kumarigal   | 27.7167000  | 85.3436000  |
| 2  | Chabahil    | 27.7172000  | 85.3478000  |
| 3  | Boudha      | 27.7215000  | 85.3620000  |
| 4  | Jorpati     | 27.7240000  | 85.3710000  |
| 5  | Naya Basti  | 27.7290000  | 85.3545000  |
| 6  | Kalanki     | 27.6930000  | 85.2810000  |

---

## Road Graph (road_segments table)

| Segment | From       | To          | Distance (km) |
|---------|------------|-------------|----------------|
| R001    | Kumarigal  | Chabahil    | 0.70           |
| R002    | Chabahil   | Boudha      | 1.40           |
| R003    | Boudha     | Jorpati     | 1.20           |
| R004    | Chabahil   | Naya Basti  | 1.80           |
| R005    | Naya Basti | Boudha      | 1.50           |
| R006    | Naya Basti | Jorpati     | 2.00           |
| R007    | Chabahil   | Kalanki     | 7.00           |
| R008    | Naya Basti | Kalanki     | 7.50           |

---

## Traffic Cost Model

| Traffic Level | Cost Multiplier |
|---------------|----------------|
| Low           | 1              |
| Medium        | 3              |
| High          | 6              |

**Dijkstra edge weight formula:**
```
edge_cost = distance_km × traffic_cost_multiplier
```

---

## System Flow

1. User selects: Start Location, Destination, Day, Time Period, Weather
2. Decision Tree predicts traffic level for **each location** (Low / Medium / High)
3. Traffic color is displayed on map (Green / Yellow / Red)
4. Dijkstra calculates the lowest-cost route using traffic-adjusted weights
5. Recommended route is drawn on the map

> ⚠️ **Note**: Traffic data is synthetic/demo data. This is NOT a real-time navigation system.

---

## Project Structure

```
traffic_prediction/
├── backend/
│   ├── config/
│   │   ├── database.php        # PDO connection (Phase 1)
│   │   └── helpers.php         # API response helpers
│   ├── api/
│   │   ├── test_connection.php # Phase 1 connection test
│   │   ├── locations.php       # Phase 2: Locations API
│   │   ├── predict.php         # Phase 3: ML prediction API
│   │   ├── route.php           # Phase 4: Dijkstra route API
│   │   ├── history.php         # Phase 7: Prediction history
│   │   ├── register.php        # Phase 8: User registration
│   │   └── login.php           # Phase 8: User login
│   ├── algorithm/
│   │   └── dijkstra.php        # Phase 4: Dijkstra's algorithm
│   └── ml/
│       ├── train_model.php     # Phase 3: Train Decision Tree
│       └── predict.php         # Phase 3: Run prediction (internal)
├── database/
│   ├── 001_create_users_table.sql
│   └── run_migrations.php
├── data/                       # Trained ML model storage (.rbx file)
├── frontend/                   # React app
│   └── src/
│       ├── components/
│       └── pages/
└── README.md
```

---

## Development Phases

| Phase | Status | Description                                |
|-------|--------|--------------------------------------------|
| 1     | ✅ Done | PHP PDO database connection                |
| 2     | ⏳      | Locations API                              |
| 3     | ⏳      | Decision Tree ML (Rubix ML)                |
| 4     | ⏳      | Dijkstra's algorithm + Route API           |
| 5     | ⏳      | React frontend                             |
| 6     | ⏳      | Leaflet map integration                    |
| 7     | ⏳      | Prediction history                         |
| 8     | ⏳      | Login/Register (users table)               |
| 9     | ⏳      | Full integration testing                   |

---

## Local Setup

1. Start XAMPP (Apache + MySQL)
2. Ensure database `traffic_prediction` exists with tables
3. Run migration: http://localhost/traffic_prediction/database/run_migrations.php
4. Test connection: http://localhost/traffic_prediction/backend/api/test_connection.php
5. Install Rubix ML: `composer require rubix/ml` inside `backend/ml/`
6. Train model: http://localhost/traffic_prediction/backend/ml/train_model.php
7. Install React frontend: `cd frontend && npm install && npm run dev`

