# Traffic Level Prediction System
## BCA 6th Semester Academic Project

> **Important:** All traffic data and predictions are based on **synthetic/demo data only**.  
> This is NOT a real-time traffic system.

---

## Project Overview

A full-stack academic prototype that combines:
- **Decision Tree** (Rubix ML) to predict traffic levels (Low / Medium / High)
- **Dijkstra's Algorithm** to find the lowest traffic-adjusted-cost route
- **Leaflet + OpenStreetMap** to visualise locations and routes on a map
- **React + Bootstrap** for the frontend UI
- **PHP 8 + PDO** for the backend APIs
- **MySQL (MariaDB)** for persistent data storage

---

## Technology Stack

| Layer        | Technology                     |
|-------------|-------------------------------|
| Frontend    | React 18, Vite, Bootstrap 5    |
| Map         | Leaflet 1.9, OpenStreetMap     |
| Backend     | PHP 8.1, PDO prepared statements |
| ML Library  | Rubix ML 2.6 (ClassificationTree) |
| Database    | MariaDB 10.4 (XAMPP, port 3307) |
| Environment | XAMPP on Windows               |

---

## System Flow

```
User fills form
  Start Location + Destination + Day + Time Period + Weather
        ↓
React calls route.php (single API call)
        ↓
PHP: Decision Tree predicts traffic for ALL 6 locations
        ↓
Traffic Level → Cost Weight: Low=1  Medium=3  High=6
        ↓
PHP: Dijkstra's Algorithm builds graph with traffic-adjusted edge costs
     edge_cost = distance_km × traffic_weight_of_destination
        ↓
PHP returns: route[] + all_traffic_levels{} + steps[] + totals
        ↓
React → Leaflet map: coloured circle markers (green/yellow/red) + blue polyline
React → ResultPanel: traffic badge + route + step-by-step table
React → (if logged in) saves prediction to predictions table
```

---

## Project Structure

```
traffic_prediction/
├── backend/
│   ├── algorithm/
│   │   └── dijkstra.php          Pure PHP Dijkstra implementation
│   ├── api/
│   │   ├── auth/
│   │   │   ├── login.php         POST — login with password_verify()
│   │   │   ├── logout.php        POST — destroy session
│   │   │   ├── me.php            GET  — session check
│   │   │   └── register.php      POST — register with password_hash()
│   │   ├── history.php           GET/POST — prediction history
│   │   ├── locations.php         GET  — all locations with coordinates
│   │   ├── predict.php           GET  — single location prediction
│   │   ├── route.php             GET  — full route + all predictions
│   │   └── test_connection.php   GET  — DB connection test
│   ├── config/
│   │   ├── database.php          PDO connection (port 3307)
│   │   ├── helpers.php           Shared helpers
│   │   └── session.php           Session start + CORS for auth endpoints
│   └── ml/
│       └── train_model.php       Train Decision Tree and save model
├── data/
│   └── traffic_model.rbx         Saved trained model (Rubix ML)
├── database/
│   └── 001_create_users_table.sql
├── frontend/
│   └── src/
│       ├── api.js                All fetch calls to PHP backend
│       ├── App.jsx               Main app — state, layout, auth
│       ├── App.css               Custom styles + Leaflet z-index fix
│       └── components/
│           ├── AuthForm.jsx      Login / Register tab card
│           ├── MapView.jsx       Leaflet map with markers + polyline
│           ├── PredictionForm.jsx Form with dynamic location dropdowns
│           ├── PredictionHistory.jsx History table
│           └── ResultPanel.jsx   Traffic badge + route + steps table
└── README.md
```

---

## How to Run

### Prerequisites
- XAMPP with Apache and MySQL running
- Node.js 20+

### 1. Start XAMPP
Open XAMPP Control Panel → Start **Apache** → Start **MySQL**

### 2. Train the ML Model
Open in browser:
```
http://localhost/traffic_prediction/backend/ml/train_model.php
```
Run once — saves `data/traffic_model.rbx`.

### 3. Start the React Frontend
```bash
cd c:\xampp\htdocs\traffic_prediction\frontend
npm run dev
```

### 4. Open the App
```
http://localhost:5173
```

---

## API Endpoints

| Method | URL | Description | Auth |
|--------|-----|-------------|------|
| GET | `/api/locations.php` | All 6 locations with coordinates | Public |
| GET | `/api/predict.php?location=&day=&time_period=&weather=` | Predict traffic for one location | Public |
| GET | `/api/route.php?start=&destination=&day=&time_period=&weather=` | Full route + all predictions | Public |
| GET | `/api/history.php` | Recent 20 predictions | Public |
| POST | `/api/history.php` | Save a prediction | **Login required** |
| GET | `/api/auth/me.php` | Check session | — |
| POST | `/api/auth/register.php` | Create account | — |
| POST | `/api/auth/login.php` | Login | — |
| POST | `/api/auth/logout.php` | Logout | — |

---

## Prototype Traffic Cost Weights

| Traffic Level | Decision Tree Output | Dijkstra Cost Weight |
|--------------|---------------------|---------------------|
| Low          | Low                 | ×1                  |
| Medium       | Medium              | ×3                  |
| High         | High                | ×6                  |

**Edge cost formula:** `distance_km × traffic_weight_of_destination_node`

---

## Locations (Prototype Road Network)

| ID | Location    | Latitude  | Longitude |
|----|-------------|-----------|-----------|
| 1  | Kumarigal   | 27.7167   | 85.3436   |
| 2  | Chabahil    | 27.7172   | 85.3478   |
| 3  | Boudha      | 27.7215   | 85.3620   |
| 4  | Jorpati     | 27.7240   | 85.3710   |
| 5  | Naya Basti  | 27.7290   | 85.3545   |
| 6  | Kalanki     | 27.6930   | 85.2810   |

---

## Security Features (Phase 8)

- Passwords stored using `password_hash(PASSWORD_DEFAULT)` — bcrypt
- Login uses `password_verify()` — no plain-text comparison
- `session_regenerate_id(true)` on login — prevents session fixation
- HttpOnly session cookie — JavaScript cannot access it
- Duplicate email registration blocked with 409 Conflict
- History POST endpoint requires valid session (401 if not logged in)
- PDO prepared statements throughout — no SQL injection possible

---

## Academic Disclaimer

This project uses **504 synthetic/demo traffic records** for model training.  
Predictions do **not** represent real-world or live traffic conditions.  
The route costs use simplified prototype weights for academic demonstration only.
