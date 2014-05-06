#!/bin/bash

repos_dir="$1"
if [ -z $repos_dir ]; then
    echo "== ERROR: please repos dir as first argument to this script";
    exit;
fi
for d in $repos_dir*; do
	echo "=== "$d" ===";
	./merge_from_upstream.sh "$d"
done