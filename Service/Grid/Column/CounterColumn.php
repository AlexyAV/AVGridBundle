<?php

namespace AV\GridBundle\Service\Grid\Column;

use AV\GridBundle\Service\Helper\Html;

class CounterColumn extends BaseColumn
{
    /**
     * @var string Default header label for action column.
     */
    protected $label = '#';

    /**
     * Get action buttons html.
     *
     * @param $entityInstance
     * @param $index
     * @param $emptyCellContent
     *
     * @return string
     */
    public function renderCellContent(
        $entityInstance, $index, $emptyCellContent = null
    ) {
        $offset = $this->gridView->getDataSource()->getPagination()
            ->getOffset();

        $index += ($offset + 1);

        return '<td '.Html::prepareTagAttributes(
            $this->contentOptions
        ).'>'. $index .'</td>';
    }
}