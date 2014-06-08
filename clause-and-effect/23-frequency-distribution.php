<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 23: frequency distribution

function frequencyo($l, $acc, $s) {
    return conde([
        [eq([], $l), eq($acc, $s)],
        [fresh_all(($n, $d, $s2) ==> [
            conso($n, $d, $l),
            updateo($n, $acc, $s2),
            frequencyo($d, $s2, $s),
         ])],
    ]);
}

function updateo($n, $in, $out) {
    return conde([
        [eq($in, []), eq($out, [['*', build_num(1), $n]])],
        [fresh_all(($f, $s, $f1) ==> [
            conso(['*', $f, $n], $s, $in),
            conso(['*', $f1, $n], $s, $out),
            pluso($f, build_num(1), $f1),
         ])],
        [fresh_all(($f, $s, $m) ==> [
            conso(['*', $f, $m], $s, $in),
            eq($out, pairo(['*', build_num(1), $n], pairo(['*', $f, $m], $s))),
            lto($n, $m),
            // !
         ])],
        [fresh_all(($f, $m, $s, $s1) ==> [
            conso(['*', $f, $m], $s, $in),
            conso(['*', $f, $m], $s1, $out),
            neq($n, $m),
            updateo($n, $s, $s1),
         ])],
    ]);
}

echo json_encode(run_star($q ==>
    frequencyo([build_num(1)],
               [],
               $q)))."\n";
echo json_encode(run_star($q ==>
    frequencyo([build_num(1), build_num(2)],
               [],
               $q)))."\n";
echo json_encode(run_star($q ==>
    frequencyo([build_num(1), build_num(1)],
               [],
               $q)))."\n";
echo json_encode(run_star($q ==>
    frequencyo([build_num(3), build_num(3), build_num(2), build_num(2), build_num(1), build_num(1), build_num(2), build_num(2), build_num(3), build_num(3)],
               [],
               $q)))."\n";
