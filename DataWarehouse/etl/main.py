from constants import FLIGHTS_CSV_PATH
from transform import transform_flight_data
from extract import load_csv



def main():
    print("Loading data from csv file")
    df = load_csv(FLIGHTS_CSV_PATH)
    print("Transforming the flights data")
    transform_flight_data(df)

if __name__ == "__main__":
    main()
