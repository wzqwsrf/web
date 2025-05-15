<?php
require_once('sqlkey.php');

class DailyCloseSql extends KeySql
{
    public function __construct($strTableName, $strKeyPrefix = 'stock') 
    {
        parent::__construct($strTableName, $strKeyPrefix);
    }

    function ComposeUniqueDateStr()
    {
		return ' UNIQUE ( `date`, `'.$this->GetKeyIndex().'` ) ';
    }
    
    function CreateDailyCloseTable($strMid)
    {
    	$str = $this->ComposeKeyStr().','
    		  . $this->ComposeDateStr().','
         	  . $strMid.','
         	  . $this->ComposeForeignKeyStr().','
         	  . $this->ComposeUniqueDateStr();
    	return $this->CreateIdTable($str);
    }
    
    public function Create()
    {
        return $this->CreateDailyCloseTable($this->ComposeCloseStr());
    }

    public function BuildOrderBy()
    {
    	return _SqlOrderByDate();
    }
    
    function GetFromDate($strKeyId, $strDate, $iNum = 0)
    {
    	return $this->GetData($this->BuildWhere_key($strKeyId)." AND date <= '$strDate'", $this->BuildOrderBy(), _SqlBuildLimit(0, $iNum));
    }
    
    function GetToDate($strKeyId, $strDate, $iNum = 0)
    {
    	return $this->GetData($this->BuildWhere_key($strKeyId)." AND date > '$strDate'", $this->BuildOrderBy(), _SqlBuildLimit(0, $iNum));
    }
    
    function GetRecordPrev($strKeyId, $strDate)
    {
    	return $this->GetSingleData($this->BuildWhere_key($strKeyId)." AND date < '$strDate'", $this->BuildOrderBy());
    }
    
    function BuildWhere_key_date($strKeyId, $strDate)
    {
/*    	$ar = $this->MakeFieldKeyId($strKeyId);
    	$ar['date'] = $strDate;
		return _SqlBuildWhereAndArray($ar);*/
		return $this->BuildWhere_key_ex($strKeyId, 'date', $strDate);
    }
    
    function GetRecord($strKeyId, $strDate)
    {
    	return $this->GetSingleData($this->BuildWhere_key_date($strKeyId, $strDate));
    }

    function _getCloseString($callback, $strKeyId, $strDate = false)
    {
    	if ($record = $this->$callback($strKeyId, $strDate))
    	{
    		return rtrim0($record['close']);
    	}
    	return false;
    }
    
    function GetClose($strKeyId, $strDate)
    {
    	return $this->_getCloseString('GetRecord', $strKeyId, $strDate);
    }

    function GetClosePrev($strKeyId, $strDate)
    {
    	return $this->_getCloseString('GetRecordPrev', $strKeyId, $strDate);
    }

    function GetCloseNow($strKeyId = false)
    {
    	return $this->_getCloseString('GetRecordNow', $strKeyId);
    }

    function _getDateString($callback, $strKeyId, $strDate = false)
    {
    	if ($record = $this->$callback($strKeyId, $strDate))
    	{
    		return $record['date'];
    	}
    	return false;
    }
    
    function GetDatePrev($strKeyId, $strDate)
    {
    	return $this->_getDateString('GetRecordPrev', $strKeyId, $strDate);
    }

    function GetDateNow($strKeyId = false)
    {
    	return $this->_getDateString('GetRecordNow', $strKeyId);
    }

	function MakeFieldArray($strKeyId, $strDate, $strClose)
    {
    	return array_merge($this->MakeFieldKeyId($strKeyId), array('date' => $strDate, 'close' => $strClose));
    }
    
    public function InsertDaily($strKeyId, $strDate, $strClose)
    {
    	if ($strDate == '')									return false;
    	
        if ($this->GetRecord($strKeyId, $strDate))			return false;
    	return $this->InsertArray($this->MakeFieldArray($strKeyId, $strDate, $strClose));
    }

    public function UpdateDaily($strId, $strClose)
    {
		return $this->UpdateById(array('close' => $strClose), $strId);
    }

    public function WriteDaily($strKeyId, $strDate, $strClose)
    {
    	if ($record = $this->GetRecord($strKeyId, $strDate))
    	{
    		if (abs(floatval($record['close']) - floatval($strClose)) > MIN_FLOAT_VAL)
    		{
//    			DebugString($record['close'].' '.$strClose); 
    			return $this->UpdateDaily($record['id'], $strClose);
    		}
    	}
    	else
    	{
    		$ymd = new StringYMD($strDate);
    		if ($ymd->IsWeekend())     			return false;   // sina fund may provide false weekend data
    		
    		return $this->InsertDaily($strKeyId, $strDate, $strClose);
    	}
    	return false;
    }
    
    function WriteDailyArray($strKeyId, $ar)
    {
		foreach ($ar as $strDate => $strClose)
		{
			$this->WriteDaily($strKeyId, $strDate, $strClose);
		}
    }
    
    function IsInvalidDate($record)
    {
		$ymd = new OldestYMD();
		$strDate = $record['date'];
    	return $ymd->IsTooOld($strDate) || $ymd->IsInvalid($strDate);
    }
    
    function DeleteInvalidDate()
    {
    	return $this->DeleteInvalid('IsInvalidDate');
    }
    
    function DeleteByDate($strKeyId, $strDate)
    {
    	if ($strWhere = $this->BuildWhere_key_date($strKeyId, $strDate))
    	{
    		return $this->DeleteData($strWhere);
    	}
    	return false;
    }

    function DeleteClose($str = '0.000000')
    {
    	$this->DeleteData("close = '$str'");
    }

    function ModifyDaily($strKeyId, $strDate, $strClose)
    {
    	if (empty($strClose))
    	{
    		$this->DeleteByDate($strKeyId, $strDate);
    		return false;
    	}
		return $this->WriteDaily($strKeyId, $strDate, $strClose);
    }
}

class FuturePremiumSql extends DailyCloseSql
{
    public function __construct() 
    {
        parent::__construct('futurepremium');
    }
}

class StockEmaSql extends DailyCloseSql
{
    public function __construct($iDays) 
    {
        parent::__construct('stockema'.strval($iDays));
    }
}

class StockSplitSql extends DailyCloseSql
{
    public function __construct() 
    {
        parent::__construct(TABLE_STOCK_SPLIT);
    }
}

class StockDividendSql extends DailyCloseSql
{
    public function __construct() 
    {
        parent::__construct(TABLE_STOCK_DIVIDEND);
    }
}

class SharesHistorySql extends DailyCloseSql
{
    public function __construct() 
    {
        parent::__construct('shareshistory');
    }
}

class SharesDiffSql extends DailyCloseSql
{
    public function __construct() 
    {
        parent::__construct('sharesdiff');
    }
}

?>
