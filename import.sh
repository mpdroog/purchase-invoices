#!/bin/bash
# Bash strict mode
set -euo pipefail
IFS=$'\n\t'

year=""
quarter=""
args=""

# CLI-args
optstring=":hy:q:v"
while getopts ${optstring} arg; do
  case ${arg} in
    v) args="-v"; set -x ;;
    y) year=$OPTARG ;;
    q) quarter=$OPTARG ;;
    h)
      echo "./$(basename $0) -y=YYYY -q=N"
      echo " -y = Year"
      echo " -q = Quarter number"
      echo " -v = Verbose"
      exit 0
      ;;
    :)
      echo "$0: Must supply an argument to -$OPTARG." >&2
      exit 1
      ;;
    ?)
      echo "Invalid option: -${OPTARG}."
      exit 2
      ;;
  esac
done

if [ -z "$year" ] || [ -z "$quarter" ]; then
    echo 'Missing -y or -q' >&2
    echo '  example: ./import.sh -y 2022 -q 1' >&2
    exit 1
fi
echo "year=$year quarter=$quarter"

# cacert.pem
wget -nv -O cacert.pem "https://curl.se/ca/cacert.pem"

# Collect all invoices
php transip/index.php -y=$year -q=$quarter $args
php digitalocean/index.php -y=$year -q=$quarter $args
php xsnews/index.php -y=$year -q=$quarter $args
php mollie/index.php -y=$year -q=$quarter $args
php vultr/index.php -y=$year -q=$quarter $args
php to-html.php -y=$year -q=$quarter $args
