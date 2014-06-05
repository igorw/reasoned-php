<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 4: acyclic directed graph

function grapho($a, $b) {
    return conde([
        [eq($a, 'g'), eq($b, 'h')],
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

function patho($a, $b) {
    return conde([
        [eq($a, $b)],
        [fresh_all($i ==> [
            grapho($a, $i),
            patho($i, $b),
         ])],
    ]);
}

var_dump(run_star($q ==> patho('f', 'f')));
var_dump(run_star($q ==> patho('a', 'c')));
var_dump(run_star($q ==> patho('g', 'e')));
var_dump(run_star($q ==> patho('g', $q)));
var_dump(run_star($q ==> patho($q, 'h')));
