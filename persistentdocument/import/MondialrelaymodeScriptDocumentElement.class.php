<?php
/**
 * mondialrelay_MondialrelaymodeScriptDocumentElement
 * @package modules.mondialrelay.persistentdocument.import
 */
class mondialrelay_MondialrelaymodeScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return mondialrelay_persistentdocument_mondialrelaymode
     */
    protected function initPersistentDocument()
    {
    	return mondialrelay_MondialrelaymodeService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_mondialrelay/mondialrelaymode');
	}
}