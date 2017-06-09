<?php

namespace Backend\Modules\Search\Installer;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Common\ModuleExtraType;
use Backend\Core\Installer\ModuleInstaller;

/**
 * Installer for the search module
 */
class Installer extends ModuleInstaller
{
    /** @var int */
    private $searchExtraId;

    public function install(): void
    {
        $this->addModule('Search');
        $this->importSQL(__DIR__ . '/Data/install.sql');
        $this->importLocale(__DIR__ . '/Data/locale.xml');
        $this->configureSettings();
        $this->configureBackendNavigation();
        $this->configureBackendRights();
        $this->configureFrontendExtras();
        $this->configureFrontendPages();
        $this->configureFrontendSearchIndexes();
    }

    private function configureBackendNavigation(): void
    {
        // Set navigation for "modules"
        $navigationModulesId = $this->setNavigation(null, 'Modules');
        $navigationSearchId = $this->setNavigation($navigationModulesId, 'Search');
        $this->setNavigation($navigationSearchId, 'Statistics', 'search/statistics');
        $this->setNavigation(
            $navigationSearchId,
            'Synonyms',
            'search/synonyms',
            ['search/add_synonym', 'search/edit_synonym']
        );

        // Set navigation for "settings"
        $navigationSettingsId = $this->setNavigation(null, 'Settings');
        $navigationModulesId = $this->setNavigation($navigationSettingsId, 'Modules');
        $this->setNavigation($navigationModulesId, 'Search', 'search/settings');
    }

    private function configureBackendRights(): void
    {
        $this->setModuleRights(1, $this->getModule());
        $this->setActionRights(1, $this->getModule(), 'AddSynonym');
        $this->setActionRights(1, $this->getModule(), 'EditSynonym');
        $this->setActionRights(1, $this->getModule(), 'DeleteSynonym');
        $this->setActionRights(1, $this->getModule(), 'Settings');
        $this->setActionRights(1, $this->getModule(), 'Statistics');
        $this->setActionRights(1, $this->getModule(), 'Synonyms');
    }

    private function configureFrontendExtras(): void
    {
        $this->insertExtra($this->getModule(), ModuleExtraType::widget(), 'SearchForm', 'Form', null, false, 2001);
        $this->searchExtraId = $this->insertExtra($this->getModule(), ModuleExtraType::block(), 'Search', null, null, false, 2000);
    }

    private function configureFrontendPages(): void
    {
        foreach ($this->getLanguages() as $language) {
            if ($this->hasExistingSearchIndex($language)) {
                continue;
            }

            $searchIndexPageTitle = $this->getLocale('Search', 'Core', $language, 'lbl', 'Frontend');
            $this->insertPage(
                [
                    'title' => \SpoonFilter::ucfirst($searchIndexPageTitle),
                    'type' => 'root',
                    'language' => $language,
                ],
                null,
                ['extra_id' => $this->searchExtraId, 'position' => 'main']
            );
        }
    }

    private function configureFrontendSearchIndexes(): void
    {
        $this->makeSearchable('Pages');

        foreach ($this->getActivePages() as $page) {
            $this->insertSearchIndexForPage($page['id'], $page['language'], $page['title']);
            $this->insertSearchIndexForPage(
                $page['id'],
                $page['language'],
                $this->getContentFromBlocksForPageRevision($page['revision_id'])
            );
        }
    }

    private function configureSettings(): void
    {
        $this->setSetting($this->getModule(), 'overview_num_items', 10);
        $this->setSetting($this->getModule(), 'validate_search', true);
    }

    private function getActivePages(): array
    {
        return (array) $this->getDB()->getRecords(
            'SELECT id, revision_id, language, title
             FROM pages
             WHERE status = ?',
            ['active']
        );
    }

    private function getContentFromBlocksForPageRevision(int $pageRevisionId): string
    {
        $blocks = (array) $this->getDB()->getColumn(
            'SELECT html FROM pages_blocks WHERE revision_id = ?',
            [$pageRevisionId]
        );

        return empty($blocks) ? '' : strip_tags(implode(' ', $blocks));
    }

    private function hasExistingSearchIndex(string $language): bool
    {
        return (bool) $this->getDB()->getVar(
            'SELECT 1
             FROM pages AS p
             INNER JOIN pages_blocks AS b ON b.revision_id = p.revision_id
             WHERE b.extra_id = ? AND p.language = ?
             LIMIT 1',
            [$this->searchExtraId, $language]
        );
    }

    private function insertSearchIndexForPage(int $id, string $language, string $term): void
    {
        $this->getDB()->execute(
            'INSERT INTO search_index (module, other_id, language, field, value, active)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE value = ?, active = ?',
            ['Pages', $id, $language, 'title', $term, 'Y', $term, 'Y']
        );
    }
}
