<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony2\Component\Console\Input\InputArgument;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;
use Symfony2\Component\Console\Command\Command;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineFormGenerator;

/**
 * Generates a form type class for a given Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 */
class GenerateDoctrineFormCommand extends GenerateDoctrineCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('entity', InputArgument::REQUIRED, 'The entity class name to initialize (shortcut notation)'),
            ))
            ->setDescription('Generates a form type class based on a Doctrine entity')
            ->setHelp(<<<EOT
The <info>doctrine:generate:form</info> command generates a form class based on a Doctrine entity.

<info>php app/console doctrine:generate:form AcmeBlogBundle:Post</info>

Every generated file is based on a template. There are default templates but they can be overriden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/form
APP_PATH/Resources/SensioGeneratorBundle/skeleton/form</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton
EOT
            )
            ->setName('doctrine:generate:form')
            ->setAliases(array('generate:doctrine:form'))
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = Validators::validateEntityName($input->getArgument('entity'));
        [$bundle, $entity] = $this->parseShortcutNotation($entity);

        $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
        $metadata = $this->getEntityMetadata($entityClass);
        $bundle   = $this->getApplication()->getKernel()->getBundle($bundle);
        $generator = $this->getGenerator($bundle);

        $generator->generate($bundle, $entity, $metadata[0]);

        $output->writeln(sprintf(
            'The new %s.php class file has been created under %s.',
            $generator->getClassName(),
            $generator->getClassPath()
        ));
    }

    protected function createGenerator()
    {
        return new DoctrineFormGenerator($this->getContainer()->get('filesystem'));
    }
}
