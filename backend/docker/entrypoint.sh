#!/bin/sh
# Applies pending migrations before starting Apache (waits for the database to accept connections).
set -e

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    attempt=1
    until php bin/console migrate; do
        if [ "$attempt" -ge 30 ]; then
            echo "Migrations could not run; starting without them." >&2
            break
        fi
        echo "Waiting for the database (attempt $attempt)..." >&2
        attempt=$((attempt + 1))
        sleep 2
    done
fi

exec "$@"
