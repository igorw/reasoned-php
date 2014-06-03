<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 18: sequential maps with state

function runcode($l, $c, $n, $x) {
    return conde([
        [eq($l, []), eq($x, [['*', $n, $c]])],
        [fresh_all(($a, $d, $n1) ==> [
            conso($a, $d, $l),
            eq($c, $a),
            inc($n, $n1),
            runcode($d, $a, $n1, $x)])],
        [fresh_all(($a, $d, $z) ==> [
            conso($a, $d, $l),
            conso(['*', $n, $c], $z, $x),
            neq($a, $c),
            runcode($d, $a, 1, $z)])],
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

var_dump(run_star($q ==>
    fresh_all(($c, $x) ==> [
        runcode([1, 1, 2, 2, 2, 3], $c, 0, $x),
        eq($q, [$c, $x])])));

var_dump(run_star($q ==>
    fresh_all(($c, $x) ==> [
        runcode([12, 2, 2, 'w', 3, 3, 's', 's', 's'], $c, 0, $x),
        eq($q, [$c, $x])])));
