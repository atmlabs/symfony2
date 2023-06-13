<?php

namespace Symfony2\Component\HttpKernel\Tests\Fixtures\Controller;

class VariadicController
{
    public function action($foo, ...$bar)
    {
    }
}
