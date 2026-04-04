#!/bin/bash
cd "$(dirname "$0")"
docker compose up -d --build
echo "Dev Console running at http://localhost:3333"
