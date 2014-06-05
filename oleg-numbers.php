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

function bit_ando($x, $y, $r) {
    return conde([
        [eq(0, $x), eq(0, $y), eq(0, $r)],
        [eq(1, $x), eq(0, $y), eq(0, $r)],
        [eq(0, $x), eq(1, $y), eq(0, $r)],
        [eq(1, $x), eq(1, $y), eq(1, $r)],
    ]);
}

function half_addero($x, $y, $r, $c) {
    return all([
        bit_xoro($x, $y, $r),
        bit_ando($x, $y, $c),
    ]);
}

function full_addero($b, $x, $y, $r, $c) {
    return fresh(($w, $xy, $wz) ==>
        all([
            half_addero($x, $y, $w, $xy),
            half_addero($w, $b, $r, $wz),
            bit_xoro($xy, $wz, $c),
        ])
    );
}

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

// >1o
function gt1o($n) {
    return fresh(($a, $ad, $dd) ==>
        // (== `(,a ,ad . ,dd) n)
        eq(pair($a, pair($ad, $dd)), $n)
    );
}

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

function pluso($n, $m, $k) {
    return addero(0, $n, $m, $k);
}

function minuso($n, $m, $k) {
    return pluso($m, $k, $n);
}

function parse_num(array $n) {
    return (int) base_convert(implode('', array_reverse($n)), 2, 10);
}

function parse_nums($nums) {
    return array_map($n ==> parse_num($n), $nums);
}
