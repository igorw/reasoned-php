<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 9: searching a cyclic graph

function a($a, $b) {
    return conde([
        [eq($a, 'g'), eq($b, 'h')],
        [eq($a, 'd'), eq($b, 'a')],
        [eq($a, 'g'), eq($b, 'd')],
        [eq($a, 'e'), eq($b, 'd')],
        [eq($a, 'h'), eq($b, 'f')],
        [eq($a, 'e'), eq($b, 'f')],
        [eq($a, 'a'), eq($b, 'e')],
        [eq($a, 'a'), eq($b, 'b')],
        [eq($a, 'b'), eq($b, 'f')],
        [eq($a, 'b'), eq($b, 'c')],
        [eq($a, 'f'), eq($b, 'c')],
    ]);
}

function naive_path($a, $b) {
    return conde([
        [eq($a, $b)],
        [fresh_all($i ==> [
            a($a, $i),
            path($i, $b),
         ])],
    ]);
}

// diverges to infinite loop
// var_dump(run_star($q ==>
//     fresh(($a, $b) ==>
//         naive_path($a, $b))));

function path($a, $b, $t) {
    return conde([
        [eq($a, $a)],
        [fresh_all(($z, $t2) ==> [
            a($a, $b),
            legal($z, $t),
            conso($z, $t, $t2),
            path($z, $b, $t2)])],
    ]);
}

function legal($z, $l) {
    return conde([
        [eq($l, [])],
        [fresh_all(($a, $d) ==> [
            conso($a, $d, $l),
            neq($z, $a),
            legal($z, $d)])],
    ]);
}

var_dump(run_star($q ==> path('g', 'c', [])));
var_dump(run_star($q ==> path('g', 'c', ['f'])));
var_dump(run_star($q ==> path('a', $q, ['f', 'd'])));
