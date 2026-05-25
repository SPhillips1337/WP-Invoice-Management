#!/usr/bin/env bash
set -euo pipefail

REPO_OWNER="SPhillips1337"
REPO_NAME="WP-Invoice-Management"
REPO_URL="https://github.com/${REPO_OWNER}/${REPO_NAME}.git"
PLUGIN_DIR_NAME="wp-invoice-management"
COMPOSER_PACKAGE="wpim/wp-invoice-management"

usage() {
  cat <<'USAGE'
Usage: ./install.sh [--dir PATH] [--dev] [--no-composer]

Clone or validate WP-Invoice-Management and install its PHP dependencies.

Options:
  --dir PATH       Directory to install into (default: current directory if it
                   already looks like this repo, otherwise ./WP-Invoice-Management)
  --dev            Install Composer dev dependencies as well.
  --no-composer    Skip Composer dependency installation.
  -h, --help       Show this help.

Examples:
  ./install.sh
  ./install.sh --dir ~/src/WP-Invoice-Management --dev
USAGE
}

log() { printf '[install] %s\n' "$*"; }
fail() { printf '[install:error] %s\n' "$*" >&2; exit 1; }
need_cmd() { command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"; }

INSTALL_DIR=""
WITH_DEV=0
RUN_COMPOSER=1

while [ "$#" -gt 0 ]; do
  case "$1" in
    --dir)
      [ "$#" -ge 2 ] || fail "--dir requires a path"
      INSTALL_DIR="$2"
      shift 2
      ;;
    --dev)
      WITH_DEV=1
      shift
      ;;
    --no-composer)
      RUN_COMPOSER=0
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      fail "Unknown option: $1"
      ;;
  esac
done

need_cmd git

current_dir=$(pwd -P)
if [ -z "${INSTALL_DIR}" ]; then
  if [ -f "${current_dir}/${PLUGIN_DIR_NAME}/composer.json" ]; then
    INSTALL_DIR="${current_dir}"
  else
    INSTALL_DIR="${current_dir}/${REPO_NAME}"
  fi
fi

case "${INSTALL_DIR}" in
  ~*) fail "Use an expanded absolute or relative path for --dir; '~' is not expanded inside quoted arguments" ;;
esac

validate_repo_identity() {
  target="$1"
  [ -d "${target}" ] || return 1

  if [ -d "${target}/.git" ]; then
    origin_url=$(git -C "${target}" config --get remote.origin.url || true)
    case "${origin_url}" in
      "https://github.com/${REPO_OWNER}/${REPO_NAME}.git"|"git@github.com:${REPO_OWNER}/${REPO_NAME}.git"|"https://github.com/${REPO_OWNER}/${REPO_NAME}"|"ssh://git@github.com/${REPO_OWNER}/${REPO_NAME}.git")
        return 0
        ;;
      *)
        fail "Existing git directory at ${target} has unexpected origin: ${origin_url:-<none>}"
        ;;
    esac
  fi

  composer_file="${target}/${PLUGIN_DIR_NAME}/composer.json"
  plugin_file="${target}/${PLUGIN_DIR_NAME}/${PLUGIN_DIR_NAME}.php"
  if [ -f "${composer_file}" ] && [ -f "${plugin_file}" ]; then
    if grep -Fq "\"name\": \"${COMPOSER_PACKAGE}\"" "${composer_file}" && grep -Fq "Plugin Name: WP Invoice Management" "${plugin_file}"; then
      return 0
    fi
  fi

  fail "Existing directory at ${target} does not look like ${REPO_OWNER}/${REPO_NAME}; refusing to modify it"
}

if [ -e "${INSTALL_DIR}" ]; then
  validate_repo_identity "${INSTALL_DIR}"
  log "Using existing checkout: ${INSTALL_DIR}"
else
  parent_dir=$(dirname "${INSTALL_DIR}")
  mkdir -p "${parent_dir}"
  log "Cloning ${REPO_URL} into ${INSTALL_DIR}"
  git clone "${REPO_URL}" "${INSTALL_DIR}"
fi

if [ -d "${INSTALL_DIR}/.git" ]; then
  git -C "${INSTALL_DIR}" fetch --all --prune
fi

PLUGIN_DIR="${INSTALL_DIR}/${PLUGIN_DIR_NAME}"
[ -f "${PLUGIN_DIR}/composer.json" ] || fail "Missing expected Composer file: ${PLUGIN_DIR}/composer.json"
[ -f "${PLUGIN_DIR}/${PLUGIN_DIR_NAME}.php" ] || fail "Missing expected plugin entrypoint: ${PLUGIN_DIR}/${PLUGIN_DIR_NAME}.php"

if [ "${RUN_COMPOSER}" -eq 1 ]; then
  if command -v composer >/dev/null 2>&1; then
    if [ "${WITH_DEV}" -eq 1 ]; then
      log "Installing Composer dependencies with dev packages"
      composer install --working-dir="${PLUGIN_DIR}" --no-interaction --prefer-dist
    else
      log "Installing Composer production dependencies"
      composer install --working-dir="${PLUGIN_DIR}" --no-dev --no-interaction --prefer-dist --optimize-autoloader
    fi
  elif command -v docker >/dev/null 2>&1; then
    if [ "${WITH_DEV}" -eq 1 ]; then
      log "Composer not found locally; using official Composer Docker image with dev packages"
      docker run --rm --volume "${PLUGIN_DIR}:/app" composer:2 install --no-interaction --prefer-dist
    else
      log "Composer not found locally; using official Composer Docker image for production dependencies"
      docker run --rm --volume "${PLUGIN_DIR}:/app" composer:2 install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    fi
  else
    fail "Composer is required to install PHP dependencies (or install Docker, or rerun with --no-composer)"
  fi
else
  log "Skipping Composer dependency installation (--no-composer)"
fi

log "Installed ${REPO_OWNER}/${REPO_NAME} at ${INSTALL_DIR}"
log "Plugin directory: ${PLUGIN_DIR}"
log "For WordPress, copy or symlink '${PLUGIN_DIR}' into wp-content/plugins/ and activate 'WP Invoice Management'."
