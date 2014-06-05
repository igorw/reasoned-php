<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 2: drinking pairs

function drinkso($person, $drink) {
    return conde([
        [eq($person, 'john'), eq($drink, 'martini')],
        [eq($person, 'mary'), eq($drink, 'gin')],
        [eq($person, 'susan'), eq($drink, 'vodka')],
        [eq($person, 'john'), eq($drink, 'gin')],
        [eq($person, 'fred'), eq($drink, 'gin')],
    ]);
}

function drink_pairo($a, $b, $drink) {
    return all([
        drinkso($a, $drink),
        drinkso($b, $drink),
        // disequality constraint prevents drinking with mirror
        neq($a, $b),
    ]);
}

var_dump(run_star($q ==> drink_pairo($q, 'john', 'martini')));
var_dump(run_star($q ==> drink_pairo('mary', 'susan', 'gin')));
var_dump(run_star($q ==> drink_pairo('john', 'mary', 'gin')));
var_dump(run_star($q ==> drink_pairo('john', 'john', 'gin')));
var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        drink_pairo($a, $b, 'gin'),
        eq($q, [$a, $b]),
    ])));
var_dump(run_star($q ==> drink_pairo('bertram', 'lucinda', 'vodka')));
var_dump(run_star($q ==>
    fresh_all(($a, $b, $drink) ==> [
        drink_pairo($a, $b, $drink),
        eq($q, [$a, $b, $drink]),
    ])));
