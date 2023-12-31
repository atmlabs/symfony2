<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\DistributionBundle;

use Sensio\Bundle\DistributionBundle\DependencyInjection\Compiler\StepsPass;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\HttpKernel\Bundle\Bundle;

/**
 * SensioDistributionBundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 * @author Jérôme Vieilledent <lolautruche@gmail.com>
 */
class SensioDistributionBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new StepsPass());
    }
}
