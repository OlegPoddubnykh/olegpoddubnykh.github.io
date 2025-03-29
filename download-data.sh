#!/bin/bash

# URL вашего GitHub Pages сайта
GITHUB_PAGES_URL="https://olegpoddubnykh.github.io/"

# Скачиваем файл
curl -o roadmap-data.json "${GITHUB_PAGES_URL}/roadmap-data.json"

echo "Данные успешно скачаны в roadmap-data.json" 