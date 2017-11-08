Zk2\SpsComponent
================

[![Build Status](https://travis-ci.org/zk2/SPSComponent.svg?branch=master)](https://travis-ci.org/zk2/SPSComponent)

[![Latest Stable Version](https://poser.pugx.org/zk2/sps-component/v/stable)](https://packagist.org/packages/zk2/sps-component)
[![Total Downloads](https://poser.pugx.org/zk2/sps-component/downloads)](https://packagist.org/packages/zk2/sps-component)
[![Latest Unstable Version](https://poser.pugx.org/zk2/sps-component/v/unstable)](https://packagist.org/packages/zk2/sps-component)
[![License](https://poser.pugx.org/zk2/sps-component/license)](https://packagist.org/packages/zk2/sps-component)
[![composer.lock](https://poser.pugx.org/zk2/sps-component/composerlock)](https://packagist.org/packages/zk2/sps-component)

Often there is a need to provide the end user with the possibility of complex filtering any data.
It is quite problematic to correctly place parentheses in the AND / OR set.
It is even more problematic to filter / sort by value from the aggregating function.

The component is intended for building valid blocks "WHERE", "OFFSET", "LIMIT" and "ORDER BY"
in `Doctrine\DBAL\Query\QueryBuilder` | `Doctrine\ORM\QueryBuilder`.
Also, the component allows you to use aggregating functions in the blocks "WHERE" and "ORDER BY".

Documentation
-------------

[Quick start](https://github.com/zk2/SPSComponent/blob/master/doc/quick_start.rst)

Running the Tests
-----------------

Install the [Composer](http://getcomposer.org/) `dev` dependencies:

    php composer.phar install --dev

Then, run the test suite using
[PHPUnit](https://github.com/sebastianbergmann/phpunit/):

    vendor/bin/phpunit

License
-------

This bundle is released under the MIT license. See the complete license in the bundle:

    LICENSE
    
