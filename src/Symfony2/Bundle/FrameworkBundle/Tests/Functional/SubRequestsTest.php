<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Functional;

class SubRequestsTest extends WebTestCase
{
    public function testStateAfterSubRequest()
    {
        $client = $this->createClient(array('test_case' => 'Session', 'root_config' => 'config.yml'));
        $client->request('GET', 'https://localhost/subrequest/en');

        $this->assertEquals('--fr/json--en/html--fr/json--http://localhost/subrequest/fragment/en', $client->getResponse()->getContent());
    }
}
