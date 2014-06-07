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
             fresh_all(($a, $c) ==> [
                eq([$a, $c], $r),
                full_addero($d, 1, 1, $a, $c)
             ])],
            [eq([1], $n), gen_addero($d, $n, $m, $r)],
            [eq([1], $m), gt1o($n), gt1o($r), addero($d, [1], $n, $r)],
            [gt1o($n), gen_addero($d, $n, $m, $r)],
        ])
    );
}

function gen_addero($d, $n, $m, $r) {
    return fresh_all(($a, $b, $c, $e, $x, $y, $z) ==> [
        eq(pair($a, $x), $n),
        eq(pair($b, $y), $m),
        poso($y),
        eq(pair($c, $z), $r),
        poso($z),
        full_addero($d, $a, $b, $c, $e),
        addero($e, $x, $y, $z),
    ]);
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

// just a bit more (the reasoned schemer chapter 8)

function timeso($n, $m, $p) {
    return conde([
        [eq([], $n), eq([], $p)],
        [poso($n), eq([], $m), eq([], $p)],
        [eq([1], $n), poso($m), eq($m, $p)],
        [gt1o($n), eq([1], $m), eq($n, $p)],
        [fresh_all(($x, $z) ==> [
            eq(pair(0, $x), $n),
            poso($x),
            eq(pair(0, $z), $p),
            poso($m),
            gt1o($m),
            timeso($x, $m, $z)])],
        [fresh_all(($x, $y) ==> [
            eq(pair(1, $x), $n),
            poso($x),
            eq(pair(0, $y), $m),
            poso($y),
            timeso($m, $n, $p)])],
        [fresh_all(($x, $y) ==> [
            eq(pair(1, $x), $n),
            poso($x),
            eq(pair(1, $y), $m),
            poso($y),
            odd_timeso($x, $n, $m, $p)])],
    ]);
}

function odd_timeso($x, $n, $m, $p) {
    return fresh_all($q ==> [
        bound_timeso($q, $p, $n, $m),
        timeso($x, $m, $q),
        pluso(pair(0, $q), $m, $p),
    ]);
}

function bound_timeso($q, $p, $n, $m) {
    return conde([
        [nullo($q), pairo($p)],
        [fresh_all(($x, $y, $z) ==> [
            resto($q, $x),
            resto($p, $y),
            conde([
                [nullo($n),
                 resto($m, $z),
                 bound_timeso($x, $y, $z, [])],
                [resto($n, $z),
                 bound_timeso($x, $y, $z, $m)],
            ])])],
    ]);
}

function nullo($l) {
    return eq($l, []);
}

function pairo($l) {
    return fresh(($a, $d) ==>
        conso($a, $d, $l));
}

// =lo
function eq_lengtho($n, $m) {
    return conde([
        [eq([], $n), eq([], $m)],
        [eq([1], $n), eq([1], $m)],
        [fresh_all(($a, $x, $b, $y) ==> [
            eq(pair($a, $x), $n),
            poso($x),
            eq(pair($b, $y), $m),
            poso($y),
            eq_lengtho($x, $y)])],
    ]);
}

// <lo
function lt_lengtho($n, $m) {
    return conde([
        [eq([], $n), poso($m)],
        [eq([1], $n), gt1o($m)],
        [fresh_all(($a, $x, $b, $y) ==> [
            eq(pair($a, $x), $n),
            poso($x),
            eq(pair($b, $y), $m),
            poso($y),
            lt_lengtho($x, $y)])],
    ]);
}

// <=lo
function lteq_lengtho($n, $m) {
    return conde([
        [eq_lengtho($n, $m)],
        [lt_lengtho($n, $m)],
    ]);
}

// <o
function lto($n, $m) {
    return conde([
        [lt_lengtho($n, $m)],
        [eq_lengtho($n, $m),
         fresh_all($x ==> [
            poso($x),
            pluso($n, $x, $m)])],
    ]);
}

// <=o
function lteqo($n, $m) {
    return conde([
        [eq($n, $m)],
        [lto($n, $m)],
    ]);
}

// hold on! it's going to get subtle!
// ffs, no kidding...

function splito($n, $r, $l, $h) {
    return conde([
        [eq([], $n), eq([], $h), eq([], $l)],
        [fresh_all(($b, $nhat) ==> [
            eq(pair(0, pair($b, $nhat)), $n),
            eq([], $r),
            eq(pair($b, $nhat), $h),
            eq([], $l)])],
        [fresh_all($nhat ==> [
            eq(pair(1, $nhat), $n),
            eq([], $r),
            eq($nhat, $h),
            eq([1], $l)])],
        [fresh_all(($b, $nhat, $a, $rhat) ==> [
            eq(pair(0, pair($b, $nhat)), $n),
            eq(pair($a, $rhat), $r),
            eq([], $l),
            splito(pair($b, $nhat), $rhat, [], $h)])],
        [fresh_all(($nhat, $a, $rhat) ==> [
            eq(pair(1, $nhat), $n),
            eq(pair($a, $rhat), $r),
            eq([1], $l),
            splito($nhat, $rhat, [], $h)])],
        [fresh_all(($b, $nhat, $a, $rhat, $lhat) ==> [
            eq(pair($b, $nhat), $n),
            eq(pair($a, $rhat), $r),
            eq(pair($b, $lhat), $l),
            poso($lhat),
            splito($nhat, $rhat, $lhat, $h)])],
    ]);
}

// /o
function divideo($n, $m, $q, $r) {
    return conde([
        [eq($r, $n), eq([], $q), lto($n, $m)],
        [eq([1], $q), eq_lengtho($n, $m), pluso($r, $m, $n), lto($r, $m)],
        [lt_lengtho($m, $n),
         lto($r, $m),
         poso($q),
         fresh_all(($nh, $nl, $qh, $ql, $qlm, $qlmr, $rr, $rh) ==> [
            splito($n, $r, $nl, $nh),
            splito($q, $r, $ql, $qh),
            conde([
                [eq([], $nh),
                 eq([], $qh),
                 minuso($nl, $r, $qlm),
                 timeso($ql, $m, $qlm)],
                [poso($nh),
                 timeso($ql, $m, $qlm),
                 pluso($qlm, $r, $qlmr),
                 minuso($qlmr, $nl, $rr),
                 splito($rr, $r, [], $rh),
                 divideo($nh, $m, $qh, $rh)],
            ])])],
    ]);
}
