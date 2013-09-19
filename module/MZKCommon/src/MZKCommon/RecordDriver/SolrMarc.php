<?php
namespace MZKCommon\RecordDriver;
use VuFind\RecordDriver\SolrMarc as ParentSolrMarc;

class SolrMarc extends ParentSolrMarc
{
    
    public function getBibinfoForObalkyKnih() {
        $bibinfo = array(
            "authors" => array($this->getPrimaryAuthor()),
            "title" => $this->getTitle(),
        );
        $isbn = $this->getCleanISBN();
        if (!empty($isbn)) {
            $bibinfo['isbn'] = $isbn;
        }
        $year = $this->getPublicationDates();
        if (!empty($year)) {
            $bibinfo['year'] = $year[0];
        }
        return $bibinfo;
    }

    /**
     * gets CNB of current record (field 015)
     * @return string CNB if exists, empty string otherwise 
     */
    protected function getCNB()
    {
        $cnb = parent::getFieldArray('015');
        return is_array($cnb) && !empty($cnb[0]) ? $cnb[0] : '';
    }

}
