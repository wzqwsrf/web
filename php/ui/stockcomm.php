<?php

function RefHasData($ref)
{
	if ($ref)
	{
		return $ref->HasData();
	}
	return false;
}

function RefGetMyStockLink($ref)
{
	if ($ref)
	{
		return $ref->GetMyStockLink();
	}
	return '';
}

function RefSortByNumeric($arRef, $callback)
{
    $ar = array();
    $arNum = array();
    
    foreach ($arRef as $ref)
    {
        $strSymbol = $ref->GetSymbol();
        $ar[$strSymbol] = $ref;
    	$arNum[$strSymbol] = call_user_func($callback, $ref);
    }
    asort($arNum, SORT_NUMERIC);
    
    $arSort = array();
    foreach ($arNum as $strSymbol => $fNum)
    {
        $arSort[] = $ar[$strSymbol];
    }
    return $arSort;
}

function RefSortBySymbol($arRef)
{
    $ar = array();
    foreach ($arRef as $ref)
    {
        $strSymbol = $ref->GetSymbol();
		if (isset($ar[$strSymbol]) == false)		 $ar[$strSymbol] = $ref; 
    }
    ksort($ar);
    
    $arSort = array();
    foreach ($ar as $str => $ref)
    {
        $arSort[] = $ref;
    }
    return $arSort;
}

function RefEchoTableColumn($ref, $ar, $bWide = false)
{
    EchoTableColumn($ar, false, $bWide ? false : SqlGetStockName($ref->GetSymbol()));
}

function GetArbitrageRatio($strStockId)
{
	if ($iHedge = FundGetHedgeVal($strStockId))		return $iHedge;
	return 1;
}

function GetArbitrageQuantity($strStockId, $fQuantity)
{
	return strval(round($fQuantity / GetArbitrageRatio($strStockId)));
}

function GetTurnoverDisplay($fVolume, $fShare, $iPrecision = 2)
{
	return strval_round(100.0 * $fVolume / ($fShare * 10000.0), $iPrecision).'%';
}

?>
