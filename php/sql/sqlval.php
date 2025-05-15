<?php
require_once('sqltable.php');

class ValSql extends TableSql
{
	var $strValName;
	
    public function __construct($strTableName, $strValName = 'close')
    {
        $this->strValName = $strValName;
        parent::__construct($strTableName);
    }

    function GetValName()
    {
		return $this->strValName;
    }
    
    public function Create()
    {
    	$str = $this->ComposePrimaryIdStr().','
         	  . $this->ComposeCloseStr();
    	return $this->CreateTable($str);
    }
    
    function Insert($strId, $strVal)
    {
    	return $this->InsertArray(array('id' => $strId, $this->strValName => $strVal));
    }

    function Update($strId, $strVal)
    {
		return $this->UpdateById(array($this->strValName => $strVal), $strId);
    }
    
    function WriteVal($strId, $strVal, $bString = false)
    {
   		if ($record = $this->GetRecordById($strId))
   		{
   			if ($bString)
   			{
   				if ($strVal == $record[$this->strValName])													return false;
   			}
   			else
   			{
   				if (abs(floatval($record[$this->strValName]) - floatval($strVal)) < MIN_FLOAT_VAL)		return false;
   			}
			return $this->Update($strId, $strVal);
   		}
    	return $this->Insert($strId, $strVal);
    }
    
    function ReadVal($strId, $bString = false)
    {
   		if ($record = $this->GetRecordById($strId))
   		{
			$strVal = $record[$this->strValName];
			return $bString ? $strVal : floatval($strVal); 
   		}
    	return false;
    }
    
    function GetAllIdVal()
    {
    	$ar = array();
    
    	if ($result = $this->GetData()) 
    	{
    		while ($record = mysqli_fetch_assoc($result)) 
    		{
    			$ar[$record['id']] = $record[$this->strValName];
    		}
    		mysqli_free_result($result);
    	}
    	return $ar;
    }
}

class FundPositionSql extends ValSql
{
    public function __construct()
    {
        parent::__construct('fundposition');
    }
}

class LastCalibrationSql extends ValSql
{
    public function __construct()
    {
        parent::__construct('lastcalibration');
    }
}

?>
