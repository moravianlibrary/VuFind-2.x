<?php
/**
 * Aleph Id Resolver
 *
 * PHP version 5
 *
 * Copyright (C) UB/FU Berlin
 *
 * last update: 7.11.2007
 * tested with X-Server Aleph 22
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Vaclav Rosecky <xrosecky@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace VuFind\ILS\Driver\Aleph;

/**
 * SolrIdResolver
 *
 */
class SolrIdResolver implements IdResolver
{

    protected $solrQueryField = 'barcodes';

    protected $itemIdentifier = 'barcode';

    protected $stripPrefix = true;

    /**
     * Search service (used for lookups by barcode number)
     *
     * @var \VuFindSearch\Service
     */
    protected $searchService = null;

    public function __construct(\VuFindSearch\Service $searchService, $config)
    {
        $this->searchService = $searchService;
        if (isset($config['IdResolver']['solrQueryField'])) {
            $this->solrQueryField = $config['IdResolver']['solrQueryField'];
        }
        if (isset($config['IdResolver']['itemIdentifier'])) {
            $this->itemIdentifier = $config['IdResolver']['itemIdentifier'];
        }
        if (isset($config['IdResolver']['stripPrefix'])) {
            $this->stripPrefix = $config['IdResolver']['stripPrefix'];
        }
    }

    public function resolveIds(&$recordsToResolve)
    {
        $idsToResolve = [];
        foreach ($recordsToResolve as $record) {
            $identifier = $record[$this->itemIdentifier];
            if (isset($identifier) && !empty($identifier)) {
                $idsToResolve[] = $record[$this->itemIdentifier];
            }
        }
        $resolved = $this->convertToIDUsingSolr($idsToResolve);
        foreach ($recordsToResolve as &$record) {
            if (isset($record[$this->itemIdentifier])) {
                $id = $record[$this->itemIdentifier];
                if (isset($resolved[$id])) {
                    $record['id'] = $resolved[$id];
                }
            }
        }
    }

    protected function convertToIDUsingSolr(&$ids)
    {
        if (empty($ids)) {
            return [];
        }
        $results = array();
        foreach ($ids as $id) {
            $query = new \VuFindSearch\Query\Query($this->solrQueryField. ':' . $id);
            if (isset($this->prefix)) {
                $idPrefixQuery = new \VuFindSearch\Query\Query('id:' . $this->prefix . '.*');
                $query = new \VuFindSearch\Query\QueryGroup('AND', [$idPrefixQuery, $query]);
            }
            $params = new \VuFindSearch\ParamBag();
            $doc = $this->searchService->getIds('Solr', $query, 0, sizeof($ids), $params)->first();
            if ($doc != null) {
                $results[$id] = $this->getId($doc);
            }
        }
        return $results;
    }

    protected function getId($record) {
        $id = $record->getUniqueID();
        if ($this->stripPrefix && strpos($id, '.') !== false) {
            $id = substr($id, strpos($id, '.') + 1);
        }
        return $id;
    }

}