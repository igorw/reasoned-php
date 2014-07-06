<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// chapter 6: case study: term rewriting

// @todo replace tagged values with better constraints
function atomic($x) {
    return fresh($v ==>
        eq(v($v), $x));
}

// tagged atomic value
function v($v) {
    return ['v', $v];
}

// 6.1 symbolic differentiation

function derivativo($expr, $x, $out) {
    return conde([
        [eq($expr, $x), eq($out, v(1))],
        [neq($expr, $x), atomic($expr), eq($out, v(0))],
        [fresh_all(($a, $u) ==> [
            eq(['-', $a], $expr),
            eq(['-', $u], $out),
            derivativo($a, $x, $u),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['+', $a, $b], $expr),
            eq(['+', $u, $v], $out),
            derivativo($a, $x, $u),
            derivativo($b, $x, $v),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['-', $a, $b], $expr),
            eq(['-', $u, $v], $out),
            derivativo($a, $x, $u),
            derivativo($b, $x, $v),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['*', $a, $b], $expr),
            eq(['+', ['*', $b, $u], ['*', $a, $v]], $out),
            derivativo($a, $x, $u),
            derivativo($b, $x, $v),
         ])],
    ]);
}

// @todo 6.2 matrix products by symbolic algebra

// 6.3 the simplifier

function simplify_expro($expr, $out) {
    return conde([
        [fresh_all(($a, $a1, $b, $b1) ==> [
            eq(['+', $a, $b], $expr),
            simplify_expro($a, $a1),
            simplify_expro($b, $b1),
            op(['+', $a1, $b1], $out),
         ])],
        [fresh_all(($a, $a1, $b, $b1) ==> [
            eq(['-', $a, $b], $expr),
            simplify_expro($a, $a1),
            simplify_expro($b, $b1),
            op(['-', $a1, $b1], $out),
         ])],
        [fresh_all(($a, $a1, $b, $b1) ==> [
            eq(['*', $a, $b], $expr),
            simplify_expro($a, $a1),
            simplify_expro($b, $b1),
            op(['*', $a1, $b1], $out),
         ])],
        [fresh_all(($a, $b) ==> [
            neq(['+', $a, $b], $expr),
            neq(['-', $a, $b], $expr),
            neq(['*', $a, $b], $expr),
            eq($expr, $out),
         ])],
    ]);
}

function op($expr, $out) {
    return conde([
        // @todo could rewrite using oleg numbers
        //       to perform actual addition
        // [fresh_all(($a, $b) ==> [
        //     eq(['+', $a, $b], $expr),
        //     numbero($a),
        //     numbero($b),
        //     pluso($a, $b, $out),
        //  ])],
        [fresh_all($a ==> [
            eq(['+', v(0), $a], $expr),
            eq($a, $out),
         ])],
        [fresh_all($a ==> [
            eq(['+', $a, v(0)], $expr),
            eq($a, $out),
         ])],
        [fresh_all($a ==> [
            eq(['*', v(1), $a], $expr),
            eq($a, $out),
         ])],
        [fresh_all($a ==> [
            eq(['*', v(0), $a], $expr),
            eq(v(0), $out),
         ])],
        [fresh_all($a ==> [
            eq(['*', $a, v(1)], $expr),
            eq($a, $out),
         ])],
        [fresh_all($a ==> [
            eq(['*', $a, v(0)], $expr),
            eq(v(0), $out),
         ])],
        [fresh_all($a ==> [
            eq(['-', $a, v(0)], $expr),
            eq($a, $out),
         ])],
        [fresh_all($a ==> [
            eq(['-', $a, $a], $expr),
            eq(v(0), $out),
         ])],
        [fresh_all($a ==> [
            neq(['+', v(0), $a], $expr),
            neq(['+', $a, v(0)], $expr),
            neq(['*', v(1), $a], $expr),
            neq(['*', v(0), $a], $expr),
            neq(['*', $a, v(1)], $expr),
            neq(['*', $a, v(0)], $expr),
            neq(['-', $a, v(0)], $expr),
            neq(['-', $a, $a], $expr),
            eq($expr, $out),
         ])],
    ]);
}

// distribute negations using DeMorgan's Laws
function distribute_nego($expr, $out) {
    return conde([
        [fresh_all($a ==> [
            eq(['-', ['-', $a]], $expr),
            distribute_nego($a, $out),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['-', ['+', $a, $b]], $expr),
            eq(['+', $u, $v], $out),
            distribute_nego(['-', $a], $u),
            distribute_nego(['-', $b], $v),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['-', ['*', $a, $b]], $expr),
            eq(['*', $u, $v], $out),
            distribute_nego(['-', $a], $u),
            distribute_nego($b, $v),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['+', $a, $b], $expr),
            eq(['+', $u, $v], $out),
            distribute_nego($a, $u),
            distribute_nego($b, $v),
         ])],
        [fresh_all(($a, $b, $u, $v) ==> [
            eq(['*', $a, $b], $expr),
            eq(['*', $u, $v], $out),
            distribute_nego($a, $u),
            distribute_nego($b, $v),
         ])],
        [fresh_all(($a, $b) ==> [
            neq(['-', ['-', $a]], $expr),
            neq(['-', ['+', $a, $b]], $expr),
            neq(['-', ['*', $a, $b]], $expr),
            neq(['+', $a, $b], $expr),
            neq(['*', $a, $b], $expr),
            eq($expr, $out),
         ])],
    ]);
}

function simplifyo($expr, $out) {
    return fresh_all($a ==> [
        distribute_nego($expr, $a),
        simplify_expro($a, $out),
    ]);
}

echo json_encode(run_star($q ==>
    derivativo(v(0), v('x'), $q)))."\n";
echo json_encode(run_star($q ==>
    derivativo(v('x'), v('x'), $q)))."\n";
echo json_encode(run_star($q ==>
    derivativo(['-', ['*', v('x'), v('x')], v(2)], v('x'), $q)))."\n";

// @todo figure out why it is spitting out so many answers, we only want the shortest
echo json_encode(run_star($q ==>
    simplify_expro(['-', v(1), v(0)], $q)))."\n";
echo json_encode(run_star($q ==>
    simplifyo(['-', ['+', ['*', v('x'), v(1)], ['*', v('x'), v(1)]], v(0)], $q)))."\n";
