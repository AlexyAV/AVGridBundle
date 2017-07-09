<?php

namespace AV\GridBundle\Service\Grid\Column;

use AV\GridBundle\Service\Helper\Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use AV\GridBundle\Service\Grid\Column\Exception\ActionColumnException;

class ActionColumn extends BaseColumn
{
    const SHOW = 'show';

    const EDIT = 'edit';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string Default header label for action column.
     */
    protected $label = 'Actions';

    /**
     * @var string Default column cell data format
     */
    protected $format = ColumnFormat::RAW_FORMAT;

    /**
     * @var array List of buttons to show. If this parameter was not change then
     * default buttons with url based on current url will be used. Buttons links
     * can be specified as string or callback function.
     *
     * If we use callable function it will be called with two parameters:
     * 1. $entity object Entity used in current row.
     * 2. $url    string Default url for this action. Can be changed.
     * 3. $index  int    Index of current entity.
     * Example of callback:
     * 'buttons' => [
     *     ActionColumn::EDIT => function ($entity, $url) {
     *         // Return link depends on some entity condition
     *         return $entity->isHidden() ? $url : '';
     *     },
     * ]
     *
     * In case of string we simply specify new url.
     * Example of string:
     * 'buttons' => [
     *     ActionColumn::SHOW => 'your_custom_url'
     * ]
     */
    protected $buttons = [];

    /**
     * @var array List of buttons that could be hidden. If this parameter was
     * not change then all buttons will be shown. Visibility condition can be
     * specified as boolean value or callback function.
     *
     * If we use callable function it will be called with two parameters:
     * 1. $entity object Entity used in current row.
     * 2. $url string Default url for this action. Can be changed.
     * Example of callback:
     * 'hiddenButtons' => [
     *     ActionColumn::EDIT => function ($entity, $url) {
     *         // If callback function returns true then button will be hidden
     *         return $entity->isActive();
     *     },
     * ]
     *
     * Example of boolean expression:
     * 'hiddenButtons' => [
     *     ActionColumn::SHOW => true
     * ]
     */
    protected $hiddenButtons = [];

    /**
     * @var array List of buttons icons.
     */
    protected $buttonsLabel = [
        self::SHOW => 'eye-open',
        self::EDIT => 'pencil',
    ];

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
        $entityInstance,
        $index,
        $emptyCellContent = null
    ) {
        $buttonsHtml = implode(
            '',
            $this->renderButtons($entityInstance, $index)
        );

        if (!$buttonsHtml) {
            $buttonsHtml = $emptyCellContent;
        }

        return '<td '.Html::prepareTagAttributes(
            $this->contentOptions
        ).'>'.$buttonsHtml.'</td>';
    }

    /**
     * Check if specified button should be hidden.
     *
     * @param string $buttonName
     * @param string $buttonUrl
     * @param object $entityInstance
     *
     * @return bool
     */
    protected function isButtonHidden(
        $buttonName,
        $buttonUrl,
        $entityInstance
    ) {
        if (empty($this->hiddenButtons[$buttonName])) {
            return false;
        }

        $isHiddenExpression = $this->hiddenButtons[$buttonName];

        if (is_callable($isHiddenExpression)) {

            $isHidden = call_user_func_array(
                $isHiddenExpression,
                [$entityInstance, $buttonUrl]
            );
        } else {
            $isHidden = (bool)$isHiddenExpression;
        }

        return $isHidden;
    }

    /**
     * Create url based on current requested uri. Will be used if custom url
     * was not specified.
     *
     * @param string $actionName
     * @param object $entityInstance
     *
     * @return string
     */
    public function createDefaultButtonUrl($actionName, $entityInstance)
    {
        if (!method_exists($entityInstance, 'getId')) {
            return '';
        }

        return $this->request->getBaseUrl().$this->request->getPathInfo()
        .$entityInstance->getId().'/'.$actionName;
    }

    /**
     * Render all buttons.
     *
     * @param object $entityInstance
     * @param int $index
     *
     * @return array
     */
    protected function renderButtons($entityInstance, $index)
    {
        $defaultButtons = [self::SHOW => '', self::EDIT => ''];

        $this->buttons = array_merge($defaultButtons, $this->buttons);

        $buttons = [];

        foreach ($this->buttons as $buttonName => $buttonUrl) {

            $defaultButtonUrl = $this->createDefaultButtonUrl(
                $buttonName,
                $entityInstance
            );

            $this->checkButtonUrl($buttonUrl);

            if (is_callable($buttonUrl)) {
                $buttonUrl = call_user_func_array(
                    $buttonUrl,
                    [$entityInstance, $defaultButtonUrl, $index]
                );
            }

            if (!$buttonUrl) {
                $buttonUrl = $defaultButtonUrl;
            }

            if (
            $this->isButtonHidden($buttonName, $buttonUrl, $entityInstance)
            ) {
                continue;
            }

            $buttons[] = $this->renderButton($buttonName, $buttonUrl);
        }

        return $buttons;
    }

    /**
     * Validation of type of certain button url value.
     *
     * @param mixed $buttonUrlData
     *
     * @return bool
     * @throws \Exception
     */
    protected function checkButtonUrl($buttonUrlData)
    {
        if (!is_callable($buttonUrlData) && !is_string($buttonUrlData)) {
            throw new ActionColumnException(
                'Action column button url can contain string value or callable. '
                .gettype($buttonUrlData).' given.'
            );
        }

        return true;
    }

    /**
     * Render single button.
     *
     * @param string $buttonName
     * @param string $buttonLink
     *
     * @return string
     */
    protected function renderButton($buttonName, $buttonLink)
    {
        return '<a href="'.$buttonLink.'"><span class="glyphicon glyphicon-'
        .$this->buttonsLabel[$buttonName]
        .'" aria-hidden="true">&nbsp;</span></a>';
    }

    /**
     * @param array $buttons
     *
     * @return $this
     */
    public function setButtons(array $buttons)
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * @param RequestStack $requestStack
     *
     * @return $this
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();

        return $this;
    }

    /**
     * @param array $hiddenButtons
     *
     * @return $this
     */
    public function setHiddenButtons(array $hiddenButtons)
    {
        $this->hiddenButtons = $hiddenButtons;

        return $this;
    }
}