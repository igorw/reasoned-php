<?php

namespace igorw\reasoned;

class TestCase extends \PHPUnit_Framework_TestCase {
    function testConjDisj() {
        // test1
        $expected = [100, 101, 101, 110, 110];
        $this->assertSame($expected, call(disj(
             disj(fail, succeed),
             conj(
               disj(function ($x) { return succeed($x + 1); },
                 function ($x) { return succeed($x + 10); }),
               disj(succeed, succeed))),
            100));
    }

    function testLogicVars() {
        // define a bunch of logic variables, for convenience
        $vx = lvar('x');
        $vy = lvar('y');
        $vz = lvar('z');
        $vq = lvar('q');

        // test-u1
        $expected = [pair('x', lvar('y'))];
        $this->assertEquals($expected, unify($vx, $vy, empty_subst()));

        // test-u2
        $expected = [
            pair('y', 1),
            pair('x', lvar('y')),
        ];
        $this->assertEquals($expected, unify($vx, 1, unify($vx, $vy, empty_subst())));

        // test-u3
        $expected = 1;
        $this->assertEquals($expected, lookup($vy, unify($vx, 1, unify($vx, $vy, empty_subst()))));
        // when two variables are associated with each other,
        // improving our knowledge about one of them improves the knowledge of the
        // other

        // test-u4
        $expected = [
            pair('y', 1),
            pair('x', lvar('y')),
        ];
        $this->assertEquals($expected, unify([$vx, $vy], [$vy, 1], empty_subst()));
        // exactly the same substitution as in test-u2
    }

    function testChoice() {
        // test choice 1
        $expected = [[]];
        $this->assertEquals($expected, run(choice(2, [1, 2, 3])));

        // test choice 2
        $expected = [];
        $this->assertEquals($expected, run(choice(10, [1, 2, 3])));
        // empty list of outcomes: 10 is not a member of '(1 2 3)

        // test choice 3
        $vx = lvar('x');
        $expected = [
            [pair('x', 1)],
            [pair('x', 2)],
            [pair('x', 3)],
        ];
        $this->assertEquals($expected, run(choice($vx, [1, 2, 3])));
        // three outcomes
    }
}
