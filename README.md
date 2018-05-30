HTML 2 MHT
=======================

This is a library to convert an html page into an MHT archive.

Dependencies
-------------
PHP >=7.1

Install library via composer:
---------------

```
composer require mrstacy/html2mht
```

Usage:
----------------
```
$html2mht = new Html2Mht(<FULL PATH TO INPUT HTML FILE>);
$html2mht->generateMhtFile(<FULL PATH TO OUTPUT MHT FILE>);
```

Running Tests locally:
---------------
In project root run the following to run tests
```
php vendor/phpunit/phpunit/phpunit
```


Known Issues:
---------------
* This only runs against local html files (not a URL)
* This does not include files that aren't linked directly from the main html file (IE: If an image is linked in the css file, it won't be included)