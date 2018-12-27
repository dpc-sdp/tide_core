#!/usr/bin/env bash
##
# Setup project for development.
#
# Usage:
# . dev-init.sh
#

# Development only: uncomment and set commit value to fetch Dev Tools at
# specific commit.
#export GH_COMMIT=COMMIT_SHA

# Install development files, including the scripts used below, from the
# centralised location.
curl https://raw.githubusercontent.com/dpc-sdp/dev-tools/master/install | bash
