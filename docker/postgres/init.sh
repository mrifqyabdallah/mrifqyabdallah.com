#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "postgres" <<-EOSQL
    CREATE DATABASE "${POSTGRES_DB}_test";
    GRANT ALL PRIVILEGES ON DATABASE "${POSTGRES_DB}_test" TO "$POSTGRES_USER";
EOSQL