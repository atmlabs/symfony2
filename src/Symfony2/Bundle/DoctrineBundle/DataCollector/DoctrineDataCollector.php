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

namespace Symfony2\Bundle\DoctrineBundle\DataCollector;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\Version;
use Symfony2\Bridge\Doctrine\DataCollector\DoctrineDataCollector as BaseCollector;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;

/**
 * DoctrineDataCollector.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class DoctrineDataCollector extends BaseCollector
{
    private $registry;
    private $invalidEntityCount;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        parent::__construct($registry);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        parent::collect($request, $response, $exception);

        $errors = array();
        $entities = array();
        $caches = array(
            'enabled' => false,
            'log_enabled' => false,
            'counts' => array(
                'puts' => 0,
                'hits' => 0,
                'misses' => 0,
            ),
            'regions' => array(
                'puts' => array(),
                'hits' => array(),
                'misses' => array(),
            ),
        );

        foreach ($this->registry->getManagers() as $name => $em) {
            $entities[$name] = array();
            /** @var $factory \Doctrine\ORM\Mapping\ClassMetadataFactory */
            $factory = $em->getMetadataFactory();
            $validator = new SchemaValidator($em);

            /** @var $class \Doctrine\ORM\Mapping\ClassMetadataInfo */
            foreach ($factory->getLoadedMetadata() as $class) {
                if (!isset($entities[$name][$class->getName()])) {
                    $classErrors = $validator->validateClass($class);
                    $entities[$name][$class->getName()] = $class->getName();

                    if (!empty($classErrors)) {
                        $errors[$name][$class->getName()] = $classErrors;
                    }
                }
            }

            if (version_compare(Version::VERSION, '2.5.0-DEV') < 0) {
                continue;
            }

            /** @var $emConfig \Doctrine\ORM\Configuration */
            $emConfig = $em->getConfiguration();
            $slcEnabled = $emConfig->isSecondLevelCacheEnabled();

            if (!$slcEnabled) {
                continue;
            }

            $caches['enabled'] = true;

            /** @var $cacheConfiguration \Doctrine\ORM\Cache\CacheConfiguration */
            /** @var $cacheLoggerChain \Doctrine\ORM\Cache\Logging\CacheLoggerChain */
            $cacheConfiguration = $emConfig->getSecondLevelCacheConfiguration();
            $cacheLoggerChain = $cacheConfiguration->getCacheLogger();

            if (!$cacheLoggerChain || !$cacheLoggerChain->getLogger('statistics')) {
                continue;
            }

            /** @var $cacheLoggerStats \Doctrine\ORM\Cache\Logging\StatisticsCacheLogger */
            $cacheLoggerStats = $cacheLoggerChain->getLogger('statistics');
            $caches['log_enabled'] = true;

            $caches['counts']['puts'] += $cacheLoggerStats->getPutCount();
            $caches['counts']['hits'] += $cacheLoggerStats->getHitCount();
            $caches['counts']['misses'] += $cacheLoggerStats->getMissCount();

            foreach ($cacheLoggerStats->getRegionsPut() as $key => $value) {
                if (!isset($caches['regions']['puts'][$key])) {
                    $caches['regions']['puts'][$key] = 0;
                }

                $caches['regions']['puts'][$key] += $value;
            }

            foreach ($cacheLoggerStats->getRegionsHit() as $key => $value) {
                if (!isset($caches['regions']['hits'][$key])) {
                    $caches['regions']['hits'][$key] = 0;
                }

                $caches['regions']['hits'][$key] += $value;
            }

            foreach ($cacheLoggerStats->getRegionsMiss() as $key => $value) {
                if (!isset($caches['regions']['misses'][$key])) {
                    $caches['regions']['misses'][$key] = 0;
                }

                $caches['regions']['misses'][$key] += $value;
            }
        }

        $this->data['entities'] = $entities;
        $this->data['errors'] = $errors;
        $this->data['caches'] = $caches;
    }

    public function getEntities()
    {
        return $this->data['entities'];
    }

    public function getMappingErrors()
    {
        return $this->data['errors'];
    }

    public function getCacheHitsCount()
    {
        return $this->data['caches']['counts']['hits'];
    }

    public function getCachePutsCount()
    {
        return $this->data['caches']['counts']['puts'];
    }

    public function getCacheMissesCount()
    {
        return $this->data['caches']['counts']['misses'];
    }

    public function getCacheEnabled()
    {
        return $this->data['caches']['enabled'];
    }

    public function getCacheRegions()
    {
        return $this->data['caches']['regions'];
    }

    public function getCacheCounts()
    {
        return $this->data['caches']['counts'];
    }

    public function getInvalidEntityCount()
    {
        if (null === $this->invalidEntityCount) {
            $this->invalidEntityCount = array_sum(array_map('count', $this->data['errors']));
        }

        return $this->invalidEntityCount;
    }
}
