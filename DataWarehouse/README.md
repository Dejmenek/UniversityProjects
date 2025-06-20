# ✈️ Flight Data ETL and Reporting Pipeline

This project is an end-to-end ETL pipeline and reporting solution for airline flight data. It extracts, transforms, and loads flight information into a PostgreSQL database and generates insightful PDF reports with visualizations.

## ⚙️ Features

- Extracts raw flight data from `.csv` files.
- Cleans, normalizes, and validates data (handles inconsistent dates, missing values, etc.).
- Loads data into normalized PostgreSQL schema with foreign keys.
- Generates analytical SQL reports using `.sql` files.
- Outputs PDF reports with charts.

## 🧱 Requirements

- Python 3.9+
- PostgreSQL
- Required Python packages:
  - pandas
  - sqlalchemy
  - seaborn
  - psycopg2
  - matplotlib

## 🚀 Usage

### 1. Load Data

Ensure PostgreSQL is running and you have your credentials set in `constants.py`.
Then, run the ETL pipeline:

```bash
python ./etl/main.py
```

This will:

- Parse and validate the CSV
- Insert dimensions
- Load fact data

### 2. Generate Reports

Run reporting script:

```bash
python ./reports/generate_reports.py
```

This will:

- Execute SQL queries from the `/sql` folder
- Visualize the data using matplotlib
- Save the final result as `podsumowanie.pdf`

## 📊 Sample Reports

Reports include

- Top 10 most popular flight routes
![image](https://github.com/user-attachments/assets/a5969bac-d603-461f-8755-1cdae667ecef)

- Monthly number of flights
![image](https://github.com/user-attachments/assets/4bfa2fa1-8c55-4c12-8372-ca511d4f231f)

- Ranking of airports by total traffic
![image](https://github.com/user-attachments/assets/4b06c100-26c7-4c28-8849-66ff7fff3ca5)

