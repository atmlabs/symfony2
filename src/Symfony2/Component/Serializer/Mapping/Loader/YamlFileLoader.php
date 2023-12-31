<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Serializer\Mapping\Loader;

use Symfony2\Component\Serializer\Exception\MappingException;
use Symfony2\Component\Serializer\Mapping\AttributeMetadata;
use Symfony2\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony2\Component\Yaml\Parser;

/**
 * YAML File Loader.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class YamlFileLoader extends FileLoader
{
    private $yamlParser;

    /**
     * An array of YAML class descriptions.
     *
     * @var array
     */
    private $classes;

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata)
    {
        if (null === $this->classes) {
            if (!stream_is_local($this->file)) {
                throw new MappingException(sprintf('This is not a local file "%s".', $this->file));
            }

            if (null === $this->yamlParser) {
                $this->yamlParser = new Parser();
            }

            $classes = $this->yamlParser->parse(file_get_contents($this->file));

            if (empty($classes)) {
                return false;
            }

            // not an array
            if (!\is_array($classes)) {
                throw new MappingException(sprintf('The file "%s" must contain a YAML array.', $this->file));
            }

            $this->classes = $classes;
        }

        if (!isset($this->classes[$classMetadata->getName()])) {
            return false;
        }

        $yaml = $this->classes[$classMetadata->getName()];

        if (isset($yaml['attributes']) && \is_array($yaml['attributes'])) {
            $attributesMetadata = $classMetadata->getAttributesMetadata();

            foreach ($yaml['attributes'] as $attribute => $data) {
                if (isset($attributesMetadata[$attribute])) {
                    $attributeMetadata = $attributesMetadata[$attribute];
                } else {
                    $attributeMetadata = new AttributeMetadata($attribute);
                    $classMetadata->addAttributeMetadata($attributeMetadata);
                }

                if (isset($data['groups'])) {
                    if (!\is_array($data['groups'])) {
                        throw new MappingException(sprintf('The "groups" key must be an array of strings in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                    }

                    foreach ($data['groups'] as $group) {
                        if (!\is_string($group)) {
                            throw new MappingException(sprintf('Group names must be strings in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                        }

                        $attributeMetadata->addGroup($group);
                    }
                }
            }
        }

        return true;
    }
}
