<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// a bit too much (the reasoned schemer chapter 7)
//
// this is a relational number system (relational meaning based on
// logical relations).
//
// it works by implementing logic gates, as you would find them in
// an ALU. the logic gates form a half-adder, and two half adders
// form a full adder, which is capable of adding two bits with
// carry-in and carry-out.
//
// the actual addero composes n full adders (n being the amount of
// bits required) to do complete binary arithmetic.
//
// pluso is addero with carry-in set to 0. it adds two numbers.
// minuso is pluso with inverted arguments. it implements subtraction
// in terms of addition, simply by flipping the arguments.
//
// the representation of an oleg number is a little-endian list of
// bits. essentially a reversed list of binary digits.
//
// 0 = ()
// 1 = (1)
// 2 = (0 1)
// 3 = (1 1)
// 4 = (0 0 1)
// 5 = (1 0 1)
// 6 = (0 1 1)
// 7 = (1 1 1)
// ...
//
// note: running this file requires HHVM

function bit_xoro($x, $y, $r) {
    return conde([
        [eq(0, $x), eq(0, $y), eq(0, $r)],
        [eq(1, $x), eq(0, $y), eq(1, $r)],
        [eq(0, $x), eq(1, $y), eq(1, $r)],
        [eq(1, $x), eq(1, $y), eq(0, $r)],
    ]);
}

var_dump(run_star($s ==>
    fresh(($x, $y) ==>
        all([
            bit_xoro($x, $y, 0),
            eq([$x, $y], $s),
        ]))
));

function bit_ando($x, $y, $r) {
    return conde([
        [eq(0, $x), eq(0, $y), eq(0, $r)],
        [eq(1, $x), eq(0, $y), eq(0, $r)],
        [eq(0, $x), eq(1, $y), eq(0, $r)],
        [eq(1, $x), eq(1, $y), eq(1, $r)],
    ]);
}

var_dump(run_star($s ==>
    fresh(($x, $y) ==>
        all([
            bit_ando($x, $y, 1),
            eq([$x, $y], $s),
        ]))
));

function half_addero($x, $y, $r, $c) {
    return all([
        bit_xoro($x, $y, $r),
        bit_ando($x, $y, $c),
    ]);
}

var_dump(run_star($r ==>
    half_addero(1, 1, $r, 1)
));

function full_addero($b, $x, $y, $r, $c) {
    return fresh(($w, $xy, $wz) ==>
        all([
            half_addero($x, $y, $w, $xy),
            half_addero($w, $b, $r, $wz),
            bit_xoro($xy, $wz, $c),
        ])
    );
}

var_dump(run_star(($s) ==>
    fresh(($r, $c) ==>
        all([
            full_addero(0, 1, 1, $r, $c),
            eq([$r, $c], $s),
        ]))
));

function build_num($n) {
    if ($n === 0) {
        return [];
    }
    if ($n > 0 && $n % 2 === 0) {
        return cons(0, build_num($n / 2));
    }
    if ($n % 2 === 1) {
        return cons(1, build_num(($n - 1) / 2));
    }
}

function poso($n) {
    return fresh(($a, $d) ==>
        eq(pair($a, $d), $n)
    );
}

var_dump(run_star(($q) ==>
    all([
        poso([1]),
        eq(true, $q)
    ])
));

var_dump(run_star(($q) ==>
    all([
        poso([]),
        eq(true, $q)
    ])
));

var_dump(run_star(($r) ==>
    poso($r)
));

// >1o
function gt1o($n) {
    return fresh(($a, $ad, $dd) ==>
        // (== `(,a ,ad . ,dd) n)
        eq(pair($a, pair($ad, $dd)), $n)
    );
}

// is 6 greater than one?
var_dump(run_star($q ==>
    all([
        gt1o([0, 1, 1]),
        eq(true, $q),
    ])
));

// d = carry in, n = operand1, m = operand2, r = result
function addero($d, $n, $m, $r) {
    return fresh(() ==>
        conde([
            [eq(0, $d), eq([], $m), eq($n, $r)],
            [eq(0, $d), eq([], $n), eq($m, $r), poso($m)],
            [eq(1, $d), eq([], $m), addero(0, $n, [1], $r)],
            [eq(1, $d), eq([], $n), poso($m), addero(0, [1], $m, $r)],
            [eq([1], $n), eq([1], $m),
             fresh(($a, $c) ==>
                all([
                    eq([$a, $c], $r),
                    full_addero($d, 1, 1, $a, $c)
                ]))],
            [eq([1], $n), gen_addero($d, $n, $m, $r)],
            [eq([1], $m), gt1o($n), gt1o($r), addero($d, [1], $n, $r)],
            [gt1o($n), gen_addero($d, $n, $m, $r)],
        ])
    );
}

function gen_addero($d, $n, $m, $r) {
    return fresh(($a, $b, $c, $e, $x, $y, $z) ==>
        all([
            eq(pair($a, $x), $n),
            eq(pair($b, $y), $m),
            poso($y),
            eq(pair($c, $z), $r),
            poso($z),
            full_addero($d, $a, $b, $c, $e),
            addero($e, $x, $y, $z),
        ]));
}

// 6 + 3 (+ 1 carry in) = 10
var_dump(run_star($s ==>
    gen_addero(1, [0, 1, 1], [1, 1], $s)
));

// x + y = 5
var_dump(run_star($s ==>
    fresh(($x, $y) ==>
        all([
            addero(0, $x, $y, [1, 0, 1]),
            eq([$x, $y], $s),
        ]))
));

function pluso($n, $m, $k) {
    return addero(0, $n, $m, $k);
}

// x + y = 5, using pluso
var_dump(run_star($s ==>
    fresh(($x, $y) ==>
        all([
            pluso($x, $y, [1, 0, 1]),
            eq([$x, $y], $s),
        ]))
));

function minuso($n, $m, $k) {
    return pluso($m, $k, $n);
}

// 8 - 5 = 3
var_dump(run_star($q ==>
    minuso([0, 0, 0, 1], [1, 0, 1], $q)
));

// 6 - 6 = 0
var_dump(run_star($q ==>
    minuso([0, 1, 1], [0, 1, 1], $q)
));

// 6 - 8 => does not compute (negative numbers not supported)
var_dump(run_star($q ==>
    minuso([0, 1, 1], [0, 0, 0, 1], $q)
));

function parse_num(array $n) {
    return (int) base_convert(implode('', array_reverse($n)), 2, 10);
}

function parse_nums($nums) {
    return array_map($n ==> parse_num($n), $nums);
}

var_dump(parse_nums(run_star($q ==>
    all([
        pluso(build_num(15), build_num(10), $q),
    ])
)));
