<?php

namespace AV\GridBundle\Service\Grid\Pagination;

use AV\GridBundle\Service\Helper\Html;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use AV\GridBundle\Service\Grid\Pagination\Exception\PaginationException;

class PaginationView
{
    /**
     * @var Pagination
     */
    protected $pagination;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ParameterBag
     */
    protected $queryParameters;

    /**
     * @var array Parameter list for the pagination block.
     */
    protected $options = ['class' => 'pagination'];

    /**
     * @var array Parameter list for each pagination link.
     */
    protected $linkOptions = [];

    /**
     * @var string CSS class name for first page button.
     */
    public $firstPageCssClass = 'first';

    /**
     * @var string CSS class name for last page button.
     */
    public $lastPageCssClass = 'last';

    /**
     * @var string CSS class name for previous page button.
     */
    public $prevPageCssClass = 'prev';

    /**
     * @var string CSS class name for next page button.
     */
    public $nextPageCssClass = 'next';

    /**
     * @var string CSS class name for active page button
     */
    public $activePageCssClass = 'active';

    /**
     * @var string CSS class for disabled page button.
     */
    public $disabledPageCssClass = 'disabled';

    /**
     * @var int Max number of buttons to show.
     */
    public $maxButtonCount = 10;

    /**
     * @var bool Whether to show a link to the first page.
     */
    public $showFirstPageLink = false;

    /**
     * @var bool Whether to show a link to the last page
     */
    public $showLastPageLink = false;

    /**
     * @var bool Whether to show a link to the previous page.
     */
    public $showPrevPageLink = true;

    /**
     * @var bool Whether to show a link to the next page
     */
    public $showNextPageLink = true;

    /**
     * @var string Next page default label. Will be used if [[showNextPageLink]]
     * set to true.
     */
    public $nextPageLabel = '&rsaquo;';

    /**
     * @var string Precious page default label. Will be used if
     * [[showPrevPageLink]] set to true.
     */
    public $prevPageLabel = '&lsaquo;';

    /**
     * @var string First page default label. Will be used if
     * [[showFirstPageLink]] set to true.
     */
    public $firstPageLabel = '&laquo;';

    /**
     * @var string Last page default label. Will be used if
     * [[showLastPageLink]] set to true.
     */
    public $lastPageLabel = '&raquo;';

    /**
     * PaginationView constructor.
     *
     * @param RequestStack $request
     * @param Router $router
     */
    public function __construct(
        RequestStack $request,
        Router $router
    ) {
        $currentRequest = $request->getCurrentRequest();

        if ($currentRequest) {
            $this->queryParameters = $currentRequest->query->all();
        }

        $this->router = $router;
    }

    /**
     * Render pagination block.
     *
     * @return string
     * @throws PaginationException
     */
    public function renderPageButtons()
    {
        if (!$this->pagination) {
            throw new PaginationException(
                'Instance of ' . Pagination::class . ' should be specified.'
            );
        }

        $pageCount = $this->pagination->getPageCount();

        if ($pageCount < 2) {
            return '';
        }

        $buttons = [];

        $currentPage = $this->pagination->getCurrentPage();

        if ($this->showFirstPageLink) {
            $buttons[] = $this->createPageButton(
                $this->firstPageLabel,
                0,
                $this->firstPageCssClass,
                $currentPage <= 0,
                false
            );
        }

        if ($this->showPrevPageLink) {
            $buttons[] = $this->createPageButton(
                $this->prevPageLabel,
                max(0, $currentPage - 1),
                $this->prevPageCssClass,
                $currentPage <= 0,
                false
            );
        }

        list($startPage, $endPage) = $this->getPageRange();

        for ($i = $startPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->createPageButton(
                $i + 1,
                $i,
                null,
                false,
                $i == $currentPage
            );
        }

        if ($this->showNextPageLink) {
            $buttons[] = $this->createPageButton(
                $this->nextPageLabel,
                min($currentPage + 1, $pageCount - 1),
                $this->nextPageCssClass,
                $currentPage >= $pageCount - 1,
                false
            );
        }

        if ($this->showLastPageLink) {
            $buttons[] = $this->createPageButton(
                $this->lastPageLabel,
                $pageCount - 1,
                $this->lastPageCssClass,
                $currentPage >= $pageCount - 1,
                false
            );
        }

        return '<ul '.Html::prepareTagAttributes($this->options).'>'
        .implode("\n", $buttons).'</ul>';
    }

    /**
     * Render single pagination block button.
     *
     * @param string $label
     * @param int $page
     * @param string $class
     * @param bool $disabled
     * @param bool $active
     *
     * @return string
     */
    protected function createPageButton(
        $label,
        $page,
        $class,
        $disabled,
        $active
    ) {
        $buttonClassList = [$class];

        if ($active) {
            array_push($buttonClassList, $this->activePageCssClass);
        }

        if ($disabled) {
            array_push($buttonClassList, $this->disabledPageCssClass);
        }

        $buttonOptions = [
            'class' => $buttonClassList,
        ];

        $linkOptions = $this->linkOptions;

        if ($active) {
            $linkOptions = array_merge(
                $linkOptions,
                ['data-page' => $page]
            );
        }

        $link = '<a '.Html::prepareTagAttributes($linkOptions).' href="'
            .$this->createButtonLink($page, $this->pagination->getPageSize())
            .'">'.$label.'</a>';

        return '<li '.Html::prepareTagAttributes($buttonOptions).'>'.$link
        .'</li>';
    }

    /**
     * Create link for pagination button.
     *
     * @param int $pageIndex
     * @param int $pageSize
     * @param bool $absoluteUrl
     *
     * @return string
     */
    public function createButtonLink($pageIndex, $pageSize, $absoluteUrl = true)
    {
        $pageParamName = $this->pagination->getPageParamName();

        $pageSizeParamName = $this->pagination->getPageSizeParam();

        if ($pageIndex > 0) {
            $this->queryParameters[$pageParamName] = $pageIndex + 1;
        } else {
            unset($this->queryParameters[$pageParamName]);
        }

        if ($pageSize != $this->pagination->getDefaultPageSize()) {
            $this->queryParameters[$pageSizeParamName] = $pageSize;
        } else {
            unset($this->queryParameters[$pageSizeParamName]);
        }

        return $this->router->generate(
            $this->pagination->getRoute(),
            $this->queryParameters,
            $absoluteUrl ? Router::ABSOLUTE_URL : Router::ABSOLUTE_PATH
        );
    }

    /**
     * Calculate current pagination pages range.
     *
     * @return array
     */
    protected function getPageRange()
    {
        $pageCount = $this->pagination->getPageCount();

        $beginPage = max(
            0,
            $this->pagination->getCurrentPage() - floor(
                $this->maxButtonCount / 2
            )
        );

        $endPage = $beginPage + $this->maxButtonCount - 1;

        if ($endPage >= $pageCount) {
            $endPage = $pageCount - 1;

            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }

    /**
     * @param Pagination $pagination
     */
    public function setPagination(Pagination $pagination)
    {
        $this->pagination = $pagination;
    }

    /**
     * Set pagination block options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Set parameters for each pagination link.
     *
     * @param array $linkOptions
     */
    public function setLinkOptions(array $linkOptions)
    {
        $this->linkOptions = array_merge($this->linkOptions, $linkOptions);
    }
}