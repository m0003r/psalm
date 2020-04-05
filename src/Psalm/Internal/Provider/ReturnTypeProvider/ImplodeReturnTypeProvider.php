<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\StatementsSource;
use Psalm\Type;
use function count;
use function implode;

class ImplodeReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['implode'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();
        $stringBuffer = [];

        $glueArg = null;
        if (count($call_args) === 1) {
            $arrayArg = $call_args[0];
        } elseif (count($call_args) === 2) {
            [$glueArg, $arrayArg] = $call_args;
        } else {
            // invalid call args
            return Type::getString();
        }

        if ($glueArg instanceof PhpParser\Node\Arg) {
            if (($glueArgType = $nodeTypeProvider->getType($glueArg->value))
                && $glueArgType->isSingleStringLiteral()) {
                $glueStr = $glueArgType->getSingleStringLiteral()->value;
            } else {
                // glue isn't single string literal
                return Type::getString();
            }
        } else {
            $glueStr = '';
        }

        if (!($arrayArgType = $nodeTypeProvider->getType($arrayArg->value))
            || !$arrayArgType->hasArray()) {
            // array isn't array
            return Type::getString();
        }

        $arrayArgTypeAtomic = $arrayArgType->getAtomicTypes()['array'];
        if (!$arrayArgTypeAtomic instanceof Type\Atomic\ObjectLike) {
            // array isn't object-like
            return Type::getString();
        }

        foreach ($arrayArgTypeAtomic->properties as $type) {
            if (!$type->isSingleStringLiteral()) {
                // arg isn't single string literal
                return Type::getString();
            }
            $stringBuffer[] = $type->getSingleStringLiteral()->value;
        }

        return Type::getString(implode($glueStr, $stringBuffer));
    }
}
