<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

$paths = array_values(array_filter(glob(__DIR__ . '/*', GLOB_ONLYDIR) ?: [], static function (string $path): bool {
	$excludedDirs = ['lang', 'vendor'];

	return !in_array(basename($path), $excludedDirs, true);
}));

return RectorConfig::configure()
	->withPaths($paths)
	->withRootFiles()
	->withPhpSets(php82: true)
	->withRules([LogicalToBooleanRector::class])
	->withSkip([__DIR__ . '/lang/*', __DIR__ . '/vendor/*', NullToStrictStringFuncCallArgRector::class]);
