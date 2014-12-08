<?php

namespace igorw\reasoned;

require 'vendor/autoload.php';

// relational lambda calculus interpreter
// 
// tagged variant, due to absence of symbolo
//
// based on the miniKanren-uncourse by Will Byrd
// https://github.com/webyrd/miniKanren-uncourse/blob/master/hangouts-by-date/02014-12-07/trans4.scm

// (define lookupo
//   (lambda (x env out)
//     (fresh (y val envˆ)
//       (== `((,y . ,val) . ,envˆ) env)
//       (conde
//         [(== x y) (== val out)]
//         [(=/= x y) (lookupo x envˆ out)]))))

function lookupᵒ($x, $env, $out) {
    return fresh_all(function ($y, $val, $env_hat) use ($x, $env, $out) {
        return [
            ≡(pair(pair($y, $val), $env_hat), $env),
            condᵉ([
                [≡($x, $y), ≡($val, $out)],
                [≢($x, $y), lookupᵒ($x, $env_hat, $out)],
            ])
        ];
    });
}

// (define eval-expo
//   (lambda (expr env out)
//     (conde
//       [(symbolo expr) ;; variable
//        (lookupo expr env out)]
//       [(fresh (x body) ;; abstraction
//          (== `(lambda (,x) ,body) expr)
//          (== `(closure ,x ,body ,env) out))]
//       [(fresh (e1 e2 val x body envˆ) ;; application
//          (== `(,e1 ,e2) expr)
//          (eval-expo e1 env `(closure ,x ,body ,envˆ))
//          (eval-expo e2 env val)
//          (eval-expo body `((,x . ,val) . ,envˆ) out))])))

function eval_expᵒ($expr, $env, $out) {
    return condᵉ([
        [fresh_all(function ($var) use ($expr, $env, $out) {
            return [
                ≡(['var', $var], $expr),
                lookupᵒ($var, $env, $out),
            ];
         })],
        [fresh_all(function ($x, $body) use ($expr, $env, $out) {
            return [
                ≡(['lambda', [$x], $body], $expr),
                ≡(['closure', $x, $body, $env], $out),
            ];
         })],
        [fresh_all(function ($e1, $e2, $val, $x, $body, $env_hat) use ($expr, $env, $out) {
            return [
                ≡(['app', $e1, $e2], $expr),
                eval_expᵒ($e1, $env, ['closure', $x, $body, $env_hat]),
                eval_expᵒ($e2, $env, $val),
                eval_expᵒ($body, pair(pair($x, $val), $env_hat), $out),
            ];
         })],
    ]);
}

// lookupo

var_dump(run(1, function ($q) {
    return lookupᵒ('x', [pair('x', 5), pair('y', 6)], $q);
}));

var_dump(run(1, function ($q) {
    return lookupᵒ('y', [pair('x', 5), pair('y', 6)], $q);
}));

var_dump(run(1, function ($q) {
    return lookupᵒ('x', [pair('x', 5), pair('x', 6)], $q);
}));

var_dump(run(1, function ($q) {
    return lookupᵒ('x', [pair('y', 5), pair('z', 6)], $q);
}));

var_dump(run(1, function ($q) {
    return lookupᵒ('x', [], $q);
}));

// eval-expo

var_dump(run(1, function ($q) {
    return eval_expᵒ(['lambda', ['x'], ['var', 'x']], [], $q);
}));

var_dump(run(1, function ($q) {
    return eval_expᵒ(['var', 'x'], [pair('x', 5)], $q);
}));

var_dump(run(1, function ($q) {
    return eval_expᵒ(['app', ['lambda', ['x'], ['var', 'x']],
                             ['lambda', ['y'], ['var', 'y']]],
                     [],
                     $q);
}));

var_dump(run(1, function ($q) {
    return eval_expᵒ(['app', ['lambda', ['x'], ['var', 'x']],
                             ['var', 'x']],
                     [pair('x', 3)],
                     $q);
}));
