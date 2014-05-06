#!/bin/bash

repo_dir="$1"
if [ -z $repo_dir ]; then
    echo "== ERROR: please repo dir as first argument to this script";
    exit;
else
    echo "== Starting merge for "$repo_dir
fi

main_branch="$2"
if [ -z $main_branch ]; then
	main_branch="master"
fi

(
	cd $repo_dir \
	&& git branch -a \
	&& git reset --hard \
	&& git checkout origin/master \
	&& git fetch upstream \
	&& git checkout $main_branch \
	&& git reset --hard upstream/master \
	&& git merge upstream/master \
	&& git push --all
)
