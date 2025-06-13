import logging
import pandas

from constants import DTYPE_DICT

def load_csv(file_path: str) -> pandas.DataFrame:
    try:
        df = pandas.read_csv(file_path, dtype=DTYPE_DICT)
        return df
    except FileNotFoundError:
        logging.error(f"❌ File not found: {file_path}")
        raise
    except Exception as e:
        logging.error(f"❌ File loading error: {e}")
        raise
