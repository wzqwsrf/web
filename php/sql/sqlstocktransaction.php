<?php
require_once('sqltable.php');

// ****************************** StockTransactionSql class *******************************************************
class StockTransactionSql extends TableSql
{
    function StockTransactionSql()
    {
        parent::TableSql('stocktransaction');
    }
    
    function _buildWhereOr_groupitem($arGroupItemId)
    {
    	return _SqlBuildWhereOrArray('groupitem_id', $arGroupItemId);
    }
    
    function _buildWhere_groupitem($strGroupItemId)
    {
    	return _SqlBuildWhere('groupitem_id', $strGroupItemId);
    }
    
    function GetAll($arGroupItemId, $iStart = 0, $iNum = 0)
    {
    	if ($strWhere = $this->_buildWhereOr_groupitem($arGroupItemId))
    	{
    		return $this->GetData($strWhere, '`filled` DESC', _SqlBuildLimit($iStart, $iNum));
    	}
    	return false;
    }

    function GetRecord($strGroupItemId, $iStart = 0, $iNum = 0)
    {
    	return $this->GetAll(array($strGroupItemId), $iStart, $iNum);
    }
    
    function CountAll($arGroupItemId)
    {
    	if ($strWhere = $this->_buildWhereOr_groupitem($arGroupItemId))
    	{
    		return $this->CountData($strWhere);
    	}
    	return 0;
    }

    function Count($strGroupItemId)
    {
    	if ($strWhere = $this->_buildWhere_groupitem($strGroupItemId))
    	{
    		return $this->CountData($strWhere);
    	}
    }
    
    function Delete($strGroupItemId)
    {
    	if ($strWhere = $this->_buildWhere_groupitem($strGroupItemId))
    	{
    		$this->DeleteRecord($strWhere);
    	}
    }

    function _makePrivateFieldArray($strGroupItemId, $strQuantity, $strPrice, $strFees, $strRemark)
    {
    	$strDateTime = DebugGetDateTime();
    	return array('groupitem_id' => $strGroupItemId, 'quantity' => $strQuantity, 'price' => $strPrice, 'fees' => $strFees, 'filled' => $strDateTime, 'remark' => $strRemark);
    }

    function Insert($strGroupItemId, $strQuantity, $strPrice, $strFees = '', $strRemark = '')
    {
    	return $this->InsertArray($this->_makePrivateFieldArray($strGroupItemId, $strQuantity, $strPrice, $strFees, $strRemark));
    }

    function Update($strId, $strGroupItemId, $strQuantity, $strPrice, $strFees, $strRemark)
    {
    	$ar = $this->_makePrivateFieldArray($strGroupItemId, $strQuantity, $strPrice, $strFees, $strRemark);
    	unset($ar['filled']);
		return $this->UpdateById($ar, $strId);
	}
	
    function Merge($strSrcGroupItemId, $strDstGroupItemId)
    {
    	if ($strWhere = $this->_buildWhere_groupitem($strSrcGroupItemId))
    	{
    		return $this->UpdateArray(array('groupitem_id' => $strDstGroupItemId), $strWhere);
    	}
    	return false;
    }
}

// ****************************** Stock Transaction table *******************************************************
/*
function SqlCreateStockTransactionTable()
{
    $str = 'CREATE TABLE IF NOT EXISTS `camman`.`stocktransaction` ('
        . '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
        . '`groupitem_id` INT UNSIGNED NOT NULL ,'
        . '`quantity` INT NOT NULL ,'
        . '`price` DOUBLE(10,3) NOT NULL ,'
        . '`fees` DOUBLE(8,3) NOT NULL ,'
        . '`filled` DATETIME NOT NULL ,'
        . '`remark` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,'
        . ' FOREIGN KEY (`groupitem_id`) REFERENCES `stockgroupitem`(`id`) ON DELETE CASCADE'
        . ') ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci'; 
}
*/

?>
