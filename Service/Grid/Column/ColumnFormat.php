<?php

namespace AV\GridBundle\Service\Grid\Column;

/**
 * ColumnFormat used by [[Column]] class to prepare cell data before render.
 * This class format data with twig native functions.
 *
 * It contains five available formats:
 *  html - used by default. Escapes data for the "html" context;
 *  js   - escapes data for the JavaScript context;
 *  raw  - data will be rendered without any escaping;
 *  twig - escapes twig syntax to render it as plain text;
 *  date - prepare data to datetime representation with specified format.
 */
class ColumnFormat
{
    const TEXT_FORMAT = 'html';

    const RAW_FORMAT = 'raw';

    const TWIG_FORMAT = 'twig';

    const DATE_FORMAT = 'date';

    /**
     * @var string Default date format. Used in case if [[data]] is instance of
     * [[\DateTime]] or [[\DateInterval]] and date format was not specified.
     */
    private $defaultDateFormat = 'Y-m-d H:i:s';

    /**
     * Escape data with certain escape format.
     *
     * @param string $data
     * @param string $format
     *
     * @return string
     */
    public function format($data, $format)
    {
        if (
            is_object($data)
            && !($data instanceof \DateTime)
            && !($data instanceof \DateInterval)
        ) {
            throw new \InvalidArgumentException(
                'Only to '.\DateTime::class.' and '.\DateInterval::class
                .' instances grid column format can be applied. '
                .gettype($data).' given.'
            );
        }

        if (!is_string($format) && !is_array($format)) {
            throw new \InvalidArgumentException(
                'Invalid column data format type. String or array expected. '
                .gettype($format).' given.'
            );
        }

        $format = $this->normalizeCurrentFormat($data, $format);

        $formatMethodName = $this->getFormatMethodName($format);

        if (is_array($format)) {
            return call_user_func_array(
                [$this, $formatMethodName],
                [$data, current($format)]
            );
        }

        return call_user_func([$this, $formatMethodName], $data);
    }

    /**
     * Define current format depends on data type.
     *
     * @param mixed $data
     * @param string|array $format
     *
     * @return string|array
     */
    protected function normalizeCurrentFormat($data, $format)
    {
        if (($data instanceof \DateTime) || ($data instanceof \DateInterval)) {

            if (!is_array($format) || key($format) !== self::DATE_FORMAT) {
                return [self::DATE_FORMAT => $this->defaultDateFormat];
            }
        }

        return $format;
    }

    /**
     * Fetch certain format method name depends on specified format.
     *
     * @param string|array $format
     *
     * @return string
     * @throws \RuntimeException
     */
    protected function getFormatMethodName($format)
    {
        $formatName = is_array($format) ? key($format) : $format;

        $formatMethodName = $formatName.'Format';

        if (!method_exists($this, $formatMethodName)) {
            throw new \RuntimeException('Unknown column format: '.$formatName);
        }

        return $formatMethodName;
    }

    /**
     * Convert data to specified date format. $dateTime parameter can contain
     * timestamp or instance of \DateInterval or \DateTime classes.
     *
     * @param string|int|\DateInterval|\DateTime $dateTime
     * @param string|array $format
     *
     * @return string
     * @throws \Exception
     */
    protected function dateFormat($dateTime, $format)
    {
        if (is_string($dateTime) || is_numeric($dateTime)) {
            return "{{ '".$dateTime."'|date('".$format."') }}";
        }

        if (
            !($dateTime instanceof \DateInterval)
            && !($dateTime instanceof \DateTime)
        ) {
            throw new \Exception(
                'Invalid date instance. Expected '.\DateTime::class
                .' or '.\DateInterval::class.' instances.'
            );
        }

        return $dateTime->format($format);
    }

    /**
     * Escapes a string for the HTML context.
     *
     * @param string $data
     *
     * @return string
     */
    protected function htmlFormat($data)
    {
        return "{{ '".$data."'|escape('html') }}";
    }

    /**
     * Escapes a string for the JS context.
     *
     * @param string $data
     *
     * @return string
     */
    protected function jsFormat($data)
    {
        return "{{ '".$data."'|escape('js') }}";
    }

    /**
     * Returns data without any escape.
     *
     * @param string $data
     *
     * @return string
     */
    protected function rawFormat($data)
    {
        return $data;
    }

    /**
     * Escape twig string before render.
     *
     * @param string $data
     *
     * @return string
     */
    protected function twigFormat($data)
    {
        return "{% verbatim %}".$data."{% endverbatim %}";
    }
}