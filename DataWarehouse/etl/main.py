from extract import load_csv

FLIGHTS_CSV_PATH = "data/dataset.csv"

def main():
    df = load_csv(FLIGHTS_CSV_PATH)

if __name__ == "__main__":
    main()
