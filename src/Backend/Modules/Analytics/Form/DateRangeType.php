<?php

namespace Backend\Modules\Analytics\Form;

use Backend\Core\Engine\Form;
use App\Component\Locale\BackendLanguage;
use Backend\Core\Engine\TwigTemplate;
use App\Component\Model\BackendModel;
use Backend\Modules\Analytics\DateRange\DateRange;

/**
 * A form to change the date range of the analytics module
 */
final class DateRangeType
{
    /** @var Form */
    private $form;

    /** @var DateRange $dateRange */
    private $dateRange;

    public function __construct(string $name, DateRange $dateRange)
    {
        $this->form = new Form($name);
        $this->dateRange = $dateRange;

        $this->build();
    }

    public function parse(TwigTemplate $template): void
    {
        $this->form->parse($template);
        $template->assign('startTimestamp', $this->dateRange->getStartDate());
        $template->assign('endTimestamp', $this->dateRange->getEndDate());
    }

    public function handle(): bool
    {
        $this->form->cleanupFields();

        if (!$this->form->isSubmitted() || !$this->isValid()) {
            return false;
        }

        $fields = $this->form->getFields();

        $newStartDate = BackendModel::getUTCTimestamp($fields['start_date']);
        $newEndDate = BackendModel::getUTCTimestamp($fields['end_date']);

        $this->dateRange->update($newStartDate, $newEndDate);

        return true;
    }

    private function build(): void
    {
        $this->form->addDate(
            'start_date',
            $this->dateRange->getStartDate(),
            'range',
            mktime(0, 0, 0, 1, 1, 2005),
            time()
        );
        $this->form->addDate(
            'end_date',
            $this->dateRange->getEndDate(),
            'range',
            mktime(0, 0, 0, 1, 1, 2005),
            time()
        );
    }

    private function isValid(): bool
    {
        $fields = $this->form->getFields();

        if (!$fields['start_date']->isFilled(BackendLanguage::err('FieldIsRequired')) ||
            !$fields['end_date']->isFilled(BackendLanguage::err('FieldIsRequired'))
        ) {
            return $this->form->isCorrect();
        }

        if (!$fields['start_date']->isValid(BackendLanguage::err('DateIsInvalid')) ||
            !$fields['end_date']->isValid(BackendLanguage::err('DateIsInvalid'))
        ) {
            return $this->form->isCorrect();
        }

        $newStartDate = BackendModel::getUTCTimestamp($fields['start_date']);
        $newEndDate = BackendModel::getUTCTimestamp($fields['end_date']);

        // startdate cannot be before 2005 (earliest valid google startdate)
        if ($newStartDate < mktime(0, 0, 0, 1, 1, 2005)) {
            $fields['start_date']->setError(BackendLanguage::err('DateRangeIsInvalid'));
        }

        // enddate cannot be in the future
        if ($newEndDate > time()) {
            $fields['start_date']->setError(BackendLanguage::err('DateRangeIsInvalid'));
        }

        // enddate cannot be before the startdate
        if ($newStartDate > $newEndDate) {
            $fields['start_date']->setError(BackendLanguage::err('DateRangeIsInvalid'));
        }

        return $this->form->isCorrect();
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }
}
