<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Config\Definition;

@trigger_error('The '.__NAMESPACE__.'\ReferenceDumper class is deprecated since Symfony 2.4 and will be removed in 3.0. Use the Symfony2\Component\Config\Definition\Dumper\YamlReferenceDumper class instead.', E_USER_DEPRECATED);

use Symfony2\Component\Config\Definition\Dumper\YamlReferenceDumper;

/**
 * @deprecated since version 2.4, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Config\Definition\Dumper\YamlReferenceDumper} instead.
 */
class ReferenceDumper extends YamlReferenceDumper
{
}
