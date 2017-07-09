<?php

namespace AV\GridBundle\Twig;

use \Twig_Extension;
use AV\GridBundle\Twig\Exception\GridTwigException;
use AV\GridBundle\Service\Grid\Pagination\Pagination;
use AV\GridBundle\Service\Grid\Pagination\PaginationView;

class PaginationExtension extends Twig_Extension
{
    /**
     * @var PaginationView
     */
    private $paginationView;

    /**
     * PaginationExtension constructor.
     *
     * @param PaginationView $paginationView
     */
    public function __construct(PaginationView $paginationView)
    {
        $this->paginationView = $paginationView;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'gridPagination',
                [$this, 'init'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Render pagination block.
     *
     * @param Pagination $pagination instance of Pagination class
     * @param array $paginationOptions list of PaginationView class options
     *
     * @return string
     * @throws \Exception
     */
    public function init(Pagination $pagination, array $paginationOptions = [])
    {
        $this->paginationView->setPagination($pagination);

        foreach ($paginationOptions as $optionName => $value) {

            $paginationView = new \ReflectionObject($this->paginationView);

            try {
                if ($paginationView->getProperty($optionName)->isPublic()) {
                    $this->paginationView->$optionName = $value;

                    continue;
                }
            } catch (\Exception $a) {
                throw new GridTwigException(
                    $a->getMessage() . ' in ' . PaginationView::class
                );
            }

            $setterMethodName = 'set' . ucfirst($optionName);

            if ($paginationView->hasMethod($setterMethodName)) {
                $this->paginationView->$setterMethodName($value);
            }
        }

        return $this->paginationView->renderPageButtons();
    }

    public function getName()
    {
        return get_class($this);
    }
}