<?php
require_once('stock.php');

function _getHedgeQuantity($strHedge, $strAskQuantity)
{
	$fFloor = floor(floatval($strAskQuantity) / floatval($strHedge));
	return strval(intval($fFloor));
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
		$strIndex = $strSymbol;
		$ref = StockGetReference($strSymbol);
		if ($ref->IsSymbolA())
		{
			$strAskQuantity = false;
			if (isset($ref->arAskQuantity[0]))
			{
				$strAskPrice = $ref->arAskPrice[0];
				$arData['ask_price'] = $strAskPrice;
				$strAskQuantity = $ref->arAskQuantity[0];
				$arData['ask_quantity'] = $strAskQuantity;
			}
    	
			$strBidQuantity = false;
			if (isset($ref->arBidQuantity[0]))
			{
				$strBidPrice = $ref->arBidPrice[0];
				$arData['bid_price'] = $strBidPrice;
				$strBidQuantity = $ref->arBidQuantity[0];
				$arData['bid_quantity'] = $strBidQuantity;
			}
    	
			if ($ref->IsFundA())
			{
				$fund_ref = StockGetFundReference($strSymbol);
				if (method_exists($fund_ref, 'GetEstRef'))
				{	
					if ($est_ref = $fund_ref->GetEstRef())	$strIndex = $est_ref->GetSymbol();
				}
				else if ($strSymbol == 'SZ164906')			$strIndex = 'KWEB';

				$arData['symbol'] = $strSymbol;
				if ($strAskQuantity)		$arData['peer_ask_price'] = RefGetPeerVal($fund_ref, $strAskPrice);
				if ($strBidQuantity)		$arData['peer_bid_price'] = RefGetPeerVal($fund_ref, $strBidPrice);
				if ($strHedge = FundGetHedgeVal($ref->GetStockId()))
				{
					$arData['hedge'] = $strHedge;
					if ($strAskQuantity)		$arData['peer_ask_quantity'] = _getHedgeQuantity($strHedge, $strAskQuantity);
					if ($strBidQuantity)		$arData['peer_bid_quantity'] = _getHedgeQuantity($strHedge, $strBidQuantity);
				}
			}
		}
		$ar[$strIndex] = $arData;
    }
    
    return $ar;
}

?>
