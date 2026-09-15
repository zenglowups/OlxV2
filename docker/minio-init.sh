#!/bin/sh
set -eu
mc alias set local http://minio:9000 "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY"
mc mb --ignore-existing "local/$AWS_BUCKET"
# Buckets stay private, including future original images and legal evidence.
