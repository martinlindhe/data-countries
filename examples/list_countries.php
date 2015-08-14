<?php

require_once __DIR__.'/../vendor/autoload.php';

foreach (MartinLindhe\Data\Countries\CountryList::all() as $o) {
    d($o);
}
