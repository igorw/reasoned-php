<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 8: maximum of a list

function maxo($l, $acc, $m) {
    return conde([
        [eq($l, []), eq($acc, $m)],
        [fresh_all(($a, $d) ==> [
            conso($a, $d, $l),
            conde([
                [lto($acc, $a), maxo($d, $a, $m)],
                [lteqo($a, $acc), maxo($d, $acc, $m)],
            ]),
         ])],
    ]);
}

var_dump(parse_nums(run(1, $q ==>
    fresh($n ==>
        maxo([build_num(1), build_num(2), build_num(14), build_num(7)], build_num(0), $q)))));
