<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Serializer\Encoder;

use Symfony2\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Decodes JSON data.
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class JsonDecode implements DecoderInterface
{
    protected $serializer;

    private $associative;
    private $recursionDepth;
    private $lastError = JSON_ERROR_NONE;

    /**
     * Constructs a new JsonDecode instance.
     *
     * @param bool $associative True to return the result associative array, false for a nested stdClass hierarchy
     * @param int  $depth       Specifies the recursion depth
     */
    public function __construct($associative = false, $depth = 512)
    {
        $this->associative = $associative;
        $this->recursionDepth = (int) $depth;
    }

    /**
     * Returns the last decoding error (if any).
     *
     * @return int
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     *             The {@self decode()} method throws an exception if error found.
     * @see http://php.net/manual/en/function.json-last-error.php json_last_error
     */
    public function getLastError()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Catch the exception raised by the decode() method instead to get the last JSON decoding error.', E_USER_DEPRECATED);

        return $this->lastError;
    }

    /**
     * Decodes data.
     *
     * @param string $data    The encoded JSON string to decode
     * @param string $format  Must be set to JsonEncoder::FORMAT
     * @param array  $context An optional set of options for the JSON decoder; see below
     *
     * The $context array is a simple key=>value array, with the following supported keys:
     *
     * json_decode_associative: boolean
     *      If true, returns the object as associative array.
     *      If false, returns the object as nested stdClass
     *      If not specified, this method will use the default set in JsonDecode::__construct
     *
     * json_decode_recursion_depth: integer
     *      Specifies the maximum recursion depth
     *      If not specified, this method will use the default set in JsonDecode::__construct
     *
     * json_decode_options: integer
     *      Specifies additional options as per documentation for json_decode. Only supported with PHP 5.4.0 and higher
     *
     * @return mixed
     *
     * @throws UnexpectedValueException
     *
     * @see http://php.net/json_decode json_decode
     */
    public function decode($data, $format, array $context = array())
    {
        $context = $this->resolveContext($context);

        $associative = $context['json_decode_associative'];
        $recursionDepth = $context['json_decode_recursion_depth'];
        $options = $context['json_decode_options'];

        if (\PHP_VERSION_ID >= 50400) {
            $decodedData = json_decode($data, $associative, $recursionDepth, $options);
        } else {
            $decodedData = json_decode($data, $associative, $recursionDepth);
        }

        if (JSON_ERROR_NONE !== $this->lastError = json_last_error()) {
            throw new UnexpectedValueException(json_last_error_msg());
        }

        return $decodedData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return JsonEncoder::FORMAT === $format;
    }

    /**
     * Merges the default options of the Json Decoder with the passed context.
     *
     * @return array
     */
    private function resolveContext(array $context)
    {
        $defaultOptions = array(
            'json_decode_associative' => $this->associative,
            'json_decode_recursion_depth' => $this->recursionDepth,
            'json_decode_options' => 0,
        );

        return array_merge($defaultOptions, $context);
    }
}
