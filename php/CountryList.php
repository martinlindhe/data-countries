<?php namespace MartinLindhe\Data\Countries;

class CountryList
{
    /**
     * @return Country[]
     */
    public static function all()
    {
        $fileName = __DIR__.'/../data/countries.json';

        $data = file_get_contents($fileName);

        $list = [];
        foreach (json_decode($data) as $t) {
            $o = new Country;
            foreach ($t as $key => $value) {
                $o->{$key} = $value;
            }
            $list[] = $o;
        }
        return $list;
    }
}
