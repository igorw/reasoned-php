<?php

namespace igorw\reasoned;

require 'vendor/autoload.php';

var_dump(run_star(function ($x) {
    return conj(
        eq($x, 'a'),
        eq($x, 'b')
    );
}));

var_dump(run_star(function ($x) {
    return disj(
        eq($x, 'a'),
        eq($x, 'b')
    );
}));

var_dump(run_star(function ($x, $y) {
    return eq($x, $y);
}));

var_dump(run_star(function ($q, $a, $b) {
    return conj_plus([
        eq([$a, $b], ['a', 'b']),
        eq($q, [$a, $b]),
    ]);
}));

var_dump(run_star(function ($q, $a, $b) {
    return conj_plus([
        disj_plus([
            eq([$a, $b], ['a', 'b']),
            eq([$a, $b], ['b', 'a']),
        ]),
        eq($q, [$a, $b]),
    ]);
}));

var_dump(run_star(function ($q) {
    return disj_plus([
        eq($q, 'a'),
        eq($q, 'b'),
        eq($q, 'c'),
    ]);
}));

var_dump(run_star(function ($q) {
    return conde([
        [eq($q, 'a')],
        [eq($q, 'b')],
        [eq($q, 'c')],
    ]);
}));

var_dump(run_star(function ($q) {
    return eq($q, []);
}));

var_dump(run_star(function ($q) {
    return conde([
        [eq($q, [])],
        [fresh(function ($a, $d) use ($q) {
            return eq($q, pair($a, $d));
        })],
    ]);
}));

var_dump(run_star(function ($q) {
    return fresh(function ($a, $d) use ($q) {
        return all([
            eq([1, 2, 3], pair($a, $d)),
            eq($q, [$a, $d]),
        ]);
    });
}));

var_dump(run_star(function ($q) {
    return fresh(function ($a, $d) use ($q) {
        return all([
            eq([$a, 2, 3], pair(1, $d)),
            eq($q, [$a, $d]),
        ]);
    });
}));

function conso($a, $d, $l) {
    return eq(pair($a, $d), $l);
}

function membero($x, $l) {
    return conde([
        [fresh(function ($d) use ($x, $l) {
            return conso($x, $d, $l);
        })],
        [fresh(function ($a, $d) use ($x, $l) {
            return all([
                conso($a, $d, $l),
                membero($x, $d),
            ]);
        })],
    ]);
}

var_dump(run_star(function ($q) {
    return all([
        membero(7, [1, 2, 3]),
    ]);
}));

var_dump(run_star(function ($q) {
    return all([
        membero($q, [1, 2, 3]),
    ]);
}));

var_dump(run(1, function ($q) {
    return all([
        membero($q, [1, 2, 3]),
        membero($q, [3, 4, 5]),
    ]);
}));
