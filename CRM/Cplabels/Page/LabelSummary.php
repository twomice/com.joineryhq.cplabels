<?php
use CRM_Cplabels_ExtensionUtil as E;

class CRM_Cplabels_Page_LabelSummary extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Label Summary'));

    $labelFormHash = CRM_Cplabels_Utils::constructLabelFormHash($_GET);
    $labelSummaryData = CRM_Cplabels_Utils::getSessionVar("labelSummaryData_{$labelFormHash}", array());

    $this->assign('duplicates', $labelSummaryData['duplicates']);
    $this->assign('total', $labelSummaryData['total']);
    $this->assign('zipcounts', $labelSummaryData['zipcounts']);

    parent::run();
  }

}
