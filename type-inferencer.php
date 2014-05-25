<?hh

namespace igorw\reasoned;

require 'vendor/autoload.php';

// type inferencer from the rKanren paper

function Ͱᵒ($Γ, $e, $t) {
    return fresh(($e1, $e2, $e3, $t1, $t2) ==>
        condᵉ([
            [≡($e, ['intc', $e1]), ≡($t, 'int')],
            [≡($e, ['+', $e1, $e2]), ≡($t, 'int'),
             Ͱᵒ($Γ, $e1, 'int'),
             Ͱᵒ($Γ, $e2, 'int')],
            [≡($e, ['var', $e1]), lookupᵒ($Γ, $e1, $t)],
            [≡($e, ['λ', [$e1], $e2]),
             ≡($t, ['→', $t1, $t2]),
             Ͱᵒ(pair(pair($e1, $t1), $Γ), $e2, $t2)],
            [≡($e, ['app', $e1, $e2]),
             Ͱᵒ($Γ, $e1, ['→', $t1, $t]),
             Ͱᵒ($Γ, $e2, $t1)],
        ])
    );
}

function lookupᵒ($Γ, $x, $t) {
    return fresh(($rest, $type, $y) ==>
        condᵉ([
            [≡(pair(pair($x, $t), $rest), $Γ)],
            [≡(pair(pair($y, $type), $rest), $Γ),
             ≢($x, $y),
             lookupᵒ($rest, $x, $t)],
        ])
    );
}

var_dump(run(4, function ($q) {
    return fresh(($e, $t) ==>
        all([
            ≡($q, [$e, ':', $t]),
            Ͱᵒ([], $e, $t),
        ])
    );
}));
