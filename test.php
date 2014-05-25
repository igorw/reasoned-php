<?php

namespace igorw\reasoned;

require 'vendor/autoload.php';

class AssertionFailedException extends \UnexpectedValueException {}

function assertSame($expected, $value) {
    if ($expected !== $value) {
        throw new AssertionFailedException(sprintf("%s is not %s", json_encode($value), json_encode($expected)));
    }
}

assertSame([], run_star(function ($x) {
    return conj(
        eq($x, 'a'),
        eq($x, 'b')
    );
}));

assertSame(['a', 'b'], run_star(function ($x) {
    return disj(
        eq($x, 'a'),
        eq($x, 'b')
    );
}));

assertSame(['_.0'], run_star(function ($x, $y) {
    return eq($x, $y);
}));

assertSame([['a', 'b']], run_star(function ($q, $a, $b) {
    return conj_plus([
        eq([$a, $b], ['a', 'b']),
        eq($q, [$a, $b]),
    ]);
}));

assertSame([['a', 'b'], ['b', 'a']], run_star(function ($q, $a, $b) {
    return conj_plus([
        disj_plus([
            eq([$a, $b], ['a', 'b']),
            eq([$a, $b], ['b', 'a']),
        ]),
        eq($q, [$a, $b]),
    ]);
}));

assertSame(['a', 'b', 'c'], run_star(function ($q) {
    return disj_plus([
        eq($q, 'a'),
        eq($q, 'b'),
        eq($q, 'c'),
    ]);
}));

assertSame(['a', 'b', 'c'], run_star(function ($q) {
    return conde([
        [eq($q, 'a')],
        [eq($q, 'b')],
        [eq($q, 'c')],
    ]);
}));

assertSame([[]], run_star(function ($q) {
    return eq($q, []);
}));

assertSame([[], ['_.0', '.', '_.1']], run_star(function ($q) {
    return conde([
        [eq($q, [])],
        [fresh(function ($a, $d) use ($q) {
            return eq($q, pair($a, $d));
        })],
    ]);
}));

assertSame([[1, [2, 3]]], run_star(function ($q) {
    return fresh(function ($a, $d) use ($q) {
        return all([
            eq([1, 2, 3], pair($a, $d)),
            eq($q, [$a, $d]),
        ]);
    });
}));

assertSame([[1, [2, 3]]], run_star(function ($q) {
    return fresh(function ($a, $d) use ($q) {
        return all([
            eq([$a, 2, 3], pair(1, $d)),
            eq($q, [$a, $d]),
        ]);
    });
}));

function membero($x, $l) {
    return conde([
        [firsto($l, $x)],
        [fresh(function ($d) use ($x, $l) {
            return all([
                resto($l, $d),
                membero($x, $d),
            ]);
        })],
    ]);
}

assertSame([], run_star(function ($q) {
    return all([
        membero(7, [1, 2, 3]),
    ]);
}));

assertSame([1, 2, 3], run_star(function ($q) {
    return all([
        membero($q, [1, 2, 3]),
    ]);
}));

assertSame([3], run_star(function ($q) {
    return all([
        membero($q, [1, 2, 3]),
        membero($q, [3, 4, 5]),
    ]);
}));

assertSame([2, 3], run_star(function ($q) {
    return all([
        membero($q, [1, 2, 3]),
        membero($q, [2, 3, 4]),
    ]);
}));

// unicode

assertSame(['unicode', 'madness'], run٭(
    $q ==> condᵉ([
        [≡($q, 'unicode')],
        [≡($q, 'madness')],
    ])
));

// pair reification

assertSame([['tofu', '.', '_.0'], ['_.0', 'tofu', '.', '_.1'], ['_.0', '_.1', 'tofu', '.', '_.2']], run(3, function ($q) {
    return membero('tofu', $q);
}));

assertSame([[1, 2, 3, 4, 5, 6]], run_star(function ($q) {
    return appendo([1, 2, 3], [4, 5, 6], $q);
}));
