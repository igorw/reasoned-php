<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 6: length of a list

function length($l, $len) {
    return conde([
        [eq($l, []), eq($len, 0)],
        [fresh(($a, $d, $d_len) ==>
            all([
                conso($a, $d, $l),
                length($d, $d_len),
                inc($d_len, $len),
            ]))],
    ]);
}

// amazing number system
function inc($n, $n1) {
    $cases = [];
    foreach (range(0, 100) as $i) {
        $cases[] = [eq($n, $i), eq($n1, $i + 1)];
    }
    return conde($cases);
}

// accumulator version

function accumulate($l, $acc, $out) {
    return conde([
        [eq($l, []), eq($out, $acc)],
        [fresh(($a, $d, $acc1) ==>
            all([
                conso($a, $d, $l),
                inc($acc, $acc1),
                accumulate($d, $acc1, $out)
            ]))],
    ]);
}

function length_acc($l, $len) {
    return accumulate($l, 0, $len);
}

var_dump(run_star($q ==> length(['a', 'b', 'c'], $q)));
var_dump(run_star($q ==> length_acc(['a', 'b', 'c'], $q)));
var_dump(run_star($q ==> length_acc(['apple', 'pear'], $q)));
// var_dump(run(1, $q ==> length_acc($q, 3)));
// var_dump(run(1, $q ==> length($q, 0))); // stack overflow? @todo investigate
var_dump(run_star($q ==> length_acc(['alpha'], 2)));
