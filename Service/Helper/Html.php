<?php

namespace AV\GridBundle\Service\Helper;

use AV\GridBundle\Service\Helper\Exception\HtmlException;

class Html
{
    const CLASS_ATTR = 'class';

    const STYLE_ATTR = 'style';

    const DATA_ATTR = 'data';

    /**
     * @var array List of [data] type attributes
     */
    public $dataAttributes = ['data', 'data-ng', 'ng'];

    /**
     * Converts list of tag attributes from array to encoded string
     * representation.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function prepareTagAttributes(array $attributes)
    {
        $preparedHtml = '';

        foreach ($attributes as $attributeName => $attributeData) {

            $preparedHtml .= $this->prepareTagAttribute(
                $attributeName, $attributeData
            );
        }

        return trim($preparedHtml);
    }

    /**
     * Prepare certain type of attributes.
     *
     * @param string $attributeName
     * @param mixed $attributeData
     *
     * @return mixed|string
     */
    protected function prepareTagAttribute($attributeName, $attributeData)
    {
        if (is_bool($attributeData)) {
            return $attributeName.' ';
        }

        $preparedAttribute = '';

        if (is_array($attributeData)) {

            $attributeType = $this->guessAttributeType($attributeName);

            $prepareMethod = 'prepare'.ucfirst($attributeType).'Attribute';

            if (!method_exists($this, $prepareMethod))  {
                $preparedAttribute .= " $attributeName='"
                    .$this->jsonEncode($attributeData)."' ";

                return $preparedAttribute;
            }

            return call_user_func_array(
                [$this, $prepareMethod], [$attributeName, $attributeData]
            );
        }

        $preparedAttribute .= " $attributeName=".json_encode($attributeData);

        return $preparedAttribute;
    }

    /**
     * Get attribute type.
     *
     * @param string $attributeName
     *
     * @return bool|string
     */
    protected function guessAttributeType($attributeName)
    {
        if (in_array($attributeName, $this->dataAttributes)) {
            return self::DATA_ATTR;
        }

        if (in_array($attributeName, [self::CLASS_ATTR, self::STYLE_ATTR])) {
            return $attributeName;
        }

        return false;
    }

    /**
     * Creates string representation of class attribute from array.
     *
     * @param string $attributeName
     * @param array $attributeData
     *
     * @return string
     * @throws HtmlException
     */
    protected function prepareClassAttribute(
        $attributeName, array $attributeData
    ) {
        $preparedAttribute = '';

        if (!is_string($attributeName)) {
            throw new HtmlException(
                'The expected type of the "attributeName" is string. '
                .gettype($attributeName).' given.'
            );
        }

        $preparedAttribute .= "$attributeName="
            .$this->jsonEncode(implode(' ', $attributeData));

        return $preparedAttribute;
    }

    /**
     * Creates string representation of data attribute from array.
     *
     * @param string $attributeName
     * @param array $attributeData
     *
     * @return string
     * @throws HtmlException
     */
    protected function prepareDataAttribute(
        $attributeName,
        array $attributeData
    ) {
        if (!is_string($attributeName)) {
            throw new HtmlException(
                'The expected type of the "attributeName" is string. '
                .gettype($attributeName).' given.'
            );
        }

        $preparedAttribute = '';

        foreach ($attributeData as $dataName => $dataValue) {

            if (!is_string($dataName) && !is_numeric($dataName)) {
                throw new HtmlException(
                    'Unexpected type of the data attribute name. String or '
                    .'numeric expected. '.gettype($dataName).' given.'
                );
            }

            $preparedAttribute .= " $attributeName-$dataName=";

            if (is_array($dataValue)) {
                $preparedAttribute .= json_encode($dataValue);

                continue;
            }

            $preparedAttribute .= $this->jsonEncode($dataValue);
        }

        return $preparedAttribute;
    }

    /**
     * Creates string representation of style attribute from array.
     *
     * @param string $attributeName
     * @param array $attributeData
     *
     * @return string
     * @throws HtmlException
     */
    protected function prepareStyleAttribute(
        $attributeName,
        array $attributeData
    ) {
        if (!is_string($attributeName)) {
            throw new HtmlException(
                'The expected type of the "attributeName" is string. '
                .gettype($attributeName).' given.'
            );
        }

        $preparedAttribute = " $attributeName=";

        $styles = [];

        foreach ($attributeData as $styleName => $styleValue) {

            if (!is_string($styleName) || !is_string($styleValue)) {
                throw new HtmlException(
                    'The expected type of the style name and style value is a '
                    .'string. '.gettype($attributeName).' given.'
                );
            }

            $styles[] = "$styleName: $styleValue";
        }

        $preparedAttribute .= $this->jsonEncode(implode('; ', $styles));

        return $preparedAttribute;
    }

    /**
     * Encodes data for using as html attribute value.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function jsonEncode($data)
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_HEX_QUOT
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
        );
    }
}