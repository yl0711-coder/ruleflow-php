<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

/**
 * Marker contract for operators that evaluate a field's existence rather than
 * its resolved value.
 *
 * The evaluator passes a boolean (whether the field is present in the input
 * context) to `evaluate()` instead of the field value when an operator
 * implements this interface, and the validator treats the rule `value` as
 * optional for such operators. Implement it on custom "field is present"
 * style operators so they receive existence input without the engine having
 * to special-case their names.
 */
interface UsesExistenceInput
{
}
