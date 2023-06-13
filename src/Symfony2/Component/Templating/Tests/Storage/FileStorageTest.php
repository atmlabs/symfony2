<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Templating\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Templating\Storage\FileStorage;

class FileStorageTest extends TestCase
{
    public function testGetContent()
    {
        $storage = new FileStorage('foo');
        $this->assertInstanceOf('Symfony2\Component\Templating\Storage\Storage', $storage, 'FileStorage is an instance of Storage');
        $storage = new FileStorage(__DIR__.'/../Fixtures/templates/foo.php');
        $this->assertEquals('<?php echo $foo ?>'."\n", $storage->getContent(), '->getContent() returns the content of the template');
    }
}
