<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// peano arithmetic
// based on nada amin's "From Greek to Clojure"

function peanoo($n) {
    return conde([
        [eq($n, 'z')],
        [fresh($m ==> all([
            eq($n, ['s', $m]),
            peanoo($m),
         ]))],
    ]);
}

function pluso($a, $b, $out) {
    return all([
        peanoo($a),
        peanoo($b),
        peanoo($out),
        conde([
            [eq($a, 'z'), eq($out, $b)],
            [fresh(($c, $d) ==> all([
                eq($a, ['s', $c]),
                eq($out, ['s', $d]),
                pluso($c, $b, $d),
             ]))],
        ]),
    ]);
}

// var_dump(run(10, $q ==> peanoo($q)));
// var_dump(run(1, $q ==> pluso(['s', 'z'], ['s', 'z'], $q)));
// var_dump(run(2, $q ==> pluso(['s', 'z'], ['s', 'z'], $q)));
// var_dump(run(10, $q ==>
//     fresh(($x, $y, $z) ==>
//         all([
//             pluso($x, $y, $z),
//             eq($q, [$x, $y, $z]),
//         ]))));
// var_dump(run(3, $q ==>
//     fresh(($x, $y) ==>
//         all([
//             pluso($x, $y, ['s', ['s', ['s', 'z']]]),
//             eq($q, [$x, $y]),
//         ]))));
