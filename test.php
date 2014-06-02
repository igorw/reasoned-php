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

// disequality

assertSame(['_.0'], run_star(function ($q) {
    return neq(5, 6);
}));

assertSame([], run_star(function ($q) {
    return neq(5, 5);
}));

assertSame([1, 3], run_star(function ($q) {
    return all([
        membero($q, [1, 2, 3]),
        neq($q, 2),
    ]);
}));

assertSame(
    [
        [['_.0', '_.1'], ':-', ['!=', [['_.0', 5], ['_.1', 6]]]],
    ],
    run_star(function ($q) {
        return fresh(function ($x, $y) use ($q) {
            return all([
                neq([5, 6], [$x, $y]),
                eq($q, [$x, $y]),
            ]);
        });
    })
);

// rembero

function rembero($x, $l, $out) {
    return conde([
        [eq([], $l), eq([], $out)],
        [fresh(function ($a, $d) use ($x, $l, $out) {
            return all([
                eq(pair($a, $d), $l),
                eq($a, $x),
                eq($d, $out),
            ]);
         })],
        [fresh(function ($a, $d, $res) use ($x, $l, $out) {
            return all([
                eq(pair($a, $d), $l),
                neq($a, $x),
                eq(pair($a, $res), $out),
                rembero($x, $d, $res),
            ]);
         })],
    ]);
}

assertSame([['a', 'c', 'b', 'd']], run_star(function ($q) {
    return rembero('b', ['a', 'b', 'c', 'b', 'd'], $q);
}));

assertSame([], run_star(function ($q) {
    return rembero('b', ['b'], ['b']);
}));

assertSame(
    [
        ['a', ['b', 'c']],
        ['b', ['a', 'c']],
        ['c', ['a', 'b']],
        [['_.0', ['a', 'b', 'c']], ':-', ['!=', [['_.0', 'a']], [['_.0', 'b']], [['_.0', 'c']]]],
    ],
    run_star(function ($q) {
        return fresh(function ($x, $out) use ($q) {
            return all([
                rembero($x, ['a', 'b', 'c'], $out),
                eq([$x, $out], $q),
            ]);
        });
    })
);

// occurs check

assertSame([], run_star(function ($q) {
    return eq($q, [$q]);
}));

// neq order

assertSame([], run_star(function ($q) {
    return fresh(function ($a, $b) use ($q) {
        return all([
            eq($a, 'mary'),
            eq($b, 'mary'),
            neq($a, $b),
            eq($q, [$a, $b]),
        ]);
    });
}));

assertSame([], run_star(function ($q) {
    return fresh(function ($a, $b) use ($q) {
        return all([
            neq($a, $b),
            eq($a, 'mary'),
            eq($b, 'mary'),
            eq($q, [$a, $b]),
        ]);
    });
}));
