<?php

declare(strict_types=1);

namespace Gaambo\DeployerWordpress\PHPStan;

use Composer\InstalledVersions;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

/**
 * Suppresses v7/v8 compat errors on Deployer\runLocally calls.
 *
 * - argument.unknown: v8 named params are unknown in the v7 signature → suppress on v7
 * - argument.type:    v7 array $options is incompatible with v8 ?string $cwd → suppress on v8
 *
 * Both are intentional and will be removed when v7 support is dropped.
 */
final class DeployerVersionCompatExtension implements IgnoreErrorExtension
{
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $identifier = $error->getIdentifier();

        $isV8 = version_compare(
            InstalledVersions::getVersion('deployer/deployer') ?? '0.0.0',
            '8.0.0',
            '>='
        );

        // argument.unknown only appears when analysing against v7
        // argument.type only appears when analysing against v8
        if ($identifier === 'argument.unknown' && $isV8) {
            return false;
        }
        if ($identifier === 'argument.type' && !$isV8) {
            return false;
        }
        if ($identifier !== 'argument.unknown' && $identifier !== 'argument.type') {
            return false;
        }

        if (!$node instanceof FuncCall || !$node->name instanceof Name) {
            return false;
        }

        $name = $node->name->toString();
        return $name === 'runLocally' || $name === 'Deployer\\runLocally';
    }
}
