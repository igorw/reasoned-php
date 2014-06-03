<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 13: removing duplicates

// this produces incorrect answers,
// due to overlapping clauses

function setify($l1, $l2) {
    return conde([
        [eq($l1, []), eq($l2, [])],
        [fresh_all(($a, $d) ==> [
            conso($a, $d, $l1),
            membero($a, $d),
            setify($d, $l2)])],
        [fresh_all(($a, $d1, $d2) ==> [
            conso($a, $d1, $l1),
            conso($a, $d2, $l2),
            setify($d1, $d2)])]
    ]);
}

function membero($x, $l) {
    return conde([
        [firsto($l, $x)],
        [fresh_all($d ==> [
                resto($l, $d),
                membero($x, $d)])],
    ]);
}

var_dump(run_star($q ==> setify(['a', 'a', 'b', 'c', 'b'], $q)));
var_dump(run_star($q ==> setify(['a', 'a', 'b', 'c', 'b'], ['a', 'c', 'b'])));
var_dump(run_star($q ==> setify(['a', 'a', 'b', 'c', 'b'], ['a', 'b', 'c'])));
