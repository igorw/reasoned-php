<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 9: searching a cyclic graph

// this is more tricky than the acyclic one, because
// we need to avoid divergence

function grapho($a, $b) {
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

function naive_patho($a, $b) {
    return conde([
        [eq($a, $b)],
        [fresh_all($i ==> [
            grapho($a, $i),
            naive_patho($i, $b),
         ])],
    ]);
}

// diverges to infinite loop
// var_dump(run_star($q ==>
//     fresh(($a, $b) ==>
//         naive_path($a, $b))));

function patho($a, $b, $t) {
    return conde([
        [eq($a, $a)],
        [fresh_all(($z, $t2) ==> [
            grapho($a, $b),
            legalo($z, $t),
            conso($z, $t, $t2),
            patho($z, $b, $t2)])],
    ]);
}

function legalo($z, $l) {
    return conde([
        [eq($l, [])],
        [fresh_all(($a, $d) ==> [
            conso($a, $d, $l),
            neq($z, $a),
            legalo($z, $d)])],
    ]);
}

var_dump(run_star($q ==> patho('g', 'c', [])));
var_dump(run_star($q ==> patho('g', 'c', ['f'])));
var_dump(run_star($q ==> patho('a', $q, ['f', 'd'])));
