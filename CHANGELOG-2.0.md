# Changelog for version 2.0

This is a list of changes done in version 2.0.

## Dropped support for PHP below 7.1

To be able to fully utilize new functionality introduced in 7.1, we have decided
to only support PHP versions equal or higher to it.

## The postHydrate event has been removed

It was created due to the fact that prior to `doctrine/common 2.5`, the `postLoad`
event was fired before associations were loaded. Since that has been changed, it
has become redundant.
