<?php

namespace Backend\Modules\Location\Actions;

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Form as BackendForm;
use App\Component\Locale\BackendLanguage;
use App\Component\Model\BackendModel;
use Backend\Modules\Location\Engine\Model as BackendLocationModel;
use Symfony\Component\Intl\Intl as Intl;

/**
 * This is the add-action, it will display a form to create a new item
 */
class Add extends BackendBaseActionAdd
{
    public function execute(): void
    {
        parent::execute();
        $this->loadForm();
        $this->validateForm();
        $this->parse();
        $this->display();
    }

    private function loadForm(): void
    {
        $this->form = new BackendForm('add');
        $this->form->addText('title', null, null, 'form-control title', 'form-control danger title')->makeRequired();
        $this->form->addText('street')->makeRequired();
        $this->form->addText('number')->makeRequired();
        $this->form->addText('zip')->makeRequired();
        $this->form->addText('city')->makeRequired();
        $this->form->addDropdown('country', Intl::getRegionBundle()->getCountryNames(BackendLanguage::getInterfaceLanguage()), 'BE')->makeRequired();
    }

    private function validateForm(): void
    {
        if ($this->form->isSubmitted()) {
            $this->form->cleanupFields();

            // validate fields
            $this->form->getField('title')->isFilled(BackendLanguage::err('TitleIsRequired'));
            $this->form->getField('street')->isFilled(BackendLanguage::err('FieldIsRequired'));
            $this->form->getField('number')->isFilled(BackendLanguage::err('FieldIsRequired'));
            $this->form->getField('zip')->isFilled(BackendLanguage::err('FieldIsRequired'));
            $this->form->getField('city')->isFilled(BackendLanguage::err('FieldIsRequired'));

            if ($this->form->isCorrect()) {
                // build item
                $item = [];
                $item['language'] = BackendLanguage::getWorkingLanguage();
                $item['title'] = $this->form->getField('title')->getValue();
                $item['street'] = $this->form->getField('street')->getValue();
                $item['number'] = $this->form->getField('number')->getValue();
                $item['zip'] = $this->form->getField('zip')->getValue();
                $item['city'] = $this->form->getField('city')->getValue();
                $item['country'] = $this->form->getField('country')->getValue();

                // define coordinates
                $coordinates = BackendLocationModel::getCoordinates(
                    $item['street'],
                    $item['number'],
                    $item['city'],
                    $item['zip'],
                    $item['country']
                );

                // define latitude and longitude
                $item['lat'] = $coordinates['latitude'];
                $item['lng'] = $coordinates['longitude'];

                // insert the item
                $item['id'] = BackendLocationModel::insert($item);

                // redirect
                $this->redirect(
                    BackendModel::createUrlForAction('Edit') . '&id=' . $item['id'] .
                    '&report=added&var=' . rawurlencode($item['title'])
                );
            }
        }
    }
}
