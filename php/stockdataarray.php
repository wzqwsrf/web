<?php
require_once('stock.php');

function _getHedgeQuantity($iHedge, $iQuantity)
{
	$fQuantity = floatval($iQuantity) / 100.0;
	$fQuantity = floor($fQuantity) * 100.0;
	$fFloor = floor($fQuantity / $iHedge);
	return intval($fFloor);
}

function GetStockDataArray($strSymbols)
{
	InitGlobalStockSql();
    $arSymbol = GetInputSymbolArray(SqlCleanString($strSymbols));
    StockPrefetchArrayExtendedData($arSymbol);
	
	$ar = array();
	foreach ($arSymbol as $strSymbol)
	{
		$arData = array();
		$ref = StockGetReference($strSymbol);
		if ($ref->IsSymbolA())
		{
			$iAskQuantity = false;
			if (isset($ref->arAskQuantity[0]))
			{
				$strAskPrice = $ref->arAskPrice[0];
				$arData['ask_price'] = $strAskPrice;
				$iAskQuantity = intval($ref->arAskQuantity[0]);
				$arData['ask_size'] = $iAskQuantity;
			}
    	
			$iBidQuantity = false;
			if (isset($ref->arBidQuantity[0]))
			{
				$strBidPrice = $ref->arBidPrice[0];
				$arData['bid_price'] = $strBidPrice;
				$iBidQuantity = intval($ref->arBidQuantity[0]);
				$arData['bid_size'] = $iBidQuantity;
			}
    	
			if ($ref->IsFundA())
			{
				$fund_ref = StockGetFundReference($strSymbol);
				if (method_exists($fund_ref, 'GetEstRef'))
				{	
					if ($est_ref = $fund_ref->GetEstRef())	$strIndex = $est_ref->GetSymbol();
				}
				else if ($strSymbol == 'SZ164906')			$strIndex = 'KWEB';

				$iHedge = GetArbitrageRatio($ref->GetStockId());
				$arData['hedge'] = $iHedge;
				$arData['symbol_hedge'] = $strIndex;
				if ($iAskQuantity)
				{
					$arData['ask_price_hedge'] = RefGetPeerVal($fund_ref, $strAskPrice);
					$arData['ask_size_hedge'] = _getHedgeQuantity($iHedge, $iAskQuantity);
				}
				if ($iBidQuantity)
				{
					$arData['bid_price_hedge'] = RefGetPeerVal($fund_ref, $strBidPrice);
					$arData['bid_size_hedge'] = _getHedgeQuantity($iHedge, $iBidQuantity);
				}
			}
		}
		$ar[$strSymbol] = $arData;
    }
    
    return $ar;
}

?>
