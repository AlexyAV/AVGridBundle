<?php

namespace AV\GridBundle\Service\Grid;

use AV\GridBundle\Service\Grid\Exception\GridException;
use Symfony\Component\Form\FormBuilder;
use AV\GridBundle\Service\Helper\Html;
use Symfony\Component\Form\FormBuilderInterface;
use AV\GridBundle\Service\Grid\Column\BaseColumn;
use AV\GridBundle\Service\DataSourceGateway\QueryDataSource;
use AV\GridBundle\Service\DataSourceGateway\BaseDataSource;

class GridView
{
    /**
     * @var array List of html attributes that will be applied to grid container.
     */
    protected $containerOptions = ['class' => 'grid-view'];

    /**
     * @var array List of html attributes that will be applied to grid table.
     */
    protected $tableOptions = ['class' => 'table table-bordered table-striped'];

    /**
     * @var string Grid table caption.
     */
    protected $tableCaption;

    /**
     * @var bool Whether the table header row will be shown.
     */
    protected $showHeader = true;

    /**
     * @var array List of html attributes that will be applied to grid table
     * header row.
     */
    protected $headerRowOptions = [];

    /**
     * @var array Options for grid table rows.
     */
    protected $rowOptions = [];

    /**
     * @var array List of html attributes that will be applied row that contains
     * filters.
     */
    public $filterRowOptions = ['class' => 'filters'];

    /**
     * @var string Value that will be used for empty table cell.
     */
    protected $emptyCell = '&nbsp;';

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var object|null Instance of target entity that will be used for creating
     * filter fields.
     */
    protected $filterEntity;

    /**
     * @var string Target url which will accept filter data. Current route will
     * be used by default.
     */
    protected $filterUrl;

    /**
     * @var string
     */
    protected $gridIdPrefix = 'grid_';

    /**
     * @var string Current unique grid id.
     */
    protected $gridId;

    /**
     * @var int Unique grid id
     */
    protected static $gridCounter = 0;

    /**
     * @var QueryDataSource
     */
    protected $dataSource;

    /**
     * @var FormBuilder
     */
    public $formBuilder;

    /**
     * Get grid id. If value was not set yet method generates new id based on
     * static counter so id will be unique for each new grid instance.
     *
     * @return string
     */
    public function getId()
    {
        if ($this->gridId === null) {
            $this->gridId = $this->gridIdPrefix.static::$gridCounter++;
        }

        return $this->gridId;
    }

    /**
     * @param BaseColumn $column
     *
     * @return $this
     */
    public function addColumn(BaseColumn $column)
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Renders full grid content.
     *
     * @return string
     */
    public function renderGrid()
    {
        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->getId();
        }

        $gridContainerOptions = Html::prepareTagAttributes(
            $this->containerOptions
        );

        return '<div '.$gridContainerOptions.'>'.$this->renderTable().'</div>';
    }

    /**
     * Renders grid table.
     *
     * @return string
     */
    protected function renderTable()
    {
        $tableOptions = Html::prepareTagAttributes($this->tableOptions);

        $tableHtml = '<table '.$tableOptions.'>'.$this->renderCaption()
            .$this->renderTableHeader();

        $tableHtml .= $this->renderTableFilter();

        $tableHtml .= $this->renderTableBody().'</table>';

        return $tableHtml;
    }

    /**
     * Renders table body.
     *
     * @return string
     */
    protected function renderTableBody()
    {
        $tableBody = '<tbody>';

        $dataEntities = $this->dataSource->fetchEntities();

        foreach ($dataEntities as $index => $entity) {
            $tableBody .= $this->renderTableRow($entity, $index);
        }

        $tableBody .= '</tbody>';

        return $tableBody;
    }

    /**
     * Renders table caption. Caption value will not be encoded.
     *
     * @return string
     */
    protected function renderCaption()
    {
        if ($this->tableCaption) {
            return '<caption>'.$this->tableCaption.'</caption>';
        }

        return '';
    }

    /**
     * Renders table header row.
     *
     * @return string
     */
    public function renderTableHeader()
    {
        if (!$this->showHeader) {
            return '';
        }

        $tableHeader = '<thead><tr '
            .Html::prepareTagAttributes($this->headerRowOptions).' >';

        /** @var BaseColumn $column */
        foreach ($this->columns as $column) {
            $tableHeader .= $column->renderHeaderCellContent();
        }

        $tableHeader .= "</tr></thead>";

        return $tableHeader;
    }

    /**
     * @return string
     */
    public function renderTableFilter()
    {
        if (!$this->filterEntity) {
            return '';
        }

        $this->filterRowOptions['id'] = $this->getId().'_filters';

        $tableHeader = '<tr '.Html::prepareTagAttributes(
                $this->filterRowOptions
            ).'>';

        $tableHeader .= '{{ form_start('.$this->getId().') }}';

        /** @var BaseColumn $column */
        foreach ($this->columns as $column) {
            $tableHeader .= $column->renderFilterCellContent();
        }

        $tableHeader .= '{{ form_end('.$this->getId().') }}';

        $tableHeader .= "</tr>";

        return $tableHeader;
    }

    /**
     * Renders table body row.
     *
     * @param $entity
     * @param $index
     *
     * @return string
     */
    public function renderTableRow($entity, $index)
    {
        $tableRaw = '<tr '.Html::prepareTagAttributes($this->rowOptions).' >';

        /** @var BaseColumn $column */
        foreach ($this->columns as $column) {

            $tableRaw .= $column->renderCellContent(
                $entity,
                $index,
                $this->emptyCell
            );
        }

        $tableRaw .= '</tr>';

        return $tableRaw;
    }

    /**
     * Set grid table caption.
     *
     * @param string $tableCaption
     *
     * @return $this
     * @throws GridException
     */
    public function setTableCaption($tableCaption)
    {
        if (!is_string($tableCaption)) {
            throw new GridException(
                'The expected type of the '.self::class
                .' table caption is string . '.gettype($tableCaption).' given.'
            );
        }

        $this->tableCaption = trim($tableCaption);

        return $this;
    }

    /**
     * @param array $rowOptions
     *
     * @return $this
     */
    public function setRowOptions(array $rowOptions)
    {
        $this->rowOptions = $rowOptions;

        return $this;
    }

    /**
     * @param boolean $showHeader
     *
     * @return $this
     */
    public function setShowHeader($showHeader)
    {
        $this->showHeader = (bool)$showHeader;

        return $this;
    }

    /**
     * @param array $tableOptions
     *
     * @return $this
     */
    public function setTableOptions(array $tableOptions)
    {
        $this->tableOptions = array_merge($this->tableOptions, $tableOptions);

        return $this;
    }

    /**
     * @return QueryDataSource
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @return $this
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder)
    {
        $this->formBuilder = $formBuilder;

        return $this;
    }

    /**
     * @return FormBuilder
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * @return string
     */
    public function getEmptyCell()
    {
        return $this->emptyCell;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param BaseDataSource $dataSource
     *
     * @return $this
     */
    public function setDataSource(BaseDataSource $dataSource)
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * @param object $filterEntity
     *
     * @throws GridException
     */
    public function setFilterEntity($filterEntity)
    {
        if (!is_object($filterEntity)) {
            throw new GridException(
                'The expected type of the '.self::class
                .' filter entity is object . '.gettype($filterEntity).' given.'
            );
        }

        $this->filterEntity = $filterEntity;
    }

    /**
     * @return object
     */
    public function getFilterEntity()
    {
        return $this->filterEntity;
    }

    /**
     * @param array $containerOptions
     *
     * @return $this
     */
    public function setContainerOptions(array $containerOptions)
    {
        $this->containerOptions = array_merge(
            $this->containerOptions,
            $containerOptions
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getFilterUrl()
    {
        return $this->filterUrl;
    }

    /**
     * @param string $filterUrl
     *
     * @return $this
     * @throws GridException
     */
    public function setFilterUrl($filterUrl)
    {
        if (!is_string($filterUrl)) {
            throw new GridException(
                'The expected type of the '.self::class
                .' filter url is string. '.gettype($filterUrl).' given.'
            );
        }

        $this->filterUrl = $filterUrl;

        return $this;
    }
}