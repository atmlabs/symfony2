<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\DoctrineBundle\Tests\Command;

use Symfony2\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Symfony2\Component\Console\Application;
use Symfony2\Component\Console\Tester\CommandTester;

class CreateDatabaseDoctrineTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $connectionName = 'default';
        $dbName = 'test';
        $params = array(
            'dbname' => $dbName,
            'memory' => true,
            'driver' => 'pdo_sqlite'
        );

        $application = new Application();
        $application->add(new CreateDatabaseDoctrineCommand());

        $command = $application->find('doctrine:database:create');
        $command->setContainer($this->getMockContainer($connectionName, $params));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(array('command' => $command->getName()))
        );

        $this->assertContains("Created database \"$dbName\" for connection named $connectionName", $commandTester->getDisplay());
    }


    /**
     * @param $connectionName
     * @param null $params Connection parameters
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockContainer($connectionName, $params=null)
    {
        // Mock the container and everything you'll need here
        $mockDoctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ConnectionRegistry')
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getDefaultConnectionName')
            ->withAnyParameters()
            ->willReturn($connectionName)
        ;


        $mockConnection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(array('getParams'))
            ->getMockForAbstractClass();

        $mockConnection->expects($this->any())
            ->method('getParams')
            ->withAnyParameters()
            ->willReturn($params);


        $mockDoctrine->expects($this->any())
            ->method('getConnection')
            ->withAnyParameters()
            ->willReturn($mockConnection);
        ;


        $mockContainer = $this->getMockBuilder('Symfony2\Component\DependencyInjection\Container')
            ->setMethods(array('get'))
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->with('doctrine')
            ->willReturn($mockDoctrine);

        return $mockContainer;
    }
}
