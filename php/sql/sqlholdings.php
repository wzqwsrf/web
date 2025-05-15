<?php
require_once('sqlkey.php');

class HoldingsSql extends KeySql
{
    public function __construct() 
    {
        parent::__construct('holdings', 'stock');
    }

    public function Create()
    {
    	$str = $this->ComposeKeyStr().','
    		  . $this->ComposeIntStr('holding_id').','
         	  . ' `ratio` DOUBLE(13,6) NOT NULL ,'
         	  . $this->ComposeForeignKeyStr();
    	return $this->CreateIdTable($str);
    }

    public function BuildOrderBy()
    {
    	return '`ratio` DESC';
    }
    
    function InsertHolding($strStockId, $strHoldingId, $strRatio)
    {
    	return $this->InsertArrays($this->MakeFieldKeyId($strStockId), array('holding_id' => $strHoldingId, 'ratio' => $strRatio));
    }
    
    function InsertHoldingsArray($strStockId, $ar)
    {
    	foreach ($ar as $strHoldingId => $strRatio)
    	{
    		$this->InsertHolding($strStockId, $strHoldingId, $strRatio);
    	}
    }
    
    function GetHoldingsArray($strStockId)
    {
    	$ar = array();
    	if ($result = $this->GetAll($strStockId)) 
    	{
    		while ($record = mysqli_fetch_assoc($result)) 
    		{
    			$strHoldingId = $record['holding_id']; 
    			$ar[$strHoldingId] = $record['ratio'];
    		}
    		mysqli_free_result($result);
    	}
    	return $ar;
    }
}

?>
