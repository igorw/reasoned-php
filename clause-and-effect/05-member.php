<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 5: member

function member($x, $l) {
    return fresh(($a, $d) ==>
        all([
            conso($a, $d, $l),
            conde([
                [eq($x, $a)],
                [neq($x, $a), member($x, $d)],
            ]),
        ]));
}

var_dump(run_star($q ==> member('john', ['paul', 'john'])));
var_dump(run_star($q ==> member($q, ['paul', 'john'])));
var_dump(run_star($q ==> member('joe', ['marx', 'darwin', 'freud'])));
var_dump(run(2, $q ==> member('foo', $q)));

// x is a member of both a and b
function mystery($x, $a, $b) {
    return all([
        member($x, $a),
        member($x, $b),
    ]);
}

var_dump(run_star($q ==> mystery('a', ['b', 'c', 'a'], ['p', 'a', 'l'])));
var_dump(run_star($q ==> mystery('b', ['b', 'l', 'u', 'e'], ['y', 'e', 'l', 'l', 'o', 'w'])));
var_dump(run_star($q ==> mystery($q, ['r', 'a', 'p', 'i', 'd'], ['a', 'c', 't', 'i', 'o', 'n'])));
var_dump(run_star($q ==> mystery($q, ['w', 'a', 'l', 'n', 'u', 't'], ['c', 'h', 'e', 'r', 'r', 'y'])));
