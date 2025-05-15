<?php
require_once('_fundgroup.php');

function _tradingUserDefined($strVal = false)
{
	global $acct;
    
	$fund = $acct->GetRef();
	$est_ref = $fund->GetEstRef();

    if ($strVal)
    {
    	if ($strVal == '0')	return '';
    	else
    	{
    		$strEst = $fund->GetEstValue($strVal);
    		return $est_ref->GetPriceDisplay($strEst);
    	}
    }
    
   	return GetTableColumnStock($est_ref).GetTableColumnPrice();
}

function _convertCallback($fRatio, $fFactor)
{
	global $acct;
   	$calibration_sql = GetCalibrationSql();
    
	$ref = $acct->GetRef();
   	$fPosition = RefGetPosition($ref);
   	$fCalibration = floatval($calibration_sql->GetCloseNow($ref->GetStockId()));
   	return strval(round(($fCalibration / $fPosition) * ($fRatio / $fFactor)));
}

class QdiiGroupAccount extends FundGroupAccount 
{
//    var $arLeverage = array();
    var $arLeverage;
    var $ar_leverage_ref = array();
    
    function QdiiCreateGroup()
    {
    	$ref = $this->GetRef();
    	$stock_ref = $ref->GetStockRef();
       	$est_ref = $ref->GetEstRef();
       	$arRef = array($stock_ref, $est_ref);
		if ($realtime_ref = $ref->GetRealtimeRef())		$arRef[] = $realtime_ref;
    	
        if ($ar = YahooUpdateNetValue($est_ref))
        {
//        	list($strNav, $strDate) = $ar;
        	if ($est_ref->GetSymbol() == 'INDA')
        	{
        		$est_ref->DailyCalibration();
/*        		if ($realtime_ref->GetDate() == $strDate)
        		{
        			$calibration_sql = GetCalibrationSql();
        			$calibration_sql->WriteDaily($est_ref->GetStockId(), $strDate, strval(EtfGetCalibration($realtime_ref->GetPrice(), $strNav)));
        		}*/
        	}
        }
        
        GetChinaMoney($stock_ref);
        SzseGetLofShares($stock_ref);
        
    	foreach ($this->arLeverage as $strSymbol)
    	{
    		$leverage_ref = new FundPairReference($strSymbol);
    		$this->ar_leverage_ref[] = $leverage_ref;
    		YahooUpdateNetValue($leverage_ref);
    		$leverage_ref->DailyCalibration();
    	}
        $this->CreateGroup(array_merge($arRef, $this->ar_leverage_ref));
    }
    
    function GetLeverage()
    {
        return $this->arLeverage;
    }

    function GetLeverageRef()
    {
    	return $this->ar_leverage_ref;
    }
    
    function EchoCommonParagraphs()
    {
    	$ref = $this->GetRef();
    	
    	EchoFundTradingParagraph($ref, '_tradingUserDefined');    
    	EchoQdiiSmaParagraph($ref);
    	if (count($this->ar_leverage_ref) > 0)	
    	{
    		EchoFundListParagraph($this->ar_leverage_ref, '_convertCallback');
    		EchoFundPairSmaParagraphs($ref->GetEstRef(), $this->ar_leverage_ref);
    	}
    	EchoFutureSmaParagraph($ref);
    	EchoFundHistoryParagraph($ref);
    	EchoFundShareParagraph($ref);
    	EchoNvCloseHistoryParagraph($ref->GetEstRef());
    }

    function GetLeverageSymbols($strEstSymbol)
    {
   		$pair_sql = new FundPairSql();
        $this->arLeverage = $pair_sql->GetSymbolArray($strEstSymbol);
    }
    
    function EchoDebugParagraph()
    {
    	if ($this->IsAdmin())
    	{
    		$ref = $this->GetRef();
    		$strDebug = $ref->DebugLink();
   			EchoHtmlElement($strDebug);
    	}
    }
} 

function GetMetaDescription()
{
    global $acct;
    
    $fund = $acct->GetRef();
    $cny_ref = $fund->GetCnyRef();
	$strBase = SqlGetStockName($cny_ref->GetSymbol());
    if ($est_ref = $fund->GetEstRef())     $strBase .= '、'.SqlGetStockName($est_ref->GetSymbol());
    
    $str = '根据'.$strBase.'等其它网站的数据来源估算'.$acct->GetStockDisplay().'净值的网页工具。';
    return CheckMetaDescription($str);
}

?>
