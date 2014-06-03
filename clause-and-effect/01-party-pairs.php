<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 1: party pairs

function male($person) {
    return conde([
        [eq($person, 'bertram')],
        [eq($person, 'percival')],
        [eq($person, 'apollo')],
        [eq($person, 'fido')],
    ]);
}

function female($person) {
    return conde([
        [eq($person, 'lucinda')],
        [eq($person, 'camilla')],
        [eq($person, 'daphne')],
    ]);
}

function dance_pair($a, $b) {
    return all([
        male($a),
        female($b),
    ]);
}

var_dump(run_star($q ==> dance_pair('percival', $q)));
var_dump(run_star($q ==> dance_pair('apollo', 'daphne')));
var_dump(run_star($q ==> dance_pair('camilla', $q)));
var_dump(run_star($q ==> dance_pair($q, $q)));
var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        dance_pair($a, $b),
        eq($q, [$a, $b]),
    ])));

// ok, now let's be a bit more inclusive
// fuck the patriarchy

function person($person) {
    return conde([
        [eq($person, 'bertram')],
        [eq($person, 'percival')],
        [eq($person, 'apollo')],
        [eq($person, 'fido')],
        [eq($person, 'lucinda')],
        [eq($person, 'camilla')],
        [eq($person, 'daphne')],
    ]);
}

function dance_pair_fixed($a, $b) {
    return all([
        person($a),
        person($b),
        neq($a, $b),
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        dance_pair_fixed($a, $b),
        eq($q, [$a, $b]),
    ])));

// that's better
