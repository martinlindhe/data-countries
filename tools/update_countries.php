<?php

// script to update data files, based onhttps://en.wikipedia.org/wiki/ISO_3166-1

require_once __DIR__.'/../vendor/autoload.php';

function cleanText($s)
{
    $s = trim($s);

    if ($s == '|-') {
        return '';
    }

    if (substr($s, 0, 2) == '| ') {
        $s = substr($s, 2);
    }

    $p1 = strpos($s, '<!--');
    if ($p1 !== false) {
        $p2 = strpos($s, '-->');
        if ($p2 !== false) {
            $s = substr($s, 0, $p1).substr($s, $p2 + strlen('-->'));
            return cleanText($s);
        }
        return '';
    }

    $p1 = strpos($s, '<ref>');
    if ($p1 !== false) {
        $p2 = strpos($s, '</ref>');
        if ($p2 !== false) {
            $s = substr($s, 0, $p1).substr($s, $p2 + strlen('</ref>'));
            return cleanText($s);
        }
        return '';
    }

    return $s;
}

function getRightSideOfMediawikiTag($t)
{
    $pos = mb_strpos($t, '{{');
    if ($pos === false) {
        return $t;
    }

    $pos2 = mb_strpos($t, '}}', $pos);
    if ($pos2 === false) {
        return $t;
    }

    $t = mb_substr($t, $pos, $pos2 - $pos);
    $n = explode('|', $t);

    if (!empty($n[1])) {
        return array_pop($n);
    }
    return $t;
}

function isAlpha3InList($alpha3, $list)
{
    foreach ($list as $o) {
        if ($o->alpha3 == $alpha3) {
            return true;
        }
    }
    return false;
}

/**
 * @param string $n name as used in the ISO 3166/MA document, and thus in the wikipedia article
 * @return string common english name
 */
function translateName($n)
{
    if ($n == 'Cabo Verde') {
        return 'Cape Verde';
    }
    if ($n == "Lao People's Democratic Republic") {
        return 'Laos';
    }
    if ($n == 'Viet Nam') {
        return 'Vietnam';
    }
    if ($n == 'Congo (Democratic Republic of the)') {
        return 'Democratic Republic of the Congo';
    }
    if ($n == 'Congo') {
        return 'Republic of the Congo';
    }
    if ($n == 'Palestine, State of') {
        return 'State of Palestine';
    }
    if ($n == "Korea (Democratic People's Republic of)") {
        return 'North Korea';
    }
    if ($n == 'Korea (Republic of)') {
        return 'South Korea';
    }
    if ($n == 'Virgin Islands (British)') {
        return 'British Virgin Islands';
    }
    if ($n == 'Virgin Islands (U.S.)') {
        return 'United States Virgin Islands';
    }
    if ($n == 'Holy See') {
        return 'Vatican City';
    }
    if ($n == 'Micronesia (Federated States of)') {
        return 'Federated States of Micronesia';
    }
    if ($n == 'Pitcairn') {
        return 'Pitcairn Islands';
    }

    return $n;
}

function write_csv($fileName, $list)
{
    $csv = League\Csv\Writer::createFromFileObject(new SplTempFileObject());

    $csv->insertOne(['alpha2', 'alpha3', 'number', 'name']);

    foreach ($list as $o) {
        $csv->insertOne([$o->alpha2, $o->alpha3, $o->number, $o->name]);
    }

    file_put_contents($fileName, $csv->__toString());
}

function write_json($fileName, $list)
{
    $data = json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($fileName, $data);
}


$res = (new MartinLindhe\MediawikiClient\Client)
    ->server('en.wikipedia.org')
    ->cacheTtlSeconds(3600) // 1 hour
    ->fetchArticle('ISO 3166-1');

$x = $res->data;

$start = "Link to [[ISO 3166-2]] subdivision codes"."\n"."|-"."\n";


$pos = strpos($x, $start);
if ($pos === false) {
    echo "ERROR: didn't find start\n";
    exit;
}

$pos += strlen($start);


$end = "\n"."|}";
$pos2 = strpos($x, $end, $pos);
if ($pos2 === false) {
    echo "ERROR: didnt find end\n";
    exit;
}

$data = substr($x, $pos, $pos2 - $pos);

$list = [];

$rows = explode("\n", $data);
for ($i = 0; $i < count($rows); $i++) {

    $rows[$i] = cleanText($rows[$i]);
    if (!$rows[$i]) {
        continue;
    }

    $cols = explode('||', $rows[$i]);
    if (count($cols) == 1) {
        $name = $rows[$i];
        $i++;
        $rows[$i] = cleanText($rows[$i]);
        $cols = explode('||', $rows[$i]);
    }

    $o = new \MartinLindhe\Data\Countries\Country;
    $o->alpha2 = getRightSideOfMediawikiTag($cols[0]);
    $o->alpha3 = getRightSideOfMediawikiTag($cols[1]);
    $o->number = getRightSideOfMediawikiTag($cols[2]);

    $name = cleanText($name);
    $name = getRightSideOfMediawikiTag(\MartinLindhe\MediawikiClient\Client::stripMediawikiLinks($name));

    $pos = mb_strpos($name, '/');
    if ($pos !== false) {
        $name = mb_substr($name, 0, $pos);
    }

    $pos = mb_strpos($name, '|');
    if ($pos !== false) {
        $name = mb_substr($name, $pos + 1);
    }

    $o->name = trim(translateName($name));

    $list[] = $o;
}

// aug 2015: Kosovo has a temporary "XK" code since 2010
$o = new \MartinLindhe\Data\Countries\Country;
$o->alpha2 = 'XK';
$o->alpha3 = 'XKO';
$o->number = '';
$o->name = 'Kosovo';
$list[] = $o;


write_csv(__DIR__.'/../data/countries.csv', $list);
write_json(__DIR__.'/../data/countries.json', $list);
