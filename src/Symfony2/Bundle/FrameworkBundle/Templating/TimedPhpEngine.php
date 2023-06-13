<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Templating;

use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\Stopwatch\Stopwatch;
use Symfony2\Component\Templating\Loader\LoaderInterface;
use Symfony2\Component\Templating\TemplateNameParserInterface;

/**
 * Times the time spent to render a template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TimedPhpEngine extends PhpEngine
{
    protected $stopwatch;

    public function __construct(TemplateNameParserInterface $parser, ContainerInterface $container, LoaderInterface $loader, Stopwatch $stopwatch, GlobalVariables $globals = null)
    {
        parent::__construct($parser, $container, $loader, $globals);

        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = array())
    {
        $e = $this->stopwatch->start(sprintf('template.php (%s)', $name), 'template');

        $ret = parent::render($name, $parameters);

        $e->stop();

        return $ret;
    }
}
