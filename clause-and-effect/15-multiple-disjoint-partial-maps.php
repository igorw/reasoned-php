<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 15: multiple disjoint partial maps

// goal: separate a herd (list of sheep and goats)
// into two separate lists, one for sheep and  one
// for goats.
//
// variation: skip over invalid elements
// variation: put invalid elements in a separate list
// variation: split a list into two lists in an alternating
//            (interleaving) way

function herdo($l, $sheep, $goats) {
    return conde([
        [eq($l, []), eq($sheep, []), eq($goats, [])],
        [fresh_all(($d, $d_sheep) ==> [
            conso('sheep', $d, $l),
            conso('sheep', $d_sheep, $sheep),
            herdo($d, $d_sheep, $goats)])],
        [fresh_all(($d, $d_goats) ==> [
            conso('goat', $d, $l),
            conso('goat', $d_goats, $goats),
            herdo($d, $sheep, $d_goats)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($sheep, $goats) ==> [
        herdo(['sheep', 'goat', 'goat', 'sheep', 'goat'], $sheep, $goats),
        eq($q, [$sheep, $goats])])));
var_dump(run_star($q ==>
    fresh_all(($sheep, $goats) ==> [
        herdo(['goat', 'sheep', 'stone', 'goat', 'tree'], $sheep, $goats),
        eq($q, [$sheep, $goats])])));
var_dump(run_star($q ==> herdo($q, ['sheep', 'sheep'], ['goat', 'goat'])));

function herd_ignore_invalido($l, $sheep, $goats) {
    return conde([
        [eq($l, []), eq($sheep, []), eq($goats, [])],
        [fresh_all(($d, $d_sheep) ==> [
            conso('sheep', $d, $l),
            conso('sheep', $d_sheep, $sheep),
            herd_ignore_invalido($d, $d_sheep, $goats)])],
        [fresh_all(($d, $d_goats) ==> [
            conso('goat', $d, $l),
            conso('goat', $d_goats, $goats),
            herd_ignore_invalido($d, $sheep, $d_goats)])],
        [fresh_all(($a, $d) ==> [
            conso($a, $d, $l),
            neq($a, 'sheep'),
            neq($a, 'goat'),
            herd_ignore_invalido($d, $sheep, $goats)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($sheep, $goats) ==> [
        herd_ignore_invalido(['goat', 'sheep', 'stone', 'goat', 'tree'], $sheep, $goats),
        eq($q, [$sheep, $goats])])));

function herd_separate_invalido($l, $sheep, $goats, $other) {
    return conde([
        [eq($l, []), eq($sheep, []), eq($goats, []), eq($other, [])],
        [fresh_all(($d, $d_sheep) ==> [
            conso('sheep', $d, $l),
            conso('sheep', $d_sheep, $sheep),
            herd_separate_invalido($d, $d_sheep, $goats, $other)])],
        [fresh_all(($d, $d_goats) ==> [
            conso('goat', $d, $l),
            conso('goat', $d_goats, $goats),
            herd_separate_invalido($d, $sheep, $d_goats, $other)])],
        [fresh_all(($a, $d, $d_other) ==> [
            conso($a, $d, $l),
            neq($a, 'sheep'),
            neq($a, 'goat'),
            conso($a, $d_other, $other),
            herd_separate_invalido($d, $sheep, $goats, $d_other)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($sheep, $goats, $other) ==> [
        herd_separate_invalido(['goat', 'sheep', 'stone', 'goat', 'tree'], $sheep, $goats, $other),
        eq($q, [$sheep, $goats, $other])])));

function alternateo($l, $x, $y) {
    return conde([
        [eq($l, []), eq($x, []), eq($y, [])],
        [fresh_all(($d1, $d2, $ax, $dx, $ay, $dy) ==> [
            conso($ax, $d1, $l),
            conso($ay, $d2, $d1),
            conso($ax, $dx, $x),
            conso($ay, $dy, $y),
            alternateo($d2, $dx, $dy)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($x, $y) ==> [
        alternateo([1, 2, 3, 4, 5, 6], $x, $y),
        eq($q, [$x, $y])])));
