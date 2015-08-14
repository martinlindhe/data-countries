<?php namespace MartinLindhe\Data\Countries;

class CountryList
{
    /**
     * @return Country[]
     */
    public static function all()
    {
        $fileName = __DIR__.'/../data/countries.csv';
        $csv = \League\Csv\Reader::createFromPath($fileName);

        // skip header
        $csv->setOffset(1);

        $list = [];
        $csv->each(function ($c) use (&$list) {

            if (!$c[0]) {
                return true;
            }

            $o = new Country;
            $o->alpha2 = $c[0];
            $o->alpha3 = $c[1];
            $o->number = $c[2];
            $o->name = $c[3];
            $list[] = $o;
            return true;
        });

        return $list;
    }
}
