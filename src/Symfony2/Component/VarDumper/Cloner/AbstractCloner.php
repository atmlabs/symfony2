<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\VarDumper\Cloner;

use Symfony2\Component\VarDumper\Caster\Caster;
use Symfony2\Component\VarDumper\Exception\ThrowingCasterException;

/**
 * AbstractCloner implements a generic caster mechanism for objects and resources.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
abstract class AbstractCloner implements ClonerInterface
{
    public static $defaultCasters = array(
        'Symfony2\Component\VarDumper\Caster\CutStub' => 'Symfony2\Component\VarDumper\Caster\StubCaster::castStub',
        'Symfony2\Component\VarDumper\Caster\CutArrayStub' => 'Symfony2\Component\VarDumper\Caster\StubCaster::castCutArray',
        'Symfony2\Component\VarDumper\Caster\ConstStub' => 'Symfony2\Component\VarDumper\Caster\StubCaster::castStub',
        'Symfony2\Component\VarDumper\Caster\EnumStub' => 'Symfony2\Component\VarDumper\Caster\StubCaster::castEnum',

        'Closure' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castClosure',
        'Generator' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castGenerator',
        'ReflectionType' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castType',
        'ReflectionGenerator' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castReflectionGenerator',
        'ReflectionClass' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castClass',
        'ReflectionFunctionAbstract' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castFunctionAbstract',
        'ReflectionMethod' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castMethod',
        'ReflectionParameter' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castParameter',
        'ReflectionProperty' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castProperty',
        'ReflectionExtension' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castExtension',
        'ReflectionZendExtension' => 'Symfony2\Component\VarDumper\Caster\ReflectionCaster::castZendExtension',

        'Doctrine\Common\Persistence\ObjectManager' => 'Symfony2\Component\VarDumper\Caster\StubCaster::cutInternals',
        'Doctrine\Common\Proxy\Proxy' => 'Symfony2\Component\VarDumper\Caster\DoctrineCaster::castCommonProxy',
        'Doctrine\ORM\Proxy\Proxy' => 'Symfony2\Component\VarDumper\Caster\DoctrineCaster::castOrmProxy',
        'Doctrine\ORM\PersistentCollection' => 'Symfony2\Component\VarDumper\Caster\DoctrineCaster::castPersistentCollection',

        'DOMException' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castException',
        'DOMStringList' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castLength',
        'DOMNameList' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castLength',
        'DOMImplementation' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castImplementation',
        'DOMImplementationList' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castLength',
        'DOMNode' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castNode',
        'DOMNameSpaceNode' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castNameSpaceNode',
        'DOMDocument' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castDocument',
        'DOMNodeList' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castLength',
        'DOMNamedNodeMap' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castLength',
        'DOMCharacterData' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castCharacterData',
        'DOMAttr' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castAttr',
        'DOMElement' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castElement',
        'DOMText' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castText',
        'DOMTypeinfo' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castTypeinfo',
        'DOMDomError' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castDomError',
        'DOMLocator' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castLocator',
        'DOMDocumentType' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castDocumentType',
        'DOMNotation' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castNotation',
        'DOMEntity' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castEntity',
        'DOMProcessingInstruction' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castProcessingInstruction',
        'DOMXPath' => 'Symfony2\Component\VarDumper\Caster\DOMCaster::castXPath',

        'ErrorException' => 'Symfony2\Component\VarDumper\Caster\ExceptionCaster::castErrorException',
        'Exception' => 'Symfony2\Component\VarDumper\Caster\ExceptionCaster::castException',
        'Error' => 'Symfony2\Component\VarDumper\Caster\ExceptionCaster::castError',
        'Symfony2\Component\DependencyInjection\ContainerInterface' => 'Symfony2\Component\VarDumper\Caster\StubCaster::cutInternals',
        'Symfony2\Component\VarDumper\Exception\ThrowingCasterException' => 'Symfony2\Component\VarDumper\Caster\ExceptionCaster::castThrowingCasterException',
        'Symfony2\Component\VarDumper\Caster\TraceStub' => 'Symfony2\Component\VarDumper\Caster\ExceptionCaster::castTraceStub',
        'Symfony2\Component\VarDumper\Caster\FrameStub' => 'Symfony2\Component\VarDumper\Caster\ExceptionCaster::castFrameStub',

        'PHPUnit_Framework_MockObject_MockObject' => 'Symfony2\Component\VarDumper\Caster\StubCaster::cutInternals',
        'Prophecy\Prophecy\ProphecySubjectInterface' => 'Symfony2\Component\VarDumper\Caster\StubCaster::cutInternals',
        'Mockery\MockInterface' => 'Symfony2\Component\VarDumper\Caster\StubCaster::cutInternals',

        'PDO' => 'Symfony2\Component\VarDumper\Caster\PdoCaster::castPdo',
        'PDOStatement' => 'Symfony2\Component\VarDumper\Caster\PdoCaster::castPdoStatement',

        'AMQPConnection' => 'Symfony2\Component\VarDumper\Caster\AmqpCaster::castConnection',
        'AMQPChannel' => 'Symfony2\Component\VarDumper\Caster\AmqpCaster::castChannel',
        'AMQPQueue' => 'Symfony2\Component\VarDumper\Caster\AmqpCaster::castQueue',
        'AMQPExchange' => 'Symfony2\Component\VarDumper\Caster\AmqpCaster::castExchange',
        'AMQPEnvelope' => 'Symfony2\Component\VarDumper\Caster\AmqpCaster::castEnvelope',

        'ArrayObject' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castArrayObject',
        'ArrayIterator' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castArrayIterator',
        'SplDoublyLinkedList' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castDoublyLinkedList',
        'SplFileInfo' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castFileInfo',
        'SplFileObject' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castFileObject',
        'SplFixedArray' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castFixedArray',
        'SplHeap' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castHeap',
        'SplObjectStorage' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castObjectStorage',
        'SplPriorityQueue' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castHeap',
        'OuterIterator' => 'Symfony2\Component\VarDumper\Caster\SplCaster::castOuterIterator',

        'MongoCursorInterface' => 'Symfony2\Component\VarDumper\Caster\MongoCaster::castCursor',

        ':curl' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castCurl',
        ':dba' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castDba',
        ':dba persistent' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castDba',
        ':gd' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castGd',
        ':mysql link' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castMysqlLink',
        ':pgsql large object' => 'Symfony2\Component\VarDumper\Caster\PgSqlCaster::castLargeObject',
        ':pgsql link' => 'Symfony2\Component\VarDumper\Caster\PgSqlCaster::castLink',
        ':pgsql link persistent' => 'Symfony2\Component\VarDumper\Caster\PgSqlCaster::castLink',
        ':pgsql result' => 'Symfony2\Component\VarDumper\Caster\PgSqlCaster::castResult',
        ':process' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castProcess',
        ':stream' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castStream',
        ':persistent stream' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castStream',
        ':stream-context' => 'Symfony2\Component\VarDumper\Caster\ResourceCaster::castStreamContext',
        ':xml' => 'Symfony2\Component\VarDumper\Caster\XmlResourceCaster::castXml',
    );

    protected $maxItems = 2500;
    protected $maxString = -1;
    protected $useExt;

    private $casters = array();
    private $prevErrorHandler;
    private $classInfo = array();
    private $filter = 0;

    /**
     * @param callable[]|null $casters A map of casters
     *
     * @see addCasters
     */
    public function __construct(array $casters = null)
    {
        if (null === $casters) {
            $casters = static::$defaultCasters;
        }
        $this->addCasters($casters);
        $this->useExt = \extension_loaded('symfony_debug');
    }

    /**
     * Adds casters for resources and objects.
     *
     * Maps resources or objects types to a callback.
     * Types are in the key, with a callable caster for value.
     * Resource types are to be prefixed with a `:`,
     * see e.g. static::$defaultCasters.
     *
     * @param callable[] $casters A map of casters
     */
    public function addCasters(array $casters)
    {
        foreach ($casters as $type => $callback) {
            $this->casters[strtolower($type)][] = $callback;
        }
    }

    /**
     * Sets the maximum number of items to clone past the first level in nested structures.
     *
     * @param int $maxItems
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = (int) $maxItems;
    }

    /**
     * Sets the maximum cloned length for strings.
     *
     * @param int $maxString
     */
    public function setMaxString($maxString)
    {
        $this->maxString = (int) $maxString;
    }

    /**
     * Clones a PHP variable.
     *
     * @param mixed $var    Any PHP variable
     * @param int   $filter A bit field of Caster::EXCLUDE_* constants
     *
     * @return Data The cloned variable represented by a Data object
     */
    public function cloneVar($var, $filter = 0)
    {
        $this->filter = $filter;
        $this->prevErrorHandler = set_error_handler(array($this, 'handleError'));
        try {
            $data = $this->doClone($var);
        } catch (\Exception $e) {
        }
        restore_error_handler();
        $this->prevErrorHandler = null;

        if (isset($e)) {
            throw $e;
        }

        return new Data($data);
    }

    /**
     * Effectively clones the PHP variable.
     *
     * @param mixed $var Any PHP variable
     *
     * @return array The cloned variable represented in an array
     */
    abstract protected function doClone($var);

    /**
     * Casts an object to an array representation.
     *
     * @param Stub $stub     The Stub for the casted object
     * @param bool $isNested True if the object is nested in the dumped structure
     *
     * @return array The object casted as array
     */
    protected function castObject(Stub $stub, $isNested)
    {
        $obj = $stub->value;
        $class = $stub->class;

        if (isset($class[15]) && "\0" === $class[15] && 0 === strpos($class, "class@anonymous\x00")) {
            $stub->class = get_parent_class($class).'@anonymous';
        }
        if (isset($this->classInfo[$class])) {
            $classInfo = $this->classInfo[$class];
        } else {
            $classInfo = array(
                new \ReflectionClass($class),
                array_reverse(array($class => $class) + class_parents($class) + class_implements($class) + array('*' => '*')),
            );

            $this->classInfo[$class] = $classInfo;
        }

        $a = $this->callCaster('Symfony2\Component\VarDumper\Caster\Caster::castObject', $obj, $classInfo[0], null, $isNested);

        foreach ($classInfo[1] as $p) {
            if (!empty($this->casters[$p = strtolower($p)])) {
                foreach ($this->casters[$p] as $p) {
                    $a = $this->callCaster($p, $obj, $a, $stub, $isNested);
                }
            }
        }

        return $a;
    }

    /**
     * Casts a resource to an array representation.
     *
     * @param Stub $stub     The Stub for the casted resource
     * @param bool $isNested True if the object is nested in the dumped structure
     *
     * @return array The resource casted as array
     */
    protected function castResource(Stub $stub, $isNested)
    {
        $a = array();
        $res = $stub->value;
        $type = $stub->class;

        if (!empty($this->casters[':'.$type])) {
            foreach ($this->casters[':'.$type] as $c) {
                $a = $this->callCaster($c, $res, $a, $stub, $isNested);
            }
        }

        return $a;
    }

    /**
     * Calls a custom caster.
     *
     * @param callable        $callback The caster
     * @param object|resource $obj      The object/resource being casted
     * @param array           $a        The result of the previous cast for chained casters
     * @param Stub            $stub     The Stub for the casted object/resource
     * @param bool            $isNested True if $obj is nested in the dumped structure
     *
     * @return array The casted object/resource
     */
    private function callCaster($callback, $obj, $a, $stub, $isNested)
    {
        try {
            $cast = \call_user_func($callback, $obj, $a, $stub, $isNested, $this->filter);

            if (\is_array($cast)) {
                $a = $cast;
            }
        } catch (\Exception $e) {
            $a[(Stub::TYPE_OBJECT === $stub->type ? Caster::PREFIX_VIRTUAL : '').'⚠'] = new ThrowingCasterException($e);
        }

        return $a;
    }

    /**
     * Special handling for errors: cloning must be fail-safe.
     *
     * @internal
     */
    public function handleError($type, $msg, $file, $line, $context)
    {
        if (E_RECOVERABLE_ERROR === $type || E_USER_ERROR === $type) {
            // Cloner never dies
            throw new \ErrorException($msg, 0, $type, $file, $line);
        }

        if ($this->prevErrorHandler) {
            return \call_user_func($this->prevErrorHandler, $type, $msg, $file, $line, $context);
        }

        return false;
    }
}
