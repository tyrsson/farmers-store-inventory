#!/bin/bash
set -e

git config --global --add safe.directory /workspaces/inventory-management-system

cd /workspaces/inventory-management-system
composer update --no-interaction --ignore-platform-req=php
