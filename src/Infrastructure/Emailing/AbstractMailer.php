<?php

namespace Osds\DDDCommon\Infrastructure\Emailing;

use Osds\DDDCommon\Infrastructure\View\ViewInterface;

abstract class AbstractMailer implements EmailServiceInterface
{

    /**
     * @var ViewInterface
     */
    protected $view;

    public function __construct(ViewInterface $view)
    {
        $this->view = $view;
    }

    public function setTemplate($template)
    {
        $this->view->setTemplate($template);
    }

    public function setTemplateVariables($variables)
    {
        $this->view->setVariables($variables);
    }

}