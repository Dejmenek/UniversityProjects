import sqlalchemy
import pandas as pd
from sqlalchemy.exc import IntegrityError, SQLAlchemyError

def load_to_postgres(df: pd.DataFrame, table_name: str, connection: sqlalchemy.Connection, index: bool = False) -> None:
    try:
        df.to_sql(
                name=table_name,
                con=connection,
                if_exists="append",
                index=index,
                method="multi"
            )
        print(f"✅ Successfully loaded data into '{table_name}' table.")
    except IntegrityError as ie:
        print(f"⚠️ IntegrityError: {ie.orig}")
        raise
    except SQLAlchemyError as e:
        print(f"❌ SQLAlchemyError: {str(e)}")
        raise
    except Exception as e:
        print(f"❌ Unexpected error: {str(e)}")
        raise


def read_from_postgres(table_name: str, connection: sqlalchemy.Connection, columns: list[str] = None) -> pd.DataFrame:
    cols = "*" if columns is None else ", ".join(columns)
    query = f"SELECT {cols} FROM {table_name}"

    return pd.read_sql(query, connection)
