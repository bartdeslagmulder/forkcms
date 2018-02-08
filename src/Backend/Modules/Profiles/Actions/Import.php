<?php

namespace Backend\Modules\Profiles\Actions;

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Form as BackendForm;
use App\Component\Locale\BackendLanguage;
use App\Component\Model\BackendModel;
use Backend\Modules\Profiles\Engine\Model as BackendProfilesModel;
use Backend\Core\Engine\Csv;

/**
 * This is the add-action, it will display a form to add a new profile.
 */
class Import extends BackendBaseActionAdd
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
        // get group values for dropdown
        $ddmValues = BackendProfilesModel::getGroupsForDropDown(0);

        // create form and elements
        $this->form = new BackendForm('import');
        $this->form->addDropdown('group', $ddmValues);
        $this->form->addFile('file');
        $this->form->addCheckbox('overwrite_existing');
    }

    private function validateForm(): void
    {
        if (!$this->form->isSubmitted()) {
            return;
        }
        $this->form->cleanupFields();

        // get fields
        $ddmGroup = $this->form->getField('group');
        $fileFile = $this->form->getField('file');
        $csv = [];

        // validate input
        $ddmGroup->isFilled(BackendLanguage::getError('FieldIsRequired'));
        if ($fileFile->isFilled(BackendLanguage::err('FieldIsRequired'))) {
            if ($fileFile->isAllowedExtension(['csv'], sprintf(BackendLanguage::getError('ExtensionNotAllowed'), 'csv'))) {
                $csv = Csv::fileToArray($fileFile->getTempFileName());
                if ($csv === false) {
                    $fileFile->addError(BackendLanguage::getError('InvalidCSV'));
                }
            }
        }

        if (!$this->form->isCorrect()) {
            return;
        }

        // import the profiles
        $overwrite = $this->form->getField('overwrite_existing')->isChecked();
        $statistics = BackendProfilesModel::importCsv(
            $csv,
            $ddmGroup->getValue(),
            $overwrite
        );

        // build redirect url with the right message
        $redirectUrl = BackendModel::createUrlForAction('index') . '&report=';
        $redirectUrl .= $overwrite ?
            'profiles-imported-and-updated' :
            'profiles-imported';
        $redirectUrl .= '&var[]=' . $statistics['count']['inserted'];
        $redirectUrl .= '&var[]=' . $statistics['count']['exists'];

        // everything is saved, so redirect to the overview
        $this->redirect($redirectUrl);
    }
}
