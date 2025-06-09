import logging
from typing import Dict
import pandas as pd
import constants as c
import sqlalchemy
from load import load_to_postgres, read_from_postgres

def normalize_text_cells(df: pd.DataFrame) -> None:
    for col in c.TEXT_COLUMNS:
        df[col] = df[col].str.strip().str.title()

def merge_and_validate(base_df: pd.DataFrame,
                       dim_df: pd.DataFrame,
                       left_on_cols: list[str],
                       right_on_cols: list[str],
                       dim_id_col: str,
                       merge_type: str="inner") -> pd.DataFrame:
        initial_rows = len(base_df)
        merged_df = base_df.merge(dim_df, how=merge_type, left_on=left_on_cols, right_on=right_on_cols)
        
        unmatched_count = merged_df[dim_id_col].isnull().sum()
        if unmatched_count > 0:
            logging.warning(f"Found {unmatched_count} rows with no match for '{dim_id_col}'. These rows will be dropped.")
            merged_df.dropna(subset=[dim_id_col], inplace=True)
        
        final_rows = len(merged_df)
        logging.info(f"Merged on {dim_id_col}. Rows changed from {initial_rows} to {final_rows}.")
        return merged_df

def prepare_flights(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    flights_df = df.copy()

    dates_df = read_from_postgres(c.DATES_TABLE, connection, ["date_id", "full_date"])
    dates_df["full_date_obj"] = pd.to_datetime(dates_df["full_date"]).dt.date

    airports_df = read_from_postgres(c.AIRPORTS_TABLE, connection, ["airport_id", "airport_code"]).astype({"airport_id": "Int64"})
    pilots_df = read_from_postgres(c.PILOTS_TABLE, connection, ["pilot_id", "first_name", "last_name"]).astype({"pilot_id": "Int64"})
    passengers_df = read_from_postgres(c.PASSENGERS_TABLE, connection, ["passenger_id", "passenger_code"]).astype({"passenger_id": "Int64"})
    flight_statuses_df = read_from_postgres(c.FLIGHT_STATUSES_TABLE, connection, ["status_id", "name"]).astype({"status_id": "Int64"})

    flights_df = merge_and_validate(
        flights_df, pilots_df, 
        [c.PILOT_FIRST_NAME_COLUMN, c.PILOT_LAST_NAME_COLUMN],
        ["first_name", "last_name"],
        "pilot_id",
        "left"
    )

    flights_df["departure_date_obj"] = pd.to_datetime(flights_df[c.DEPARTURE_DATE_COLUMN], format="%m/%d/%Y").dt.date
    flights_df = merge_and_validate(
        flights_df, dates_df.rename(columns={"date_id": "departure_date_id"}),
        ["departure_date_obj"],
        ["full_date_obj"],
        "departure_date_id",
        "left"
    )

    dep_airports_df = airports_df.rename(columns={"airport_id": "departure_airport_id", "airport_code": "departure_airport_code"})
    arr_airports_df = airports_df.rename(columns={"airport_id": "arrival_airport_id", "airport_code": "arrival_airport_code"})

    flights_df = merge_and_validate(
        flights_df, dep_airports_df,
        [c.DEPARTURE_AIRPORT_CODE_COLUMN],
        ["departure_airport_code"],
        "departure_airport_id",
        "left"
    )

    flights_df = merge_and_validate(
        flights_df, arr_airports_df,
        [c.ARRIVAL_AIRPORT_CODE_COLUMN],
        ["arrival_airport_code"],
        "arrival_airport_id",
        "left"
    )

    flights_df[c.PASSENGER_ID_COLUMN] = flights_df[c.PASSENGER_ID_COLUMN].astype(str).str.strip().str.replace('\u00A0', '').str.lower()
    passengers_df["passenger_code"] = passengers_df["passenger_code"].astype(str).str.strip().str.replace('\u00A0', '').str.lower()

    flights_df = merge_and_validate(
        flights_df, passengers_df,
        [c.PASSENGER_ID_COLUMN],
        ["passenger_code"],
        "passenger_id",
        "left"
    )

    flights_df = merge_and_validate(
        flights_df, flight_statuses_df.rename(columns={"status_id": "flight_status_id"}),
        [c.FLIGHT_STATUS_COLUMN],
        ["name"],
        "flight_status_id",
        "left"
    )

    flights_df = flights_df[[
        "passenger_id", "pilot_id", "departure_airport_id",
        "arrival_airport_id", "departure_date_id", "flight_status_id"
    ]]

    load_to_postgres(flights_df, c.FLIGHTS_TABLE, connection)

def prepare_facts(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    prepare_flights(df, connection)

def prepare_passengers(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    passengers_df = df[[c.PASSENGER_ID_COLUMN, c.PASSENGER_FIRST_NAME_COLUMN, c.PASSENGER_LAST_NAME_COLUMN,
                        c.GENDER_COLUMN, c.AGE_COLUMN, c.NATIONALITY_COLUMN]].drop_duplicates()

    passengers_df.rename(columns={
        c.PASSENGER_ID_COLUMN: "passenger_code",
        c.PASSENGER_FIRST_NAME_COLUMN: "first_name",
        c.PASSENGER_LAST_NAME_COLUMN: "last_name",
        c.GENDER_COLUMN: "gender",
        c.AGE_COLUMN: "age",
        c.NATIONALITY_COLUMN: "country_name"
    }, inplace=True)

    countries_df = read_from_postgres(c.COUNTRIES_TABLE, connection, ["country_id", "name"])
    passengers_df = merge_and_validate(passengers_df, countries_df, ["country_name"], ["name"], "country_id", "left").rename(
        columns={"country_id": "nationality_id"}
    )
    passengers_df = passengers_df[["first_name", "last_name", "gender", "age", "nationality_id", "passenger_code"]]

    load_to_postgres(passengers_df, c.PASSENGERS_TABLE, connection)


def prepare_airports(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    departures_df = df[[c.DEPARTURE_AIRPORT_NAME_COLUMN, c.DEPARTURE_AIRPORT_CODE_COLUMN, c.DEPARTURE_COUNTRY_NAME_COLUMN]].rename(
        columns={
            c.DEPARTURE_AIRPORT_NAME_COLUMN: "airport_name",
            c.DEPARTURE_AIRPORT_CODE_COLUMN: "airport_code",
            c.DEPARTURE_COUNTRY_NAME_COLUMN: "country_name"
        }
    )

    arrivals_df = df[[c.ARRIVAL_AIRPORT_NAME_COLUMN, c.ARRIVAL_AIRPORT_CODE_COLUMN, c.ARRIVAL_COUNTRY_NAME_COLUMN]].rename(
        columns={
            c.ARRIVAL_AIRPORT_NAME_COLUMN: "airport_name",
            c.ARRIVAL_AIRPORT_CODE_COLUMN: "airport_code",
            c.ARRIVAL_COUNTRY_NAME_COLUMN: "country_name"
        }
    )

    airports_df = pd.concat([departures_df, arrivals_df]).drop_duplicates()

    countries_df = read_from_postgres(c.COUNTRIES_TABLE, connection, ["country_id", "name"])
    airports_df = merge_and_validate(airports_df, countries_df, ["country_name"], ["name"], "country_id", "left")
    airports_df = airports_df[["airport_code", "airport_name", "country_id"]]
    airports_df.rename(columns={"airport_name": "name"}, inplace=True)

    load_to_postgres(airports_df, c.AIRPORTS_TABLE, connection)

def prepare_countries(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    arrival_countries = df[c.ARRIVAL_COUNTRY_NAME_COLUMN].rename("name").to_frame()
    passenger_nationalities = df[c.NATIONALITY_COLUMN].rename("name").to_frame()

    countries_df = pd.concat([arrival_countries, passenger_nationalities]).drop_duplicates()

    load_to_postgres(countries_df, c.COUNTRIES_TABLE, connection)

def prepare_pilots(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    pilots_df = df[[c.PILOT_FIRST_NAME_COLUMN, c.PILOT_LAST_NAME_COLUMN]].drop_duplicates().rename(
        columns={
            c.PILOT_FIRST_NAME_COLUMN: "first_name",
            c.PILOT_LAST_NAME_COLUMN: "last_name"
        }
    )

    load_to_postgres(pilots_df, c.PILOTS_TABLE, connection)

def prepare_dates(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    parsed_dates = pd.to_datetime(df[c.DEPARTURE_DATE_COLUMN], format="%m/%d/%Y")
    dates_df = pd.DataFrame({"full_date": parsed_dates})

    dates_df["year"] = dates_df["full_date"].dt.year
    dates_df["month"] = dates_df["full_date"].dt.month
    dates_df["month_name"] = dates_df["full_date"].dt.month_name()
    dates_df["day"] = dates_df["full_date"].dt.day
    dates_df["day_name"] = dates_df["full_date"].dt.day_name()
    dates_df["full_date"] = dates_df["full_date"].dt.strftime("%Y-%m-%d")

    dates_df.drop_duplicates(subset="full_date", inplace=True)

    load_to_postgres(dates_df, c.DATES_TABLE, connection)


def prepare_flight_statuses(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    flight_statuses_df = df[c.FLIGHT_STATUS_COLUMN].drop_duplicates().to_frame(name="name")

    load_to_postgres(flight_statuses_df, c.FLIGHT_STATUSES_TABLE, connection)
    

def prepare_dimensions(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    prepare_countries(df, connection)
    prepare_dates(df, connection)
    prepare_pilots(df, connection)
    prepare_passengers(df, connection)
    prepare_flight_statuses(df, connection)
    prepare_airports(df, connection)

def transform_flight_data(df: pd.DataFrame) -> None:
    df.dropna(inplace=True)
    normalize_text_cells(df)

    engine = sqlalchemy.create_engine(c.DB_URL)
    try:
        with engine.begin() as connection:
            prepare_dimensions(df, connection)
            prepare_facts(df, connection)
        logging.info("✅ All data successfully loaded in a single transaction.")
    except Exception as e:
        logging.error(f"❌ Transaction failed. Rolled back. Reason: {str(e)}")
        raise
    finally:
        engine.dispose()
