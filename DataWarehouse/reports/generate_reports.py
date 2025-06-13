import os
import sys
import logging
from matplotlib import ticker
import pandas as pd
import sqlalchemy
import matplotlib.pyplot as plt
import seaborn as sns
from matplotlib.backends.backend_pdf import PdfPages

logging.basicConfig(level=logging.INFO,
                    format='%(asctime)s - %(levelname)s - %(message)s')

sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'etl')))
from constants import REPORT_QUERIES, SQL_FOLDER, REPORT_OUTPUT, TOP_ROUTES_QUERY_NAME, MONTHLY_FLIGHTS_QUERY_NAME, AIRPORT_TRAFFIC_QUERY_NAME, DB_URL

def load_query(file_name: str) -> str:
    with open(os.path.join(SQL_FOLDER, file_name), "r") as file:
        return file.read()

def plot_top_routes(df: pd.DataFrame, pdf: PdfPages) -> None:
    top_routes_df = df.copy()
    top_routes_df['route'] = df['departure_airport_code'] + '-' + df['arrival_airport_code']
    plt.figure(figsize=(12, 8))
    ax = sns.barplot(
        data=top_routes_df,
        y="route",
        x="total_flights",
        palette="Dark2"
    )
    plt.title("Top 10 Najpopularniejszych Tras Lotniczych", fontsize=16)
    plt.xlabel("Całkowita Liczba Lotów", fontsize=12)
    plt.ylabel("Trasa (Wylot-Przylot)", fontsize=12)
    ax.bar_label(ax.containers[0], fmt='{:,.0f}'.format, fontsize=10, padding=3)
    plt.tight_layout()
    pdf.savefig()
    plt.close()

def get_number_of_records(records_num: int) -> int:
    number_of_records = None

    while True:
        entered_number_of_records = input("Enter the number of records you want to see: ")

        try:
            number_of_records = int(entered_number_of_records)
            if number_of_records < 1 and number_of_records > records_num:
                logging.error(f"❌ The number you entered ({number_of_records}) is outside the valid range. Try again.")
                continue
            break
        except ValueError:
            logging.error("❌ The value you entered isn't a number. Try again.")
    
    return number_of_records

def get_year_range(years: list[int]) -> tuple[int, int]:
    start_year, end_year = None, None
    min_year, max_year = min(years), max(years)

    while True:
        entered_start_year = input(f"Enter the start year (range: {min_year}-{max_year}): ")

        try:
            start_year = int(entered_start_year)
            if start_year not in years:
                logging.error(f"❌ The year you entered ({start_year}) is outside the valid range. Try again.")
                continue
            break
        except ValueError:
            logging.error("❌ The value you entered isn't a number. Try again.")

    while True:
        entered_end_year = input(f"Enter the end year (range: {min_year}-{max_year}): ")

        try:
            end_year = int(entered_end_year)
            if end_year not in years:
                logging.error(f"❌ The year you entered ({end_year}) is outside the valid range. Try again.")
                continue

            if end_year < start_year:
                logging.error(f"❌ The end year ({end_year}) cannot be before the start year ({start_year}). Try again.")
                continue
            break
        except ValueError:
            logging.error("❌ The value you entered isn't a number. Try again.")

    return start_year, end_year

def plot_monthly_flights(df: pd.DataFrame, pdf: PdfPages) -> None:
    monthly_routes_df = df.copy()

    years = monthly_routes_df["year"].unique()
    start_year, end_year = get_year_range(years)

    monthly_routes_df = monthly_routes_df[monthly_routes_df["year"] >= start_year]
    monthly_routes_df = monthly_routes_df[monthly_routes_df["year"] <= end_year]

    monthly_routes_df["month_year"] = df["year"].astype(str) + "-" + df["month"].astype(str).str.zfill(2)
    monthly_routes_df = monthly_routes_df.sort_values(by=["year", "month"])

    plt.figure(figsize=(15, 7))
    ax = sns.lineplot(data=monthly_routes_df, x="month_year", y="total_flights", marker="o", lw=2)
    plt.title(f"Miesięczna Liczba Lotów w latach {start_year}-{end_year}", fontsize=12)

    ax.xaxis.set_major_locator(ticker.MaxNLocator(nbins=20))
    plt.xticks(rotation=45, ha="right")
    plt.xlabel("Miesiąc i Rok", fontsize=12)

    plt.grid(True, which='both', linestyle='--', linewidth=0.5)
    ax.yaxis.set_major_formatter(ticker.FuncFormatter(lambda x, p: format(int(x), ',')))
    plt.ylabel("Całkowita Liczba Lotów", fontsize=12)

    plt.tight_layout()
    pdf.savefig()
    plt.close()

def plot_airport_traffic(df: pd.DataFrame, pdf: PdfPages) -> None:
    top_records = get_number_of_records(len(df))
    airport_traffic_df = df.copy().head(top_records)


    plt.figure(figsize=(12, 8))
    sns.barplot(data=airport_traffic_df, x="airport_code", y="total_traffic", palette='Dark2')
    plt.title(f"Top {top_records} Lotnisk Według Ruchu")
    plt.ylabel("Całkowita Liczba Lotów", fontsize=12)
    plt.xlabel("Kody Lotnisk", fontsize=12)
    plt.tight_layout()
    pdf.savefig()
    plt.close()

def generate_pdf_report() -> None:
    engine = sqlalchemy.create_engine(DB_URL)
    os.makedirs(os.path.dirname(REPORT_OUTPUT), exist_ok=True)

    with engine.connect() as connection, PdfPages(REPORT_OUTPUT) as pdf:
        for title, sql_file in REPORT_QUERIES.items():
            logging.info(f"Generating report section: {title}")
            query = load_query(sql_file)
            df = pd.read_sql(query, connection)

            if title == TOP_ROUTES_QUERY_NAME:
                plot_top_routes(df, pdf)
            elif title == MONTHLY_FLIGHTS_QUERY_NAME:
                plot_monthly_flights(df, pdf)
            elif title == AIRPORT_TRAFFIC_QUERY_NAME:
                plot_airport_traffic(df, pdf)

    logging.info(f"✅ Report successfully saved to {REPORT_OUTPUT}")

if __name__ == "__main__":
    generate_pdf_report()
