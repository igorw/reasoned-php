<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 22: ordered search trees

// binary search

$tree = [
    build_num(16),
    [
        build_num(12),
        [build_num(9), [], []],
        [
            build_num(14),
            [build_num(15), [], []],
            [],
        ],
    ],
    [
        build_num(17),
        [],
        [build_num(20), [], []]
    ],
];

// out is a new tree containing the item
function inserto($item, $tree, $out) {
    return conde([
        [eq($tree, []), eq($out, [$item, [], []])],
        [fresh_all(($n, $l, $l1, $r) ==> [
            eq($tree, [$n, $l, $r]),
            eq($out, [$n, $l1, $r]),
            lto($item, $n),
            inserto($item, $l, $l1),
         ])],
        [fresh_all(($n, $l, $r, $r1) ==> [
            eq($tree, [$n, $l, $r]),
            eq($out, [$n, $l, $r1]),
            lto($n, $item),
            inserto($item, $r, $r1),
         ])],
        [fresh_all(($l, $r) ==> [
            eq($tree, [$item, $l, $r]),
            eq($out, [$item, $l, $r]),
         ])],
    ]);
}

echo json_encode(run_star($q ==> inserto(build_num(1), [], $q)))."\n";
echo json_encode(run_star($q ==> inserto(build_num(2), [build_num(1), [], []], $q)))."\n";
echo json_encode(run_star($q ==> inserto(build_num(1), [build_num(2), [], []], $q)))."\n";
echo json_encode(run_star($q ==> inserto(build_num(3), [build_num(2), [build_num(1), [], []], []], $q)))."\n";
echo json_encode(run_star($q ==> inserto(build_num(3), [build_num(2), [build_num(1), [], []], []], $q)))."\n";
echo json_encode(run_star($q ==> inserto(build_num(8), $tree, $q)))."\n";
