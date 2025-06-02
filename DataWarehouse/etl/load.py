import sqlalchemy
import pandas as pd
from sqlalchemy.exc import IntegrityError, SQLAlchemyError

def load_to_postgres(df: pd.DataFrame, table_name: str, db_url: str, index: bool = False) -> None:
    engine = sqlalchemy.create_engine(db_url)
    try:
        with engine.begin() as connection:
            df.to_sql(
                name=table_name,
                con=connection,
                if_exists="replace" if not index else "append",
                index=False,
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
    finally:
        engine.dispose()


def read_from_postgres(table_name: str, db_url: str, columns: list[str] = None) -> pd.DataFrame:
    engine = sqlalchemy.create_engine(db_url)
    cols = "*" if columns is None else ", ".join(columns)
    query = f"SELECT {cols} FROM {table_name}"

    return pd.read_sql(query, engine)
