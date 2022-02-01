#!/bin/bash
# Bash strict mode
set -euo pipefail
IFS=$'\n\t'
set -x

# CLI-args
while getopts y:q: flag
do
    case "${flag}" in
        y) year=${OPTARG};;
        q) quarter=${OPTARG};;
    esac
done

# cacert.pem
date=$(date '+%Y-%m-%d')
wget -q -O cacert.pem "https://curl.se/ca/cacert-$date.pem"

# Collect all invoices
php transip/index.php -y=$year -q=$quarter
php digitalocean/index.php -y=$year -q=$quarter
php xsnews/index.php -y=$year -q=$quarter
php mollie/index.php -y=$year -q=$quarter
