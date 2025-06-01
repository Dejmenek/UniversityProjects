import pandas

dtype_dict = {
    "Passenger ID": "object",
    "First Name": "object",
    "Last Name": "object",
    "Gender": "category",
    "Age": "Int64",
    "Nationality": "object",
    "Airport Name": "object",
    "Airport Country Code": "object",
    "Country Name": "object",
    "Airport Continent": "category",
    "Continents": "category",
    "Arrival Airport": "object",
    "Pilot Name": "object",
    "Flight Status": "category"
}

def load_csv(file_path: str) -> pandas.DataFrame:
    try:
        df = pandas.read_csv(file_path, dtype=dtype_dict, parse_dates=["Departure Date"])
        return df
    except FileNotFoundError:
        print(f"[ERROR] Plik nie został znaleziony: {file_path}")
        raise
    except Exception as e:
        print(f"[ERROR] Błąd wczytywania pliku: {e}")
        raise
