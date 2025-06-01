import sqlalchemy
import pandas

def load_to_postgres(df: pandas.DataFrame, table_name: str, db_url: str, index: bool):
    engine = sqlalchemy.create_engine(db_url)

    df.to_sql(
        table_name,
        engine,
        if_exists="append",
        index=index,
        method="multi"
    )
