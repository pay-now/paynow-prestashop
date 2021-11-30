#!/usr/bin/env bash

echo "Cleaning target"
rm -fr vendor dist

echo "Preparing vendors"
composer install --no-dev --optimize-autoloader

echo "Preparing target directory"
mkdir dist
cd dist

echo "Copying sources"
rsync -a --exclude={'*.md',dist,'.gitignore',instruction,scripts,'.*'} ../* paynow

echo "Add index files"
git clone https://github.com/PrestaShopCorp/autoindex
cd autoindex
composer install
php bin/autoindex prestashop:add:index ../paynow
cd ..

echo "Preparing zip"
zip -r paynow.zip ./paynow