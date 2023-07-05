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
# composer.phar
if [ ! -e "composer.phar" ]; then
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
  php composer-setup.php
  php -r "unlink('composer-setup.php');"

  cd transip
  ../composer.phar install
  cd -
  cd digitalocean
  ../composer.phar install
  cd -
  cd xsnews
  ../composer.phar install
  cd -
  cd vultr
  ../composer.phar install
  cd -
fi

# Collect all invoices
php transip/index.php -y=$year -q=$quarter $args
php digitalocean/index.php -y=$year -q=$quarter $args
php xsnews/index.php -y=$year -q=$quarter $args
php mollie/index.php -y=$year -q=$quarter $args
php vultr/index.php -y=$year -q=$quarter $args
php to-html.php -y=$year -q=$quarter $args
