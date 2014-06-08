<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 27: linearising

// flattening of lists
// @todo disequality of pairs is currently broken in core

function not_listo($x) {
    return fresh_all(($a, $d) ==> [
        neq(pair($a, $d), $x),
        neq([], $x),
    ]);
}

function flatteno($l, $out) {
    return conde([
        [eq($l, []), eq($out, [])],
        [fresh_all(($a, $d, $l1, $l2) ==> [
            conso($a, $d, $l),
            flatteno($a, $l1),
            flatteno($d, $l2),
            appendo($l1, $l2, $out),
         ])],
        [not_listo($l), eq($out, [$l]), trace_lvars([$l, $out])],
    ]);
}

echo json_encode(run_star($q ==>
    flatteno([], $q)))."\n";
echo json_encode(run_star($q ==>
    flatteno([1], $q)))."\n";
echo json_encode(run_star($q ==>
    flatteno([1, 2, 3], $q)))."\n";
echo json_encode(run_star($q ==>
    flatteno([[[[[1, 2, 3], 4, 5], [6], [7, 8]]], 9], $q)))."\n";
