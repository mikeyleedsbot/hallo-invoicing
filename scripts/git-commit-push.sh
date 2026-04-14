#!/usr/bin/env bash
# Helper: verwijder stale index.lock en commit + push alles
# Gebruik: ./scripts/git-commit-push.sh "commit message"
set -e

cd "$(dirname "$0")/.."

if [ -z "$1" ]; then
  echo "Usage: $0 \"commit message\""
  exit 1
fi

# Verwijder stale lock als die er is
if [ -f .git/index.lock ]; then
  echo "Stale .git/index.lock gevonden, verwijderen..."
  rm -f .git/index.lock
fi

git add -A
git commit -m "$1"
git push origin main
