<?php

namespace AV\GridBundle\Service\Helper;

class Html
{
    /**
     * @var array List of [data] type attributes
     */
    public static $dataAttributes = ['data', 'data-ng', 'ng'];

    /**
     * Converts list of tag attributes from array to encoded string
     * representation.
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function prepareTagAttributes(array $attributes)
    {
        $preparedHtml = '';

        foreach ($attributes as $attributeName => $attributeData) {

            if (is_bool($attributeData)) {
                $preparedHtml .= "$attributeName ";

                continue;
            }

            if (is_array($attributeData)) {

                if (in_array($attributeName, static::$dataAttributes)) {

                    foreach ($attributeData as $dataName => $dataValue) {

                        $preparedHtml .= " $attributeName-$dataName=";

                        if (is_array($dataValue)) {
                            $preparedHtml .= json_encode($dataValue);
                        } else {
                            $preparedHtml .= static::encode($dataValue);
                        }
                    }
                } elseif ($attributeName === 'class') {
                    $preparedHtml .= "$attributeName=" . static::encode(
                        implode(' ', $attributeData)
                    );
                } elseif ($attributeName === 'style') {
                    $preparedHtml .= " $attributeName=";

                    $styles = [];

                    foreach ($attributeData as $styleName => $styleValue) {
                        $styles[] = "$styleName: $styleValue";
                    }

                    $preparedHtml .= static::encode(implode('; ', $styles));
                } else {
                    $preparedHtml .= " $attributeName='"
                        . static::encode($attributeData) . "' ";
                }
            } else {
                $preparedHtml .= " $attributeName="
                    . json_encode($attributeData);
            }
        }

        return trim($preparedHtml);
    }

    /**
     * Encodes data for using as html attribute value.
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function encode($data)
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