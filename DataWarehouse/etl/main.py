import logging
from constants import FLIGHTS_CSV_PATH
from transform import transform_flight_data
from extract import load_csv



def main():
    logging.info("Loading data from csv file")
    df = load_csv(FLIGHTS_CSV_PATH)
    logging.info("Transforming the flights data")
    transform_flight_data(df)

if __name__ == "__main__":
    main()
