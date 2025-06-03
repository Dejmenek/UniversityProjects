from typing import Dict
import pandas as pd
import constants as c
import sqlalchemy
from load import load_to_postgres, read_from_postgres

def normalize_text_cells(df: pd.DataFrame) -> None:
    for col in c.TEXT_COLUMNS:
        df[col] = df[col].str.strip().str.title()

def prepare_flights(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    dates_df = read_from_postgres(c.DATES_TABLE, connection, ["date_id", "full_date"])
    airports_df = read_from_postgres(c.AIRPORTS_TABLE, connection, ["airport_id", "airport_code"])
    pilots_df = read_from_postgres(c.PILOTS_TABLE, connection, ["pilot_id", "first_name", "last_name"])
    passengers_df = read_from_postgres(c.PASSENGERS_TABLE, connection, ["passenger_id", "passenger_code"])
    flight_statuses_df = read_from_postgres(c.FLIGHT_STATUSES_TABLE, connection, ["status_id", "name"])

    flights_df = df.copy()

    flights_df = flights_df.merge(
        pilots_df,
        left_on=[c.PILOT_FIRST_NAME_COLUMN, c.PILOT_LAST_NAME_COLUMN],
        right_on=["first_name", "last_name"]
    )
    print("After merging pilots:", flights_df.shape)

    flights_df["departure_full_date"] = pd.to_datetime(flights_df[c.DEPARTURE_DATE_COLUMN], format="%m/%d/%Y").dt.strftime("%Y-%m-%d")
    dates_df["full_date"] = pd.to_datetime(dates_df["full_date"]).dt.strftime("%Y-%m-%d")
    print("Dates from database:", dates_df["full_date"])
    print("Dates from file:", flights_df["departure_full_date"])
    flights_df = flights_df.merge(
        dates_df,
        left_on="departure_full_date",
        right_on="full_date",
        how="inner"
    ).rename(columns={"date_id": "departure_date_id"})
    print("After merging dates:", flights_df.shape)

    flights_df = flights_df.merge(
        airports_df,
        left_on=c.DEPARTURE_AIRPORT_CODE_COLUMN,
        right_on="airport_code",
        how="inner"
    ).rename(columns={"airport_id": "departure_airport_id"})
    print("After merging airports departures:", flights_df.shape)

    flights_df = flights_df.merge(
        airports_df,
        left_on=c.ARRIVAL_AIRPORT_CODE_COLUMN,
        right_on="airport_code",
        how="inner"
    ).rename(columns={"airport_id": "arrival_airport_id"})
    print("After merging airport arrivals:", flights_df.shape)

    flights_df = flights_df.merge(
        passengers_df,
        left_on=c.PASSENGER_ID_COLUMN,
        right_on="passenger_code",
        how="inner"
    )
    print("After merging passengers:", flights_df.shape)

    flights_df = flights_df.merge(
        flight_statuses_df,
        left_on=c.FLIGHT_STATUS_COLUMN,
        right_on="name",
        how="inner"
    ).rename(columns={"status_id": "flight_status_id"})
    print("After merging statutes:", flights_df.shape)

    flights_df = flights_df[[
        "passenger_id", "pilot_id", "departure_airport_id",
        "arrival_airport_id", "departure_date_id", "flight_status_id"
    ]]
    print("After every merge:", flights_df.shape)

    load_to_postgres(flights_df, c.FLIGHTS_TABLE, connection)

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
    passengers_df = passengers_df.merge(countries_df, left_on="country_name", right_on="name", how="inner").rename(
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
    airports_df = airports_df.merge(countries_df, left_on="country_name", right_on="name", how="inner")
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
    parsed_dates = pd.to_datetime(df[c.DEPARTURE_DATE_COLUMN], format="%m/%d/%Y", errors="coerce")
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

def prepare_facts(df: pd.DataFrame, connection: sqlalchemy.Connection) -> None:
    prepare_flights(df, connection)

def transform_flight_data(df: pd.DataFrame) -> None:
    df.dropna(inplace=True)
    normalize_text_cells(df)

    engine = sqlalchemy.create_engine(c.DB_URL)
    try:
        with engine.begin() as connection:
            # prepare_dimensions(df, connection)
            # prepare_facts(df, connection)
            prepare_flights(df, connection)
        print("✅ All data successfully loaded in a single transaction.")
    except Exception as e:
        print(f"❌ Transaction failed. Rolled back. Reason: {str(e)}")
        raise
    finally:
        engine.dispose()
