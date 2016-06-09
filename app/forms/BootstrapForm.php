<?php

namespace Libs;


class BootstrapForm
{

    /**
     * @param \Nette\Application\UI\Form $form
     * @return \Nette\Application\UI\Form
     */
    public static function makeBootstrap(\Nette\Application\UI\Form $form)
    {
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-3 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';

        $form->getElementPrototype()->class('form-horizontal');

        foreach ($form->getControls() as $control) {
            if ($control instanceof \Nette\Forms\Controls\Button) {
                $control->setAttribute('class', empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
                $usedPrimary = true;

            } elseif ($control instanceof \Nette\Forms\Controls\TextBase || $control instanceof \Nette\Forms\Controls\SelectBox
                || $control instanceof \Nette\Forms\Controls\MultiSelectBox
            ) {
                $control->setAttribute('class', 'form-control');

            } elseif ($control instanceof \Nette\Forms\Controls\Checkbox || $control instanceof \Nette\Forms\Controls\CheckboxList
                || $control instanceof \Nette\Forms\Controls\RadioList
            ) {
                $control->getSeparatorPrototype()->setName('div')->class($control->getControlPrototype()->type);
            }
        }

        return $form;
    }
}