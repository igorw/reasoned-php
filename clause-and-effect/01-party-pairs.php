<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 1: party pairs

function maleo($person) {
    return conde([
        [eq($person, 'bertram')],
        [eq($person, 'percival')],
        [eq($person, 'apollo')],
        [eq($person, 'fido')],
    ]);
}

function femaleo($person) {
    return conde([
        [eq($person, 'lucinda')],
        [eq($person, 'camilla')],
        [eq($person, 'daphne')],
    ]);
}

function dance_pairo($a, $b) {
    return all([
        maleo($a),
        femaleo($b),
    ]);
}

var_dump(run_star($q ==> dance_pairo('percival', $q)));
var_dump(run_star($q ==> dance_pairo('apollo', 'daphne')));
var_dump(run_star($q ==> dance_pairo('camilla', $q)));
var_dump(run_star($q ==> dance_pairo($q, $q)));
var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        dance_pairo($a, $b),
        eq($q, [$a, $b]),
    ])));

// ok, now let's be a bit more inclusive
// fuck the patriarchy

function persono($person) {
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

function dance_pair_fixedo($a, $b) {
    return all([
        persono($a),
        persono($b),
        neq($a, $b),
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($a, $b) ==> [
        dance_pair_fixedo($a, $b),
        eq($q, [$a, $b]),
    ])));

// that's better
