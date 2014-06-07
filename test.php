<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

class AssertionFailedException extends \UnexpectedValueException {}

function assertSame($expected, $value) {
    if ($expected !== $value) {
        throw new AssertionFailedException(sprintf("%s is not %s", json_encode($value), json_encode($expected)));
    }
}

assertSame([], run_star(function ($x) {
    return fail();
}));

assertSame(['_.0'], run_star(function ($x) {
    return succeed();
}));

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

// oleg numbers

assertSame([[0, 0], [1, 1]], run_star($s ==>
    fresh(($x, $y) ==>
        all([
            bit_xoro($x, $y, 0),
            eq([$x, $y], $s),
        ]))
));

assertSame([[1, 1]], run_star($s ==>
    fresh(($x, $y) ==>
        all([
            bit_ando($x, $y, 1),
            eq([$x, $y], $s),
        ]))
));

assertSame([0], run_star($r ==>
    half_addero(1, 1, $r, 1)
));

assertSame([[0, 1]], run_star(($s) ==>
    fresh(($r, $c) ==>
        all([
            full_addero(0, 1, 1, $r, $c),
            eq([$r, $c], $s),
        ]))
));

assertSame([true], run_star(($q) ==>
    all([
        poso([1]),
        eq(true, $q)
    ])
));

assertSame([], run_star(($q) ==>
    all([
        poso([]),
        eq(true, $q)
    ])
));

assertSame([['_.0', '.', '_.1']], run_star(($r) ==>
    poso($r)
));

// is 6 greater than one?
assertSame([true], run_star($q ==>
    all([
        gt1o([0, 1, 1]),
        eq(true, $q),
    ])
));

// 6 + 3 (+ 1 carry in) = 10
assertSame([[0, 1, 0, 1]], run_star($s ==>
    gen_addero(1, [0, 1, 1], [1, 1], $s)
));

// x + y = 5
assertSame(
    [[[1, 0, 1], []],
     [[], [1, 0, 1]],
     [[1], [0, 0, 1]]],
    run(3, $s ==>
        fresh(($x, $y) ==>
            all([
                addero(0, $x, $y, [1, 0, 1]),
                eq([$x, $y], $s),
            ])))
);

// x + y = 5, using pluso
assertSame(
    [[[1, 0, 1], []], [[], [1, 0, 1]], [[1], [0, 0, 1]]],
    run(3, $s ==>
        fresh(($x, $y) ==>
            all([
                pluso($x, $y, [1, 0, 1]),
                eq([$x, $y], $s),
            ])))
);

// 8 - 5 = 3
assertSame([[1, 1]], run_star($q ==>
    minuso([0, 0, 0, 1], [1, 0, 1], $q)
));

// 6 - 6 = 0
assertSame([[]], run_star($q ==>
    minuso([0, 1, 1], [0, 1, 1], $q)
));

// 6 - 8 => does not compute (negative numbers not supported)
assertSame([], run_star($q ==>
    minuso([0, 1, 1], [0, 0, 0, 1], $q)
));

assertSame([25], parse_nums(run_star($q ==>
    all([
        pluso(build_num(15), build_num(10), $q),
    ])
)));

// just a bit more

assertSame([[1, 0, 0, 1, 1, 1, 0, 1, 1]], run_star($q ==>
    timeso([1, 1, 1], [1, 1, 1, 1, 1, 1], $q)
));

assertSame([['_.0', '_.1', ['_.2', 1]]], run_star($q ==>
    fresh_all(($w, $x, $y) ==> [
        eq_lengtho(pair(1, pair($w, pair($x, $y))), [0, 1, 1, 0, 1]),
        eq([$w, $x, $y], $q),
    ])
));

assertSame([1], run_star($q ==>
    eq_lengtho([1], [$q])
));

assertSame([['_.0', 1]], run_star($q ==>
    eq_lengtho(pair(1, pair(0, pair(1, $q))), [0, 1, 1, 0, 1])
));

assertSame([[[], '_.0'], [[1], '_.0']], run(2, $q ==>
    fresh_all(($y, $z) ==> [
        lt_lengtho(pair(1, $y), pair(0, pair(1, pair(1, pair(0, pair(1, $z)))))),
        eq([$y, $z], $q),
    ])
));

assertSame([[[], []], [[], ['_.0', '.', '_.1']], [[1], [1]]], run(3, $q ==>
    fresh_all(($n, $m) ==> [
        lteq_lengtho($n, $m),
        eq([$n, $m], $q),
    ])
));

assertSame(['_.0'], run_star($q ==>
    lto([1, 0, 1], [1, 1, 1])
));

assertSame([], run_star($q ==>
    lto([1, 1, 1], [1, 0, 1])
));

assertSame([], run_star($q ==>
    lto([1, 0, 1], [1, 0, 1])
));

assertSame([[], [1], ['_.0', 1], [0, 0, 1]], run_star($q ==>
    lto($q, [1, 0, 1])
));

// TRS-8.52
// it has no value, since <o calls <lo
// assertSame([], run_star($q ==>
//     lto($q, $q)
// ));

assertSame([], run_star($q ==>
    fresh($r ==>
        divideo([1, 0, 1], $q, [1, 1, 1], $r))
));

assertSame([[0, 1, 1]], run_star($q ==>
    logo([0, 1, 1, 1], [0, 1], [1, 1], $q)
));

// increasing from run1 to run2 causes stack overflow
// after increasing the VM stack size, it works
//   hhvm -vEval.VMStackElms=65536 test.php
// but fails with run3.
assertSame(
    [
        [[],  ['_.0', '_.1', '.', '_.2'], [0, 0, 1, 0, 0, 0, 1]],
        // [[1], ['_.0', '_.1', '.', '_.2'], [1, 1, 0, 0, 0, 0, 1]],
    ],
    run(1, $s ==>
        fresh_all(($b, $q, $r) ==> [
            logo([0, 0, 1, 0, 0, 0, 1], $b, $q, $r),
            gt1o($q),
            eq([$b, $q, $r], $s),
        ])
    )
);

// causes stack overflow
// @todo figure out why!
// reducing the call stack might actually fix this
// assertSame([[1, 1, 0, 0, 1, 1, 1, 1]], run(1, $q ==>
//     expo([1, 1], [1, 0, 1], $q)
// ));
