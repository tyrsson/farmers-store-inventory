#!/bin/bash
set -e

git config --global --add safe.directory /workspaces/farmers-store-inventory

cd /workspaces/farmers-store-inventory
composer update --no-interaction --ignore-platform-req=php
