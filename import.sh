#!/bin/bash
set -euo pipefail
IFS=$'\n\t'
set -x

while getopts y:q: flag
do
    case "${flag}" in
        y) year=${OPTARG};;
        q) quarter=${OPTARG};;
    esac
done

php transip/index.php -y=$year -q=$quarter
php digitalocean/index.php -y=$year -q=$quarter
php xsnews/index.php -y=$year -q=$quarter
php mollie/index.php -y=$year -q=$quarter
