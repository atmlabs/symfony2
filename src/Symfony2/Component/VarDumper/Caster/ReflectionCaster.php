<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\VarDumper\Caster;

use Symfony2\Component\VarDumper\Cloner\Stub;

/**
 * Casts Reflector related classes to array representation.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ReflectionCaster
{
    private static $extraMap = array(
        'docComment' => 'getDocComment',
        'extension' => 'getExtensionName',
        'isDisabled' => 'isDisabled',
        'isDeprecated' => 'isDeprecated',
        'isInternal' => 'isInternal',
        'isUserDefined' => 'isUserDefined',
        'isGenerator' => 'isGenerator',
        'isVariadic' => 'isVariadic',
    );

    /**
     * @deprecated since Symfony 2.7, to be removed in 3.0.
     */
    public static function castReflector(\Reflector $c, array $a, Stub $stub, $isNested)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.7 and will be removed in 3.0.', E_USER_DEPRECATED);
        $a[Caster::PREFIX_VIRTUAL.'reflection'] = $c->__toString();

        return $a;
    }

    public static function castClosure(\Closure $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;
        $c = new \ReflectionFunction($c);

        $stub->class = 'Closure'; // HHVM generates unique class names for closures
        $a = static::castFunctionAbstract($c, $a, $stub, $isNested);

        if (isset($a[$prefix.'parameters'])) {
            foreach ($a[$prefix.'parameters']->value as &$v) {
                $param = $v;
                $v = new EnumStub(array());
                foreach (static::castParameter($param, array(), $stub, true) as $k => $param) {
                    if ("\0" === $k[0]) {
                        $v->value[substr($k, 3)] = $param;
                    }
                }
                unset($v->value['position'], $v->value['isVariadic'], $v->value['byReference'], $v);
            }
        }

        if ($f = $c->getFileName()) {
            $a[$prefix.'file'] = $f;
            $a[$prefix.'line'] = $c->getStartLine().' to '.$c->getEndLine();
        }

        $prefix = Caster::PREFIX_DYNAMIC;
        unset($a['name'], $a[$prefix.'this'], $a[$prefix.'parameter'], $a[Caster::PREFIX_VIRTUAL.'extra']);

        return $a;
    }

    public static function castGenerator(\Generator $c, array $a, Stub $stub, $isNested)
    {
        if (!class_exists('ReflectionGenerator', false)) {
            return $a;
        }

        // Cannot create ReflectionGenerator based on a terminated Generator
        try {
            $reflectionGenerator = new \ReflectionGenerator($c);
        } catch (\Exception $e) {
            $a[Caster::PREFIX_VIRTUAL.'closed'] = true;

            return $a;
        }

        return self::castReflectionGenerator($reflectionGenerator, $a, $stub, $isNested);
    }

    public static function castType(\ReflectionType $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        $a += array(
            $prefix.'name' => $c instanceof \ReflectionNamedType ? $c->getName() : $c->__toString(),
            $prefix.'allowsNull' => $c->allowsNull(),
            $prefix.'isBuiltin' => $c->isBuiltin(),
        );

        return $a;
    }

    public static function castReflectionGenerator(\ReflectionGenerator $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        if ($c->getThis()) {
            $a[$prefix.'this'] = new CutStub($c->getThis());
        }
        $function = $c->getFunction();
        $frame = array(
            'class' => isset($function->class) ? $function->class : null,
            'type' => isset($function->class) ? ($function->isStatic() ? '::' : '->') : null,
            'function' => $function->name,
            'file' => $c->getExecutingFile(),
            'line' => $c->getExecutingLine(),
        );
        if ($trace = $c->getTrace(DEBUG_BACKTRACE_IGNORE_ARGS)) {
            $function = new \ReflectionGenerator($c->getExecutingGenerator());
            array_unshift($trace, array(
                'function' => 'yield',
                'file' => $function->getExecutingFile(),
                'line' => $function->getExecutingLine() - 1,
            ));
            $trace[] = $frame;
            $a[$prefix.'trace'] = new TraceStub($trace, false, 0, -1, -1);
        } else {
            $function = new FrameStub($frame, false, true);
            $function = ExceptionCaster::castFrameStub($function, array(), $function, true);
            $a[$prefix.'executing'] = new EnumStub(array(
                $frame['class'].$frame['type'].$frame['function'].'()' => $function[$prefix.'src'],
            ));
        }

        $a[Caster::PREFIX_VIRTUAL.'closed'] = false;

        return $a;
    }

    public static function castClass(\ReflectionClass $c, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        if ($n = \Reflection::getModifierNames($c->getModifiers())) {
            $a[$prefix.'modifiers'] = implode(' ', $n);
        }

        self::addMap($a, $c, array(
            'extends' => 'getParentClass',
            'implements' => 'getInterfaceNames',
            'constants' => 'getConstants',
        ));

        foreach ($c->getProperties() as $n) {
            $a[$prefix.'properties'][$n->name] = $n;
        }

        foreach ($c->getMethods() as $n) {
            $a[$prefix.'methods'][$n->name] = $n;
        }

        if (!($filter & Caster::EXCLUDE_VERBOSE) && !$isNested) {
            self::addExtra($a, $c);
        }

        return $a;
    }

    public static function castFunctionAbstract(\ReflectionFunctionAbstract $c, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        self::addMap($a, $c, array(
            'returnsReference' => 'returnsReference',
            'returnType' => 'getReturnType',
            'class' => 'getClosureScopeClass',
            'this' => 'getClosureThis',
        ));

        if (isset($a[$prefix.'returnType'])) {
            $v = $a[$prefix.'returnType'];
            $v = $v instanceof \ReflectionNamedType ? $v->getName() : $v->__toString();
            $a[$prefix.'returnType'] = $a[$prefix.'returnType']->allowsNull() ? '?'.$v : $v;
        }
        if (isset($a[$prefix.'this'])) {
            $a[$prefix.'this'] = new CutStub($a[$prefix.'this']);
        }

        foreach ($c->getParameters() as $v) {
            $k = '$'.$v->name;
            if (method_exists($v, 'isVariadic') && $v->isVariadic()) {
                $k = '...'.$k;
            }
            if ($v->isPassedByReference()) {
                $k = '&'.$k;
            }
            $a[$prefix.'parameters'][$k] = $v;
        }
        if (isset($a[$prefix.'parameters'])) {
            $a[$prefix.'parameters'] = new EnumStub($a[$prefix.'parameters']);
        }

        if ($v = $c->getStaticVariables()) {
            foreach ($v as $k => &$v) {
                $a[$prefix.'use']['$'.$k] = &$v;
            }
            unset($v);
            $a[$prefix.'use'] = new EnumStub($a[$prefix.'use']);
        }

        if (!($filter & Caster::EXCLUDE_VERBOSE) && !$isNested) {
            self::addExtra($a, $c);
        }

        // Added by HHVM
        unset($a[Caster::PREFIX_DYNAMIC.'static']);

        return $a;
    }

    public static function castMethod(\ReflectionMethod $c, array $a, Stub $stub, $isNested)
    {
        $a[Caster::PREFIX_VIRTUAL.'modifiers'] = implode(' ', \Reflection::getModifierNames($c->getModifiers()));

        return $a;
    }

    public static function castParameter(\ReflectionParameter $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        // Added by HHVM
        unset($a['info']);

        self::addMap($a, $c, array(
            'position' => 'getPosition',
            'isVariadic' => 'isVariadic',
            'byReference' => 'isPassedByReference',
            'allowsNull' => 'allowsNull',
        ));

        if (method_exists($c, 'getType')) {
            if ($v = $c->getType()) {
                $a[$prefix.'typeHint'] = $v instanceof \ReflectionNamedType ? $v->getName() : $v->__toString();
            }
        } elseif (preg_match('/^(?:[^ ]++ ){4}([a-zA-Z_\x7F-\xFF][^ ]++)/', $c, $v)) {
            $a[$prefix.'typeHint'] = $v[1];
        }
        if (!isset($a[$prefix.'typeHint'])) {
            unset($a[$prefix.'allowsNull']);
        }

        try {
            $a[$prefix.'default'] = $v = $c->getDefaultValue();
            if (method_exists($c, 'isDefaultValueConstant') && $c->isDefaultValueConstant()) {
                $a[$prefix.'default'] = new ConstStub($c->getDefaultValueConstantName(), $v);
            }
            if (null === $v) {
                unset($a[$prefix.'allowsNull']);
            }
        } catch (\ReflectionException $e) {
            if (isset($a[$prefix.'typeHint']) && $c->allowsNull() && !class_exists('ReflectionNamedType', false)) {
                $a[$prefix.'default'] = null;
                unset($a[$prefix.'allowsNull']);
            }
        }

        return $a;
    }

    public static function castProperty(\ReflectionProperty $c, array $a, Stub $stub, $isNested)
    {
        $a[Caster::PREFIX_VIRTUAL.'modifiers'] = implode(' ', \Reflection::getModifierNames($c->getModifiers()));
        self::addExtra($a, $c);

        return $a;
    }

    public static function castExtension(\ReflectionExtension $c, array $a, Stub $stub, $isNested)
    {
        self::addMap($a, $c, array(
            'version' => 'getVersion',
            'dependencies' => 'getDependencies',
            'iniEntries' => 'getIniEntries',
            'isPersistent' => 'isPersistent',
            'isTemporary' => 'isTemporary',
            'constants' => 'getConstants',
            'functions' => 'getFunctions',
            'classes' => 'getClasses',
        ));

        return $a;
    }

    public static function castZendExtension(\ReflectionZendExtension $c, array $a, Stub $stub, $isNested)
    {
        self::addMap($a, $c, array(
            'version' => 'getVersion',
            'author' => 'getAuthor',
            'copyright' => 'getCopyright',
            'url' => 'getURL',
        ));

        return $a;
    }

    private static function addExtra(&$a, \Reflector $c)
    {
        $x = isset($a[Caster::PREFIX_VIRTUAL.'extra']) ? $a[Caster::PREFIX_VIRTUAL.'extra']->value : array();

        if (method_exists($c, 'getFileName') && $m = $c->getFileName()) {
            $x['file'] = $m;
            $x['line'] = $c->getStartLine().' to '.$c->getEndLine();
        }

        self::addMap($x, $c, self::$extraMap, '');

        if ($x) {
            $a[Caster::PREFIX_VIRTUAL.'extra'] = new EnumStub($x);
        }
    }

    private static function addMap(&$a, \Reflector $c, $map, $prefix = Caster::PREFIX_VIRTUAL)
    {
        foreach ($map as $k => $m) {
            if (method_exists($c, $m) && false !== ($m = $c->$m()) && null !== $m) {
                $a[$prefix.$k] = $m instanceof \Reflector ? $m->name : $m;
            }
        }
    }
}
