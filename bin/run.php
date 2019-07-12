<?php

require_once __DIR__.'/../vendor/autoload.php';

$c = new \PlaylistGenerator\Generator(__DIR__.'/../configuration.yml');
$c->generate();
