#!/bin/bash
# init.sh - bootstrap local environment

ENV_FILE="./backend/.env.docker"

if [ -f "$ENV_FILE" ]; then
    echo "$ENV_FILE already exists, skipping..."
else
    echo "Creating $ENV_FILE from example..."
    cp ./backend/.env.example "$ENV_FILE"

    # Optional: fill dummy/demo values
    # sed -i 's/your-client-id-here/dummy-client-id/' "$ENV_FILE"
    # sed -i 's/your-client-secret-here/dummy-client-secret/' "$ENV_FILE"
    # sed -i 's/your-refresh-token-here/dummy-refresh-token/' "$ENV_FILE"

    echo "$ENV_FILE created."
fi

echo "You can now run: docker-compose up -d"