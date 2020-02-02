<?php

namespace MamounAlsmaiel\FluxKesearchIndexer;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class AdditionalContentFields {

    
    public function modifyPageContentFields(&$fields, $pageIndexer)
    {
        // Add the field "pi_flexform" from the tt_content table, which is normally not indexed, to the list of fields.
        $fields .= ",pi_flexform";
    }

    public function modifyContentFromContentElement(string &$bodytext, array $ttContentRow, $pageIndexer)
    {
        if(is_null($ttContentRow['pi_flexform'])){
            return;
        }
        
        // get indexable fields from typoscript
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\Extbase\\Object\\ObjectManager');
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $extbaseFrameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $config = $extbaseFrameworkConfiguration['plugin.']['tx_flux_kesearch_indexer.']['config.'];
        $indexableFields =  array();
        foreach ($config['elements.'] as $key => $value) {
            $type = str_replace('.' , '' , $key);
            if($type !== $ttContentRow['CType'] ){
                continue;
            }
            foreach ($value as $key => $value) {
                if($key == 'fields'){
                    $indexableFields = explode(',' , $value);
                }
            }
        
        }
       
        // Add the content of the field "pi_flexform" to $bodytext, which is, what will be saved to the index.
        $flexform    = '';
        $flexformService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\FlexFormService');
        $flexArr = $flexformService->convertFlexFormContentToArray($ttContentRow['pi_flexform']);
        
        $iterator  = new RecursiveArrayIterator($flexArr);
        $recursive = new RecursiveIteratorIterator(
            $iterator,
            RecursiveIteratorIterator::SELF_FIRST
        );

        //Indexing fields designated as indexable fields in Typoscript
        //Indexing all fields if  indexable fields didn't set in Typoscript 
        foreach ($recursive as $key => $value) {
            if(is_array($value)){continue;};
            if(empty($indexableFields) ){
                $flexform .= "\n" . $value;
            }else{
                if(in_array($key, $indexableFields)) {
                    $flexform .= "\n" . $value;
                }
            }
        }
        $bodytext .= "\n" . strip_tags($flexform) . "\n" ;
    }
}