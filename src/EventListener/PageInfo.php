<?php

/**
 * This file is part of e-spin/page-info-bundle.
 *
 * Copyright (c) 2020 e-spin
 *
 * @package   e-spin/page-info-bundle
 * @author    Ingolf Steinhardt <info@e-spin.de>
 * @author    Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @author    Daniel Jahnsmüller <https://tastaturberuf.de>
 * @copyright 2020 e-spin
 * @license   LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Espin\PageInfoBundle\EventListener;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;


class PageInfo
{

    /**
     * @var Connection
     */
    private $connection;


    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * Generate the panel and return it as HTML string
     */
    public function generatePanel(): string
    {
        if (\Input::post('FORM_SUBMIT') == 'tl_filters') {
            $varValue = \Input::post('tl_page_info') ?: null;

            \Session::getInstance()->set('page_info', $varValue);
        }

        $blnActive = false;
        $strCurrent = $this->getCurrent();
        $arrOptions = array('<option value=""' . (($strCurrent == '') ? ' selected' : '') . '>---</option>');

        // Generate options
        foreach ($this->getFields() as $field) {
            $arrOptions[] = sprintf('<option value="%s"%s>%s</option>',
                $field,
                ($strCurrent == $field) ? ' selected' : '',
                $GLOBALS['TL_LANG']['tl_page'][$field][0] ?: $GLOBALS['TL_LANG']['MSC'][$field][0] ?: $field
            );

            // The field is active
            if (!$blnActive && ($strCurrent == $field)) {
                $blnActive = true;
            }
        }

        return '<div class="tl_page_info tl_subpanel" style="float:left; margin-left: 15px; text-align: left;">
<strong>' . $GLOBALS['TL_LANG']['tl_page']['page_info_filter'] . '</strong>
<select name="tl_page_info" class="tl_select tl_chosen' . ($blnActive ? ' active' : '') . '" onchange="this.form.submit()" style="width: 300px; margin-left: 3px;">
' . implode("\n", $arrOptions) . '
</select>
</div>';
    }


    private function getFields(): array
    {
        $schema = $this->connection->getSchemaManager();

        return array_keys($schema->listTableColumns('tl_page'));
    }



    /**
     * Add hint to each record
     */
    public function addHint(
        array $row,
        string $label,
        DataContainer $dc=null,
        string $imageAttribute='',
        bool $blnReturnImage=false,
        bool $blnProtected=false
    ): string
    {
        $objDefault = new \tl_page();
        $strReturn = $objDefault->addIcon($row, $label, $dc, $imageAttribute, $blnReturnImage, $blnProtected);
        $strCurrent = $this->getCurrent();

        // Add a hint
        $callback = $GLOBALS['PAGE_INFO'][$strCurrent];
        if ( is_callable($callback) )
        {
            return $strReturn.' <span style="padding-left:3px;color:#8A8A8A;">[' . $callback($row) . ']</span>';
        }
        // return if the row has a string value
        elseif ( is_string($row[$strCurrent]) && strlen($row[$strCurrent]) )
        {
            return $strReturn.' <span style="padding-left:3px;color:#8A8A8A;">[' . $row[$strCurrent] . ']</span>';
        }

        return $strReturn;
    }

    /**
     * Get the current hint
     */
    public function getCurrent(): ?string
    {
        return \Session::getInstance()->get('page_info');
    }
}
