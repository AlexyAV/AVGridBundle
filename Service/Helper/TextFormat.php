<?php

namespace AV\GridBundle\Service\Helper;

class TextFormat
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function camelCaseToWord($value)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                'String expected. '.gettype($value).' given.'
            );
        }

        return ucfirst(
            trim(
                str_replace(
                    ['_', '-', '.'],
                    ' ',
                    preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $value)
                )
            )
        );
    }
}