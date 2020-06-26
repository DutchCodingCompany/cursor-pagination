<?php

namespace DutchCodingCompany\CursorPagination\Tests;

use Orchestra\Testbench\TestCase;
use DutchCodingCompany\CursorPagination\CursorPaginationServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [CursorPaginationServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
