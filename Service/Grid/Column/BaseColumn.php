<?php

namespace AV\GridBundle\Service\Grid\Column;

use AV\GridBundle\Service\Helper\Html;
use AV\GridBundle\Service\Grid\GridView;
use AV\GridBundle\Service\Grid\Column\Exception\ColumnException;

abstract class BaseColumn
{
    /**
     * @var array List of options and attributes that will be applied to header
     * row
     */
    protected $headerOptions = [];

    /**
     * @var bool Whether the header will be encoded
     */
    protected $encodeLabel = false;

    /**
     * @var string Column header label
     */
    protected $label;

    /**
     * @var string|callable Column cell content. This parameter can contain
     * string value or callback function.
     *
     * Example with string value:
     * 'content' => 'some value',
     *
     * Callable function takes two parameters:
     *  - $entity - instance of entity
     *  - $rowIndex - index of current row
     * Example:
     * 'content' => function ($entity, $rowIndex) {
     *     return $entity->getCustomFieldValue();
     * },
     */
    protected $content;

    /**
     * @var array|callable List of option that will be applied to content cell
     */
    protected $contentOptions = [];

    /**
     * @var array List of option that will be applied to filter cell
     */
    protected $filterOptions = [];

    /**
     * @var bool|callable Whether the column should be visible. Parameter can
     * accept bool value or callable.
     *
     * Callable example:
     * 'content' => function () use ($user) {
     *     return // check view permission
     * },
     */
    protected $visible = true;

    /**
     * @var ColumnFormat
     */
    protected $columnFormat;

    /**
     * @var string Default format of column cell data
     */
    protected $format = ColumnFormat::RAW_FORMAT;

    /**
     * @var GridView
     */
    protected $gridView;

    /**
     * @var bool Whether the column is sortable
     */
    protected $sortable = true;

    /**
     * @var Html
     */
    protected $html;

    /**
     * Column constructor.
     *
     * @param ColumnFormat $columnFormat
     */
    public function __construct(ColumnFormat $columnFormat)
    {
        $this->columnFormat = $columnFormat;
    }

    /**
     * @return string
     */
    public function getHeaderCellContent()
    {
        return $this->label;
    }

    /**
     * Renders content of header cell.
     *
     * @return string
     */
    public function renderHeaderCellContent()
    {
        $headerCellContent = $this->getHeaderCellContent();

        if ($this->encodeLabel) {
            $headerCellContent = $this->columnFormat->format(
                $headerCellContent,
                ColumnFormat::TEXT_FORMAT
            );
        }

        return "<th ".$this->html->prepareTagAttributes(
            $this->headerOptions
        ).">".$headerCellContent."</th>";
    }

    /**
     * @return bool
     */
    public function initColumnFilter()
    {
        return false;
    }

    /**
     * Renders column filter cell content.
     *
     * @return string
     */
    public function renderFilterCellContent()
    {
        return '<td '.$this->html->prepareTagAttributes(
            $this->filterOptions
        ).'>'.$this->gridView->getEmptyCell().'</td>';
    }

    /**
     * Renders column cell content.
     *
     * @param mixed $entityInstance
     * @param int $index
     * @param mixed $emptyCellContent
     *
     * @return
     */
    abstract public function renderCellContent(
        $entityInstance,
        $index,
        $emptyCellContent = null
    );

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @param array|callable $contentOptions
     *
     * @return $this
     * @throws ColumnException
     */
    public function setContentOptions($contentOptions)
    {
        if (!is_array($contentOptions) && !is_callable($contentOptions)) {
            throw new ColumnException(
                'Grid column content options should be an array or callable. '
                .gettype($contentOptions).' given.'
            );
        }

        if (is_callable($contentOptions)) {
            $contentOptions = call_user_func($contentOptions);

            if (!is_array($contentOptions)) {
                throw new ColumnException(
                    'Grid column content options '
                    .'function should return an array.'
                );
            }
        }

        $this->contentOptions = $contentOptions;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param callable $enabled
     *
     * @return $this
     */
    public function setVisible($enabled)
    {
        if ($enabled instanceof \Closure) {
            $this->visible = call_user_func($enabled);
        } else {
            $this->visible = (bool)$enabled;
        }

        return $this;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @param GridView $gridView
     */
    public function setGridView(GridView $gridView)
    {
        $this->gridView = $gridView;
    }

    /**
     * @param bool $sortable
     *
     * @return $this
     */
    public function setSortable($sortable)
    {
        $this->sortable = (bool)$sortable;

        return $this;
    }

    /**
     * @param Html $html
     *
     * @return $this
     */
    public function setHtml(Html $html)
    {
        $this->html = $html;

        return $this;
    }
}