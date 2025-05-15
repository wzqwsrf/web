<?php
//require_once('stocktable.php');
require_once('calibrationhistoryparagraph.php');
require_once('netvaluehistoryparagraph.php');

// $ref from FundReference
function _echoFundEstTableItem($ref, $bFair, $bWide = false)
{
    if (RefHasData($ref) == false)      return;

    $ar = array($ref->GetStockLink());
    if ($bWide)
    {
    	$stock_ref = (method_exists($ref, 'GetStockRef')) ? $ref->GetStockRef() : $ref;
    	$ar = array_merge($ar, GetStockReferenceArray($stock_ref));
    }
    
    $strOfficialPrice = $ref->GetOfficialNav();
    $ar[] = $ref->GetPriceDisplay($strOfficialPrice);
    $ar[] = $ref->GetOfficialDate();
	$ar[] = $ref->GetPercentageDisplay($strOfficialPrice);
    
    if ($strFairPrice = $ref->GetFairNav())
    {
    	$ar[] = $ref->GetPriceDisplay($strFairPrice);
    	$ar[] = $ref->GetPercentageDisplay($strFairPrice);
    }
    else if ($bFair)
    {
    	$ar[] = '';
    	$ar[] = '';
    }
    
   	if ($strRealtimePrice = $ref->GetRealtimeNav())
   	{
   		$ar[] = $ref->GetPriceDisplay($strRealtimePrice);
   		$ar[] = $ref->GetPercentageDisplay($strRealtimePrice);
    }
    
	RefEchoTableColumn($ref, $ar, $bWide);
}

function _callbackSortFundEst($ref)
{
	$strNav = $ref->GetOfficialNav();
	if (method_exists($ref, 'GetStockRef'))
	{
    	$stock_ref = $ref->GetStockRef();
    	return $stock_ref->GetPercentage($strNav);
	}
	return $ref->GetPercentage($strNav);
}

function _getFundEstTableColumn($arRef, &$bFair, $bWide = false)
{
	$premium_col = new TableColumnPremium();
	$ar = array(new TableColumnSymbol());
	if ($bWide)	$ar = array_merge($ar, GetStockReferenceColumn());
	$ar[] = new TableColumnOfficalEst();
	$ar[] = new TableColumnDate();
	$ar[] = $premium_col;
	
	$bFair = false;
    foreach ($arRef as $ref)
    {
        if ($ref->GetFairNav())
        {
        	$bFair = true;
        	$ar[] = new TableColumnFairEst();
        	$ar[] = $premium_col;
        	break;
        }
    }
	
    foreach ($arRef as $ref)
    {
   		if ($ref->GetRealtimeNav())
   		{
   			$ar[] = new TableColumnRealtimeEst();
   			$ar[] = $premium_col;
   			break;
    	}
    }
    return $ar;
}

function _getOrderByDisplay($strOrder)
{
	return '按'.$strOrder.'排序';
}

function _echoFundEstParagraph($arColumn, $bFair, $arRef, $str, $bWide = false)
{
	if ($str === false)
	{
		$str = GetTableColumnEst().'网页链接'; 
		$iCount = count($arRef);
		if ($iCount > 2)
		{
			$str .= '共'.strval($iCount).'项';
			if ($strSort = UrlGetQueryValue('sort'))
			{
				if ($strSort == 'symbol')				
				{
					$arRef = RefSortBySymbol($arRef);
					$str .= _getOrderByDisplay(GetTableColumnSymbol());
				}
				else if ($strSort == 'premium')
				{
					$arRef = RefSortByNumeric($arRef, '_callbackSortFundEst');
					$str .= _getOrderByDisplay(STOCK_DISP_OFFICIAL.GetTableColumnPremium());
				}
			}
			else	$str .= ' '.CopyPhpLink(UrlAddQuery('sort=symbol'), _getOrderByDisplay(STOCK_DISP_SYMBOL)).' '.CopyPhpLink(UrlAddQuery('sort=premium'), _getOrderByDisplay(STOCK_DISP_OFFICIAL.STOCK_DISP_PREMIUM));
		}
	}
	
	EchoTableParagraphBegin($arColumn, 'estimation', $str);
    foreach ($arRef as $ref)		_echoFundEstTableItem($ref, $bFair, $bWide);
    EchoTableParagraphEnd();
}

function EchoFundArrayEstParagraph($arRef, $str = false, $bWide = false)
{
	$arColumn = _getFundEstTableColumn($arRef, $bFair, $bWide);
	_echoFundEstParagraph($arColumn, $bFair, $arRef, $str, $bWide);
}

function _getFundPositionStr($ref)
{
	$str = '';
	$fPosition = RefGetPosition($ref);
	if ($fPosition < 1.0)									$str .= GetFundPositionLink($ref->GetSymbol()).'值使用'.strval($fPosition).'。';
	if ($iHedge = FundGetHedgeVal($ref->GetStockId()))		$str .= '建议'.GetTableColumnConvert().strval($iHedge).'。';
	return $str;
}

function EchoFundEstParagraph($ref)
{
	$arRef = array($ref);
	$arColumn = _getFundEstTableColumn($arRef, $bFair);
	
	$str = _getFundPositionStr($ref);
    if ($ref->GetRealtimeNav())
    {
    	$col = $bFair ? $arColumn[6] : $arColumn[4]; 
    	$est_ref = $ref->GetEstRef();
    	$realtime_ref = $ref->GetRealtimeRef();
    	$str .= $col->GetDisplay().$realtime_ref->GetMyStockLink().'和'.SymCalibrationHistoryLink($est_ref).'关联程度按照100%估算。';
    }
    
	_echoFundEstParagraph($arColumn, $bFair, $arRef, $str);
   	EchoCalibrationHistoryParagraph($ref, 0, 1);
}

function EchoHoldingsEstParagraph($ref)
{
	$arRef = array($ref);
	$arColumn = _getFundEstTableColumn($arRef, $bFair);
	
	$str = _getFundPositionStr($ref);
	$str .= GetHoldingsLink($ref->GetSymbol()).'更新于'.$ref->GetHoldingsDate().'。';

	_echoFundEstParagraph($arColumn, $bFair, $arRef, $str);
   	EchoNetValueHistoryParagraph($ref, false, 0, 1);
}

?>
