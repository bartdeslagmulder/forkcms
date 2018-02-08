<?php

namespace Backend\Modules\Blog\Actions;

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use App\Component\Locale\BackendLanguage;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use App\Component\Model\BackendModel;
use Backend\Core\Engine\DataGridDatabase as BackendDataGridDatabase;
use Backend\Modules\Blog\Engine\Model as BackendBlogModel;

/**
 * This is the categories-action, it will display the overview of blog categories
 */
class Categories extends BackendBaseActionIndex
{
    public function execute(): void
    {
        parent::execute();
        $this->loadDataGrid();
        $this->parse();
        $this->display();
    }

    private function loadDataGrid(): void
    {
        // create datagrid
        $this->dataGrid = new BackendDataGridDatabase(
            BackendBlogModel::QUERY_DATAGRID_BROWSE_CATEGORIES,
            ['active', BackendLanguage::getWorkingLanguage()]
        );

        // set headers
        $this->dataGrid->setHeaderLabels([
            'num_items' => \SpoonFilter::ucfirst(BackendLanguage::lbl('Amount')),
        ]);

        // sorting columns
        $this->dataGrid->setSortingColumns(['title', 'num_items'], 'title');

        // convert the count into a readable and clickable one
        $this->dataGrid->setColumnFunction(
            [__CLASS__, 'setClickableCount'],
            ['[num_items]', BackendModel::createUrlForAction('Index') . '&amp;category=[id]'],
            'num_items',
            true
        );

        // disable paging
        $this->dataGrid->setPaging(false);

        // add attributes, so the inline editing has all the needed data
        $this->dataGrid->setColumnAttributes('title', ['data-id' => '{id:[id]}']);

        // check if this action is allowed
        if (BackendAuthentication::isAllowedAction('EditCategory')) {
            // set column URLs
            $this->dataGrid->setColumnURL(
                'title',
                BackendModel::createUrlForAction('EditCategory') . '&amp;id=[id]'
            );

            // add column
            $this->dataGrid->addColumn(
                'edit',
                null,
                BackendLanguage::lbl('Edit'),
                BackendModel::createUrlForAction('EditCategory') . '&amp;id=[id]',
                BackendLanguage::lbl('Edit')
            );
        }
    }

    protected function parse(): void
    {
        parent::parse();

        $this->template->assign('dataGrid', $this->dataGrid->getContent());
    }

    /**
     * Convert the count in a human readable one.
     *
     * @param int $count The count.
     * @param string $link The link for the count.
     *
     * @return string
     */
    public static function setClickableCount(int $count, string $link): string
    {
        if ($count > 1) {
            return '<a href="' . $link . '">' . $count . ' ' . BackendLanguage::getLabel('Articles') . '</a>';
        }

        if ($count === 1) {
            return '<a href="' . $link . '">' . $count . ' ' . BackendLanguage::getLabel('Article') . '</a>';
        }

        return '';
    }
}
