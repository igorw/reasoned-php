<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 3: affordable journeys

function border($a, $b) {
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

function adjacent($a, $b) {
    return conde([
        [border($a, $b)],
        [border($b, $a)],
    ]);
}

function affordable($a, $b) {
    return fresh($i ==>
        all([
            adjacent($a, $i),
            adjacent($i, $b),
        ]));
}

var_dump(run_star($q ==> affordable('wiltshire', 'sussex')));
var_dump(run_star($q ==> affordable('wiltshire', 'kent')));
var_dump(run_star($q ==> affordable('hampshire', 'hampshire')));
var_dump(run_star($q ==> affordable($q, 'kent')));
var_dump(run_star($q ==> affordable('sussex', $q)));
var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        affordable($a, $b),
        eq($q, [$a, $b]),
    ])));
