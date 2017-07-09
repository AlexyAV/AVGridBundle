<?php

namespace AV\GridBundle\Twig;

use \Twig_Extension;
use \Twig_Environment;
use AV\GridBundle\Service\Grid\GridView;

class GridExtension extends Twig_Extension
{
    /**
     * @var Twig_Environment
     */
    private $twig;

    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'gridView',
                [$this, 'prepareGridView'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param GridView $gridView
     *
     * @return string
     */
    public function prepareGridView(GridView $gridView)
    {
        $renderParams = [];

        $filterForm = $gridView->getFormBuilder();

        if ($filterForm) {
            $renderParams[$gridView->getId()]
                = $gridView->getFormBuilder()->getForm()->createView();
        }

        return $this->twig->createTemplate($gridView->renderGrid())->render(
            $renderParams
        );
    }

    public function getName()
    {
        return get_class($this);
    }
}