<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 6: length of a list

function lengtho($l, $len) {
    return conde([
        [eq($l, []), eq($len, build_num(0))],
        [fresh_all(($a, $d, $d_len) ==> [
            conso($a, $d, $l),
            lengtho($d, $d_len),
            pluso($d_len, build_num(1), $len),
         ])],
    ]);
}

// accumulator version

function accumulateo($l, $acc, $out) {
    return conde([
        [eq($l, []), eq($out, $acc)],
        [fresh_all(($a, $d, $acc1) ==> [
            conso($a, $d, $l),
            pluso($acc, build_num(1), $acc1),
            accumulateo($d, $acc1, $out),
         ])],
    ]);
}

function length_acco($l, $len) {
    return accumulateo($l, build_num(0), $len);
}

var_dump(parse_nums(run_star($q ==> lengtho(['a', 'b', 'c'], $q))));
var_dump(parse_nums(run_star($q ==> length_acco(['a', 'b', 'c'], $q))));
var_dump(parse_nums(run_star($q ==> length_acco(['apple', 'pear'], $q))));
// var_dump(run(1, $q ==> length_acco($q, 3)));
// var_dump(run(1, $q ==> lengtho($q, 0))); // stack overflow? @todo investigate
var_dump(parse_nums(run_star($q ==> length_acco(['alpha'], build_num(2)))));
