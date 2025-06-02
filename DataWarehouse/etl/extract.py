import pandas

from constants import DTYPE_DICT


def load_csv(file_path: str) -> pandas.DataFrame:
    try:
        df = pandas.read_csv(file_path, dtype=DTYPE_DICT)
        return df
    except FileNotFoundError:
        print(f"[ERROR] Plik nie został znaleziony: {file_path}")
        raise
    except Exception as e:
        print(f"[ERROR] Błąd wczytywania pliku: {e}")
        raise
