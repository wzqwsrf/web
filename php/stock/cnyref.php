<?php

class CnyReference extends MysqlReference
{
    public function LoadData()
    {
    	$strSymbol = $this->GetSymbol();
    	
    	$this->strSqlId = SqlGetStockId($strSymbol);
       	$this->LoadSqlNavData();
   		$this->strTime = '09:15:00';
        $this->strFileName = DebugGetChinaMoneyFile();
        $this->strExternalLink = GetReferenceRateForexLink($strSymbol);
    }
    
	public function GetClose($strDate)
	{
		if ($strDate == $this->GetDate())	return $this->GetPrice();
		return SqlGetNavByDate($this->strSqlId, $strDate);
	}
}

class HkdUsdReference
{
	var $uscny_ref;
	var $hkcny_ref;
    
    public function __construct()
    {
   		$this->uscny_ref = new CnyReference('USCNY');
   		$this->hkcny_ref = new CnyReference('HKCNY');
    }
    
	public function GetVal($strDate = false)
	{
		return $this->hkcny_ref->GetVal($strDate) / $this->uscny_ref->GetVal($strDate); 
	}
    
	public function GetClose($strDate)
	{
		return $this->hkcny_ref->GetClose($strDate) / $this->uscny_ref->GetClose($strDate); 
	}
}

?>
