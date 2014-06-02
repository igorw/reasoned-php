<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 2: drinking pairs

function drinks($person, $drink) {
    return conde([
        [eq($person, 'john'), eq($drink, 'martini')],
        [eq($person, 'mary'), eq($drink, 'gin')],
        [eq($person, 'susan'), eq($drink, 'vodka')],
        [eq($person, 'john'), eq($drink, 'gin')],
        [eq($person, 'fred'), eq($drink, 'gin')],
    ]);
}

function drink_pair($a, $b, $drink) {
    return all([
        drinks($a, $drink),
        drinks($b, $drink),
        // disequality constraint prevents drinking with mirror
        neq($a, $b),
    ]);
}

var_dump(run_star($q ==> drink_pair($q, 'john', 'martini')));
var_dump(run_star($q ==> drink_pair('mary', 'susan', 'gin')));
var_dump(run_star($q ==> drink_pair('john', 'mary', 'gin')));
var_dump(run_star($q ==> drink_pair('john', 'john', 'gin')));
var_dump(run_star($q ==>
    fresh(($a, $b) ==> all([
        drink_pair($a, $b, 'gin'),
        eq($q, [$a, $b]),
    ]))));
var_dump(run_star($q ==> drink_pair('bertram', 'lucinda', 'vodka')));
var_dump(run_star($q ==>
    fresh(($a, $b, $drink) ==> all([
        drink_pair($a, $b, $drink),
        eq($q, [$a, $b, $drink]),
    ]))));
