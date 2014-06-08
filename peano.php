<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// peano arithmetic
//
// based on Nada Amin and William Byrd's "From Greek to Clojure"
// https://www.youtube.com/watch?v=7kPMFkNm2dw
// https://github.com/namin/grk2clj
//
// this is an example of the natural numbers described constructively
// using Z as zero and S(n) as the successor to an other number.
//
// the result are these numbers: Z, S(Z), S(S(Z)), S(S(S(Z)))
// the encoding used is: 'z', ['s', 'z'], ['s', ['s', 'z']], ...
//
// pluso is a relation that describes addition through relations,
// so that it can be run backwards.
//
// @todo figure out how to prevent divergence if
// the peanoo clauses are before the conde in pluso

function peanoo($n) {
    return conde([
        [eq($n, 'z')],
        [fresh($m ==> all([
            eq($n, ['s', $m]),
            peanoo($m),
         ]))],
    ]);
}

function peano_pluso($a, $b, $out) {
    return all([
        conde([
            [eq($a, 'z'), eq($out, $b)],
            [fresh(($c, $d) ==> all([
                eq($a, ['s', $c]),
                eq($out, ['s', $d]),
                peano_pluso($c, $b, $d),
             ]))],
        ]),
        peanoo($a),
        peanoo($b),
        peanoo($out),
    ]);
}

// var_dump(run(10, $q ==> peanoo($q)));
// var_dump(run(1, $q ==> peano_pluso('z', 'z', $q)));
// var_dump(run(1, $q ==> peano_pluso(['s', 'z'], ['s', 'z'], $q)));
// var_dump(run(2, $q ==> peano_pluso(['s', 'z'], ['s', 'z'], $q)));
// var_dump(run(10, $q ==>
//     fresh(($x, $y, $z) ==>
//         all([
//             peano_pluso($x, $y, $z),
//             eq($q, [$x, $y, $z]),
//         ]))));
// var_dump(run(3, $q ==>
//     fresh(($x, $y) ==>
//         all([
//             peano_pluso($x, $y, ['s', ['s', ['s', 'z']]]),
//             eq($q, [$x, $y]),
//         ]))));
