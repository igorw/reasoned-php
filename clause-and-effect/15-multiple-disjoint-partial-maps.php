<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// clause and effect
// worksheet 15: multiple disjoint partial maps

function herd($l, $sheep, $goats) {
    return conde([
        [eq($l, []), eq($sheep, []), eq($goats, [])],
        [fresh_all(($d, $d_sheep) ==> [
            conso('sheep', $d, $l),
            conso('sheep', $d_sheep, $sheep),
            herd($d, $d_sheep, $goats)])],
        [fresh_all(($d, $d_goats) ==> [
            conso('goat', $d, $l),
            conso('goat', $d_goats, $goats),
            herd($d, $sheep, $d_goats)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($sheep, $goats) ==> [
        herd(['sheep', 'goat', 'goat', 'sheep', 'goat'], $sheep, $goats),
        eq($q, [$sheep, $goats])])));
var_dump(run_star($q ==>
    fresh_all(($sheep, $goats) ==> [
        herd(['goat', 'sheep', 'stone', 'goat', 'tree'], $sheep, $goats),
        eq($q, [$sheep, $goats])])));
var_dump(run_star($q ==> herd($q, ['sheep', 'sheep'], ['goat', 'goat'])));

function herd_ignore_invalid($l, $sheep, $goats) {
    return conde([
        [eq($l, []), eq($sheep, []), eq($goats, [])],
        [fresh_all(($d, $d_sheep) ==> [
            conso('sheep', $d, $l),
            conso('sheep', $d_sheep, $sheep),
            herd_ignore_invalid($d, $d_sheep, $goats)])],
        [fresh_all(($d, $d_goats) ==> [
            conso('goat', $d, $l),
            conso('goat', $d_goats, $goats),
            herd_ignore_invalid($d, $sheep, $d_goats)])],
        [fresh_all(($a, $d) ==> [
            conso($a, $d, $l),
            neq($a, 'sheep'),
            neq($a, 'goat'),
            herd_ignore_invalid($d, $sheep, $goats)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($sheep, $goats) ==> [
        herd_ignore_invalid(['goat', 'sheep', 'stone', 'goat', 'tree'], $sheep, $goats),
        eq($q, [$sheep, $goats])])));

function herd_separate_invalid($l, $sheep, $goats, $other) {
    return conde([
        [eq($l, []), eq($sheep, []), eq($goats, []), eq($other, [])],
        [fresh_all(($d, $d_sheep) ==> [
            conso('sheep', $d, $l),
            conso('sheep', $d_sheep, $sheep),
            herd_separate_invalid($d, $d_sheep, $goats, $other)])],
        [fresh_all(($d, $d_goats) ==> [
            conso('goat', $d, $l),
            conso('goat', $d_goats, $goats),
            herd_separate_invalid($d, $sheep, $d_goats, $other)])],
        [fresh_all(($a, $d, $d_other) ==> [
            conso($a, $d, $l),
            neq($a, 'sheep'),
            neq($a, 'goat'),
            conso($a, $d_other, $other),
            herd_separate_invalid($d, $sheep, $goats, $d_other)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($sheep, $goats, $other) ==> [
        herd_separate_invalid(['goat', 'sheep', 'stone', 'goat', 'tree'], $sheep, $goats, $other),
        eq($q, [$sheep, $goats, $other])])));

function alternate($l, $x, $y) {
    return conde([
        [eq($l, []), eq($x, []), eq($y, [])],
        [fresh_all(($d1, $d2, $ax, $dx, $ay, $dy) ==> [
            conso($ax, $d1, $l),
            conso($ay, $d2, $d1),
            conso($ax, $dx, $x),
            conso($ay, $dy, $y),
            alternate($d2, $dx, $dy)])],
    ]);
}

var_dump(run_star($q ==>
    fresh_all(($x, $y) ==> [
        alternate([1, 2, 3, 4, 5, 6], $x, $y),
        eq($q, [$x, $y])])));
