<?php

require __DIR__.'/../vendor/autoload.php';

array_map('unlink', glob(__DIR__.'/logs/*.log'));
