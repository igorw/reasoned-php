<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 3: affordable journeys

function bordero($a, $b) {
    return conde([
        [eq($a, 'sussex'), eq($b, 'kent')],
        [eq($a, 'sussex'), eq($b, 'surrey')],
        [eq($a, 'surrey'), eq($b, 'kent')],
        [eq($a, 'hampshire'), eq($b, 'sussex')],
        [eq($a, 'hampshire'), eq($b, 'surrey')],
        [eq($a, 'hampshire'), eq($b, 'berkshire')],
        [eq($a, 'berkshire'), eq($b, 'surrey')],
        [eq($a, 'wiltshire'), eq($b, 'hampshire')],
        [eq($a, 'wiltshire'), eq($b, 'berkshire')],
    ]);
}

function adjacento($a, $b) {
    return conde([
        [bordero($a, $b)],
        [bordero($b, $a)],
    ]);
}

function affordableo($a, $b) {
    return fresh($i ==>
        all([
            adjacento($a, $i),
            adjacento($i, $b),
        ]));
}

var_dump(run_star($q ==> affordableo('wiltshire', 'sussex')));
var_dump(run_star($q ==> affordableo('wiltshire', 'kent')));
var_dump(run_star($q ==> affordableo('hampshire', 'hampshire')));
var_dump(run_star($q ==> affordableo($q, 'kent')));
var_dump(run_star($q ==> affordableo('sussex', $q)));
var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        affordableo($a, $b),
        eq($q, [$a, $b]),
    ])));
