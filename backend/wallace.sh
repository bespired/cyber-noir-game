#!/usr/bin/env bash


# openssl enc -aes-256-cbc -pbkdf2 \
#   -in .env.docker \
#   -out env.docker.enc

# exit;

# https://drive.google.com/file/d/1fJMaElHY0BRl5_qsO6EsCiXCWmX33cZI/view?usp=drive_link

set -e

ENV_FILE=".env.docker"
ENC_FILE="env.docker.enc"
URL="https://drive.google.com/uc?export=download&id=1hGDfZG0Tx5y8e-RjEgE6u1WLwzvu41Wm"

if [ -f "$ENV_FILE" ]; then
  echo "$ENV_FILE already exists. Skipping."
  exit 0
fi

echo "Downloading encrypted secrets..."
curl -L -o "$ENC_FILE" "$URL"

echo "Enter password to decrypt secrets:"
openssl enc -aes-256-cbc -pbkdf2 \
  -d -in "$ENC_FILE" -out "$ENV_FILE"

rm "$ENC_FILE"

echo ".env.docker created successfully."
echo "You can now run: docker compose up -d"


# openssl enc -aes-256-cbc -pbkdf2 -d -in "env.docker.enc" -out "env.docker.test"