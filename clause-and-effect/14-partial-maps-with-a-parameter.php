<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 14: partial maps with a parameter

function reduceo($l, $x, $m) {
    return conde([
        [conso($x, $m, $l)],
        [fresh_all(($a, $d, $dm) ==> [
            conso($a, $d, $l),
            conso($a, $dm, $m),
            reduceo($d, $x, $dm)])],
    ]);
}

var_dump(run_star($q ==> reduceo(['a', 'b', 'c'], 'a', $q)));
