<?php
/**
 * Aleph ILS driver
 *
 * PHP version 5
 *
 * Copyright (C) UB/FU Berlin
 *
 * last update: 7.11.2007
 * tested with X-Server Aleph 18.1.
 *
 * TODO: login, course information, getNewItems, duedate in holdings,
 * https connection to x-server, ...
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
 * @author   Christoph Krempe <vufind-tech@lists.sourceforge.net>
 * @author   Alan Rykhus <vufind-tech@lists.sourceforge.net>
 * @author   Jason L. Cooper <vufind-tech@lists.sourceforge.net>
 * @author   Kun Lin <vufind-tech@lists.sourceforge.net>
 * @author   Vaclav Rosecky <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace MZKCatalog\ILS\Driver;
use VuFind\Exception\ILS as ILSException;
use Zend\Log\LoggerInterface;
use VuFindHttp\HttpServiceInterface;
use DateTime;
use VuFind\Exception\Date as DateException;
use VuFind\ILS\Driver\Aleph as AlephBase;

/**
 * Aleph ILS driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Christoph Krempe <vufind-tech@lists.sourceforge.net>
 * @author   Alan Rykhus <vufind-tech@lists.sourceforge.net>
 * @author   Jason L. Cooper <vufind-tech@lists.sourceforge.net>
 * @author   Kun Lin <vufind-tech@lists.sourceforge.net>
 * @author   Vaclav Rosecky <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
class Aleph extends AlephBase
{
    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $user    The patron array from patronLogin
     * @param bool  $history Include history of transactions (true) or just get
     * current ones (false).
     *
     * @throws \VuFind\Exception\Date
     * @throws ILSException
     * @return array        Array of the patron's transactions on success.
     */
    public function getMyTransactions($user, $history=false)
    {
        $userId = $user['id'];
        $transList = array();
        $params = array("view" => "full");
        if ($history) {
            $params["type"] = "history";
        }
        $xml = $this->doRestDLFRequest(
            array('patron', $userId, 'circulationActions', 'loans'), $params
        );
        foreach ($xml->xpath('//loan') as $item) {
            $z36 = $item->z36;
            $z13 = $item->z13;
            $z30 = $item->z30;
            $group = $item->xpath('@href');
            $group = substr(strrchr($group[0], "/"), 1);
            //$renew = $item->xpath('@renew');
            //$docno = (string) $z36->{'z36-doc-number'};
            //$itemseq = (string) $z36->{'z36-item-sequence'};
            //$seq = (string) $z36->{'z36-sequence'};
            $location = (string) $z36->{'z36_pickup_location'};
            $reqnum = (string) $z36->{'z36-doc-number'}
                . (string) $z36->{'z36-item-sequence'}
                . (string) $z36->{'z36-sequence'};
            $due = $returned = null;
            if ($history) {
                $due = $item->z36h->{'z36h-due-date'};
                $returned = $item->z36h->{'z36h-returned-date'};
                //$historyId = $item->z36h->{'z36h-doc-number'};
                //if (!empty($historyId)) {
                //	$historyId = "MZK01-" . $historyId;
                //}
            } else {
                $due = (string) $z36->{'z36-due-date'};
            }
            //$loaned = (string) $z36->{'z36-loan-date'};
            $title = (string) $z13->{'z13-title'};
            $author = (string) $z13->{'z13-author'};
            $isbn = (string) $z13->{'z13-isbn-issn'};
            $barcode = (string) $z30->{'z30-barcode'};
            $transList[] = array(
                //'type' => $type,
            	'id' => ($history)?"":$this->barcodeToID($barcode),
                'item_id' => $group,
                'location' => $location,
                'title' => $title,
                'author' => $author,
                'isbn' => array($isbn),
                'reqnum' => $reqnum,
                'barcode' => $barcode,
                'duedate' => $this->parseDate($due),
                'returned' => $this->parseDate($returned),
                //'holddate' => $holddate,
                //'delete' => $delete,
                'renewable' => true,
                //'create' => $this->parseDate($create)
            );
        }
        return $transList;
    }
}
