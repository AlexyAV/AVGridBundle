<?php

namespace AV\GridBundle\Service\Grid;

use AV\GridBundle\Service\Grid\Column\BaseColumn;
use AV\GridBundle\Service\Grid\Exception\GridException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use AV\GridBundle\Service\DataSourceGateway\BaseDataSource;
use AV\GridBundle\Service\DataSourceGateway\QueryDataSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GridFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var GridView
     */
    protected $gridView;

    /**
     * @var string
     */
    protected $defaultColumnService = 'av_grid.column';

    /**
     * GridFactory constructor.
     *
     * @param GridView $gridView
     * @param ContainerInterface $container
     */
    public function __construct(
        GridView $gridView,
        ContainerInterface $container
    ) {
        $this->gridView = $gridView;

        $this->container = $container;
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @throws \Exception
     */
    protected function initColumns(array $columns)
    {
        foreach ($columns as $columnData) {

            if (array_key_exists('service', $columnData)) {
                $column = $this->container->get($columnData['service']);

                unset($columnData['service']);

            } else {
                $column = $this->container->get(
                    $this->defaultColumnService
                );
            }

            foreach ($columnData as $paramName => $paramValue) {
                $methodName = 'set'.ucfirst($paramName);

                if (!method_exists($column, $methodName)) {
                    throw new \Exception('Column has no property '.$paramName);
                }

                $column->$methodName($paramValue);
            }

            if ($column->isVisible()) {
                $column->setGridView($this->gridView);

                $this->gridView->addColumn($column);
            }
        }

        return $this;
    }

    /**
     * @param array $gridViewData
     *
     * @return $this
     */
    protected function setGridParameters(array $gridViewData)
    {
        foreach ($gridViewData as $parameterName => $value) {

            $setterMethodName = 'set'.ucfirst($parameterName);

            if (method_exists($this->gridView, $setterMethodName)) {
                call_user_func(
                    [$this->gridView, $setterMethodName],
                    $value
                );
            }
        }

        return $this;
    }

    /**
     * @param array $gridViewData
     *
     * @return $this
     * @throws GridException
     */
    protected function setDataSource(array $gridViewData)
    {
        if (empty($gridViewData['dataSource'])) {
            throw new GridException(
                'Grid view data source should be specified.'
            );
        }

        $dataSource = $gridViewData['dataSource'];

        if (!($dataSource instanceof BaseDataSource)) {
            throw new GridException(
                'Data source should be instance of '.BaseDataSource::class.'. '
                .gettype($dataSource).' given.'
            );
        }

        $this->gridView->setDataSource($dataSource);

        return $this;
    }

    /**
     * @return $this
     * @throws GridException
     */
    protected function setFormBuilder()
    {
        $entityInstance = $this->gridView->getFilterEntity();

        if (!$entityInstance) {
            return $this;
        }

        if (!is_object($entityInstance)) {
            throw new GridException(
                'Entity instance should be specified to use filters.'
            );
        }

        $formBuilder = $this->container->get('form.factory')
            ->createNamedBuilder(
                $this->gridView->getDataSource()->getEntityShortName(),
                \Symfony\Component\HttpKernel\Kernel::VERSION_ID < 20800 ? 'form' : FormType::class,
                $entityInstance,
                [
                    'csrf_protection' => false,
                    'allow_extra_fields' => true,
                ]
            );

        $request = $this->container->get('request_stack')->getCurrentRequest();

        $filterUrl = $this->gridView->getFilterUrl() ?:
            $this->container->get('router')->generate(
                $request->get('_route'),
                $request->query->all()
            );

        $formBuilder->setMethod('GET')->setAction($filterUrl);

        $this->gridView->setFormBuilder($formBuilder);

        /** @var BaseColumn $column */
        foreach ($this->gridView->getColumns() as $column) {
            $column->initColumnFilter();
        }

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        return $this;
    }

    /**
     * @param array $gridViewData
     *
     * @return GridView
     */
    public function prepareGridView(array $gridViewData)
    {
        $this->setGridParameters($gridViewData);

        $columns = $this->prepareColumns($gridViewData);

        if ($columns) {
            $this->initColumns($columns);
        }

        $this->setFormBuilder();

        return $this->gridView;
    }

    /**
     * @param array $gridViewData
     *
     * @return array
     */
    protected function prepareColumns(array $gridViewData)
    {
        $columns = [];

        $columns[] = ['service' => 'av_grid.counter_column'];

        if (
            !empty($gridViewData['columns'])
            && is_array($gridViewData['columns'])
        ) {
            $columns = array_merge($columns, $gridViewData['columns']);

            return $columns;
        }

        $entityAttributes = $gridViewData['dataSource']->fetchEntityFields();

        foreach ($entityAttributes as $attribute) {
            $columns[] = ['attributeName' => $attribute];
        }

        if ($gridViewData['dataSource'] instanceof QueryDataSource) {
            $columns[] = ['service' => 'av_grid.action_column'];
        }

        return $this->filterColumn($gridViewData, $columns);
    }

    /**
     * @param array $gridViewData
     * @param array $columns
     *
     * @return mixed
     */
    protected function filterColumn(array $gridViewData, array $columns)
    {
        if (
            empty($gridViewData['columnOptions']['excludeAttributes'])
            || !($gridViewData['dataSource'] instanceof QueryDataSource)
        ) {
            return $columns;
        }

        $excludedColumns = $gridViewData['columnOptions']['excludeAttributes'];

        foreach ($columns as $key => $column) {

            if (empty($column['attributeName'])) {
                continue;
            }

            if (in_array($column['attributeName'], $excludedColumns)) {
                unset($columns[$key]);
            }
        }

        return $columns;
    }
}