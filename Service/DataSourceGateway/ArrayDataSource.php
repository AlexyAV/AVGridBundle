<?php

namespace AV\GridBundle\Service\DataSourceGateway;

use AV\GridBundle\Service\Grid\Sort\Sort;

class ArrayDataSource extends BaseDataSource
{
    /**
     * @inheritdoc
     */
    public function fetchEntities()
    {
        if (!$this->dataSource) {
            return [];
        }

        $models = $this->dataSource;

        if (($sort = $this->getSort()) !== false) {
            $models = $this->sortEntities($models, $sort);
        }

        if (($pagination = $this->getPagination()) !== false) {

            $pagination->setTotalCount($this->getTotalCount());

            if ($pagination->getPageSize() > 0) {
                $models = array_slice(
                    $models,
                    $pagination->getOffset(),
                    $pagination->getLimit()
                );
            }
        }

        return $models;
    }

    /**
     * Get list of single data row keys.
     *
     * @return array
     */
    public function fetchEntityFields()
    {
        if (empty($this->dataSource)) {
            return [];
        }

        return array_keys(current($this->dataSource));
    }

    /**
     * @param array $dataSource
     */
    public function setDataSource(array $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * Sorts the data models according to the given sort definition.
     *
     * @param array $entities
     * @param Sort $sort
     * @param int $sortFlag
     *
     * @return array the sorted data models
     */
    protected function sortEntities($entities, $sort, $sortFlag = SORT_REGULAR)
    {
        $orders = $sort->fetchOrders();

        if (!empty($orders)) {
            $sortTypes = [
                Sort::ASC => SORT_ASC,
                Sort::DESC => SORT_DESC,
            ];

            $args = [];

            foreach ($orders as $keyName => $sortType) {
                $args[] = array_column($entities, $keyName);
                $args[] = $sortTypes[$sortType];
                $args[] = $sortFlag;
            }

            $args[] = &$entities;

            call_user_func_array('array_multisort', $args);

            return array_pop($args);
        }
        return $entities;
    }
}