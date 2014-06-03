<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 4: acyclic directed graph

function a($a, $b) {
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

function path($a, $b) {
    return conde([
        [eq($a, $b)],
        [fresh_all($i ==> [
            a($a, $i),
            path($i, $b),
         ])],
    ]);
}

var_dump(run_star($q ==> path('f', 'f')));
var_dump(run_star($q ==> path('a', 'c')));
var_dump(run_star($q ==> path('g', 'e')));
var_dump(run_star($q ==> path('g', $q)));
var_dump(run_star($q ==> path($q, 'h')));
