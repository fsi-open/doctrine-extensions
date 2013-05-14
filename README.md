# FSi Doctrine 2 Extensions package #

This is a set of Doctrine 2 behavioral extensions.

Documentation for each extension can be found in ``/doc`` folder:

- [doc/lostorage.md](doc/lostorage.md) - *Large Objects Storage*
- [doc/translatable.md](doc/translatable.md) - *Translatable*
- [doc/versionable.md](doc/versionable.md) - *Versionable*


## 1. Download Doctrine Extensions

Add to composer.json

```
    "repositories": [
        {
            "type": "composer",
            "url": "http://git.fsi.pl"
        }
    ],
    "require": {
        "fsi/doctrine-extensions": "1.0.x-dev"
    }
```