<?php

namespace AV\GridBundle\Service\DataSourceGateway;

use AV\GridBundle\Service\Grid\Sort\Sort;
use AV\GridBundle\Service\Grid\Pagination\Pagination;
use AV\GridBundle\Service\DataSourceGateway\Exception\DataSourceException;

abstract class BaseDataSource
{
    /**
     * @var string Full class name of target entity.
     */
    protected $entityName;

    /**
     * @var mixed
     */
    protected $dataSource;

    /**
     * @var Pagination
     */
    protected $pagination;

    /**
     * @var Sort
     */
    protected $sort;

    /**
     * @param Pagination $pagination
     *
     * @return $this
     */
    public function setPagination(Pagination $pagination)
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * @param Sort $sort
     */
    public function setSort(Sort $sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * @return Sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Get total count of entities.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return count($this->dataSource);
    }

    /**
     * @param string $entityName
     *
     * @return $this
     * @throws DataSourceException
     */
    public function setEntityName($entityName)
    {
        if (!is_string($entityName)) {
            throw new DataSourceException(
                'The expected type of the '.self::class
                .' entity name is string . '.gettype($entityName).' given.'
            );
        }

        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get short class name.
     *
     * @return bool|string
     */
    public function getEntityShortName()
    {
        if (!$this->entityName || !is_string($this->entityName)) {
            return false;
        }

        return substr(
            $this->entityName,
            strrpos($this->entityName, '\\') + 1
        );
    }

    /**
     * Returns list of entity attributes.
     *
     * @return mixed
     */
    abstract public function fetchEntityFields();

    /**
     * Returns prepared set of entities.
     *
     * @return mixed
     */
    abstract public function fetchEntities();
}