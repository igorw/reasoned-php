<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';
require 'oleg-numbers.php';

// clause and effect
// worksheet 12: partial maps

// really just a filter

function moduloo($n, $m, $mod) {
    return fresh($res ==> divideo($n, $m, $res, $mod));
}

function eveno($n) {
    return moduloo($n, build_num(2), build_num(0));
}

function oddo($n) {
    return moduloo($n, build_num(2), build_num(1));
}

// "evenso" sounded a little odd
function evens_of_listo($l, $out) {
    return conde([
        [eq($l, []), eq($out, [])],
        [fresh_all(($a1, $d1, $a2, $d2) ==> [
            conso($a1, $d1, $l),
            conso($a2, $d2, $out),
            conde([
                [eveno($a1), eq($a1, $a2), evens_of_listo($d1, $d2)],
                [oddo($a1), evens_of_listo($d1, $out)],
            ]),
         ])],
    ]);
}

var_dump(run_star($q ==>
    evens_of_listo([], $q)));

var_dump(run_star($q ==>
    evens_of_listo([build_num(0)], $q)));

var_dump(run_star($q ==>
    evens_of_listo([build_num(1)], $q)));

var_dump(run_star($q ==>
    evens_of_listo([build_num(2)], $q)));

var_dump(run_star($q ==>
    evens_of_listo([build_num(0), build_num(1), build_num(2)], $q)));

var_dump(run_star($q ==>
    evens_of_listo([build_num(0), build_num(1), build_num(2), build_num(3), build_num(4)], $q)));
