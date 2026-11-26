#!/usr/bin/env bash

# Manual only: replace WordPress.org SVN trunk with an existing tag (sets the stable release).
# Not called from bin/release.sh or the CI tag job. Run after tags/<version> exists.
#
# Usage: WP_USERNAME=... WP_PASSWORD=... ./bin/deploy.sh <version>

set -euo pipefail

WP_SVN_REPO="${WP_SVN_REPO:-https://plugins.svn.wordpress.org/boldgrid-backup}"
TAG_NUMBER="${1:-}"

if [[ ! "${TAG_NUMBER}" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Usage: WP_USERNAME=... WP_PASSWORD=... $0 <version>  (for example: 1.17.5)" >&2
	exit 1
fi

if [[ -z "${WP_USERNAME:-}" || -z "${WP_PASSWORD:-}" ]]; then
	echo "WP_USERNAME and WP_PASSWORD must be set." >&2
	exit 1
fi

if ! svn info "${WP_SVN_REPO}/tags/${TAG_NUMBER}" >/dev/null 2>&1; then
	echo "Tag ${TAG_NUMBER} was not found at ${WP_SVN_REPO}/tags/." >&2
	exit 1
fi

echo "Replacing trunk with tags/${TAG_NUMBER} at ${WP_SVN_REPO}"

svnmucc \
	-m "Releasing new stable version" \
	--username "${WP_USERNAME}" \
	--password "${WP_PASSWORD}" \
	--non-interactive \
	--no-auth-cache \
	rm "${WP_SVN_REPO}/trunk" \
	cp HEAD "${WP_SVN_REPO}/tags/${TAG_NUMBER}" "${WP_SVN_REPO}/trunk"

echo "Trunk now matches tags/${TAG_NUMBER}."
