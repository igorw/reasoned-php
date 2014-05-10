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
