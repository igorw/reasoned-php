<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 28: linearising efficiently

// flattening of lists
// using a data structure known as difference lists
// to do O(1) list append because it's just pointers

// @todo broken, need a way to constrain a value to not be a list
// (see also worksheet 27)

function flatteno($l, $out) {
    return flat_pairo($l, ['-', $out, []]);
}

function not_listo($x) {
    return fresh_all(($a, $d) ==> [
        neq(pair($a, $d), $x),
        neq([], $x),
    ]);
}

function flat_pairo($x, $y) {
    return conde([
        [eq($x, []), fresh($l ==> eq($y, ['-', $l, $l]))],
        [fresh_all(($a, $d, $l1, $l2, $l3) ==> [
            conso($a, $d, $x),
            eq($y, ['-', $l1, $l3]),
            flat_pairo($a, ['-', $l1, $l2]),
            flat_pairo($d, ['-', $l2, $l3]),
         ])],
        [not_listo($x), fresh($z ==> eq($y, ['-', pair($x, $z), $z]))],
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
