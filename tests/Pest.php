<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Illuminate\Testing\Assert;
use sixlive\DotenvEditor\DotenvEditor;

uses(Thenpingme\Tests\TestCase::class)->in(__DIR__);

function loadEnv(string $file): DotenvEditor
{
    return tap(new DotenvEditor)->load($file);
}

expect()->extend('toMatchSubset', function (array $subset) {
    return Assert::assertArraySubset($subset, $this->value);
});
