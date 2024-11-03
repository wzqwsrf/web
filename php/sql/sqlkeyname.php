<?php
require_once('sqltable.php');

class KeyNameSql extends TableSql
{
	var $strKeyName;
	var $iLen;
	var $bUnicode;
	
    public function __construct($strTableName, $strKeyName = 'parameter', $iLen = 128, $bUnicode = false)
    {
        $this->strKeyName = $strKeyName;
        $this->iLen = $iLen;
        $this->bUnicode = $bUnicode;
        parent::__construct($strTableName);
    }

    function InsertKey($strKey)
    {
    	if (strlen($strKey) > $this->iLen)	$strKey = substr($strKey, 0, $this->iLen);
    	
		if ($this->GetRecord($strKey) == false)
		{
			return $this->InsertArray(array($this->strKeyName => $strKey));
   		}
   		return false;
    }
    
    public function Create()
    {
    	$strCharSet = $this->bUnicode ? 'utf8 COLLATE utf8_unicode_ci' : 'latin1 COLLATE latin1_general_ci';
    	$str = ' `'.$this->strKeyName.'` VARCHAR( '.strval($this->iLen).' ) CHARACTER SET '.$strCharSet.' NOT NULL ,'
         	  . ' UNIQUE ( `'.$this->strKeyName.'` )';
        return $this->CreateIdTable($str);
    }
    
    function GetKey($strId)
    {
   		if ($record = $this->GetRecordById($strId))
   		{
   			return $record[$this->strKeyName];
   		}
   		return false;
    }

    public function GetRecord($strKey)
    {
    	return $this->GetSingleData(_SqlBuildWhere($this->strKeyName, $strKey));
    }

    public function GetAll($iStart = 0, $iNum = 0)
    {
   		return $this->GetData(false, '`'.$this->strKeyName.'` ASC', _SqlBuildLimit($iStart, $iNum));
    }
}

?>
