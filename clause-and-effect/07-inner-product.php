<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 7: inner product

function inner($a, $b, $n) {
    return dotaux($a, $b, build_num(0), $n);
}

function dotaux($a, $b, $n, $z) {
    return conde([
        [eq($a, []), eq($b, []), eq($n, $z)],
        [fresh_all(($aa, $ad, $ba, $bd, $prod, $n1) ==> [
            conso($aa, $ad, $a),
            conso($ba, $bd, $b),
            timeso($aa, $ba, $prod),
            pluso($prod, $n, $n1),
            dotaux($ad, $bd, $n1, $z)])],
    ]);
}

var_dump(parse_nums(run(20, $q ==>
    fresh($n ==>
        inner([build_num(2), build_num(2), build_num(1)], [build_num(2), build_num(2), build_num(3)], $q)))));
