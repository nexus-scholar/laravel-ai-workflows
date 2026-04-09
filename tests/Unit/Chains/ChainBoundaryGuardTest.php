<?php

declare(strict_types=1);

use function PHPUnit\Framework\fail;

it('keeps chain layer free from direct provider transport logic', function () {
    $chainDirectory = __DIR__.'/../../../src/Chains';

    $forbiddenPatterns = [
        '/\\bGuzzleHttp\\\\/i',
        '/\\bIlluminate\\\\Support\\\\Facades\\\\Http\\b/',
        '/\\bcurl_(init|exec|setopt|close)\\s*\\(/i',
        '/https?:\\/\\//i',
    ];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chainDirectory));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        expect($contents)->not->toBeFalse();

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $contents) === 1) {
                fail("Forbidden transport pattern [{$pattern}] found in {$file->getPathname()}.");
            }
        }
    }
});

