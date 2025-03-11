<?php
require_once('stock.php');

// 'SH513350', 'SZ159518', 'SZ161127', 'SZ162411', 'SZ164906'
/*
function _refGetPeerVal($ref, $strQdii)
{
	$cny_ref = $ref->GetCnyRef();
    $strStockId = $ref->GetStockId();
    $calibration_sql = GetCalibrationSql();
    $strDate = $calibration_sql->GetDateNow($strStockId);
    		
    $fVal = FundReverseAdjustPosition(RefGetPosition($ref), floatval($strQdii), floatval(SqlGetNavByDate($strStockId, $strDate)));
    $fEst = QdiiGetPeerVal($fVal, floatval($cny_ref->GetPrice()), floatval($calibration_sql->GetCloseNow($strStockId)));
    return strval_round($fEst, 4);
}

function _getHedgeQuantity($iHedge, $iQuantity)
{
	$fQuantity = floatval($iQuantity) / 100.0;
	$fQuantity = floor($fQuantity) * 100.0;
	$fFloor = floor($fQuantity / $iHedge);
	return intval($fFloor);
}
*/
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
/*			$iAskQuantity = false;
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
*/    	
			if ($ref->IsFundA())
			{
				$fund_ref = StockGetFundReference($strSymbol);
				$strOrgIndex = false;
				if (method_exists($fund_ref, 'GetEstRef'))
				{	
					if ($est_ref = $fund_ref->GetEstRef())
					{
						$strIndex = $est_ref->GetSymbol();
						if ($strIndex == '^GSPC')
						{
							$strOrgIndex = $strIndex;
							$strIndex = 'SPY';
						}
						else if ($strIndex == '^NDX')
						{
							$strOrgIndex = $strIndex;
							$strIndex = 'QQQ';
						}
					}
				}
				else if ($strSymbol == 'SZ164906')			$strIndex = 'KWEB';

				$strStockId = $ref->GetStockId();
//				$iHedge = GetArbitrageRatio($strStockId);
//				$arData['hedge'] = $iHedge;
				$arData['symbol_hedge'] = $strIndex;
/*				if ($iAskQuantity)
				{
					$arData['ask_price_hedge'] = _refGetPeerVal($fund_ref, $strAskPrice);
					$arData['ask_size_hedge'] = _getHedgeQuantity($iHedge, $iAskQuantity);
				}
				if ($iBidQuantity)
				{
					$arData['bid_price_hedge'] = _refGetPeerVal($fund_ref, $strBidPrice);
					$arData['bid_size_hedge'] = _getHedgeQuantity($iHedge, $iBidQuantity);
				}
*/				
				$arData['position'] = strval(RefGetPosition($fund_ref));

				$calibration_sql = GetCalibrationSql();
				$strDate = $calibration_sql->GetDateNow($strStockId);
				$arData['nav'] = SqlGetNavByDate($strStockId, $strDate);
				$arData['calibration'] = $calibration_sql->GetCloseNow($strStockId);
				if ($strOrgIndex)
				{
					$arData['calibration'] = strval(floatval($arData['calibration']) / floatval($calibration_sql->GetCloseNow(SqlGetStockId($strIndex))));
				}
				
				$cny_ref = $fund_ref->GetCnyRef();
				$arData['CNY'] = $cny_ref->GetPrice();
			}
		}
		$ar[$strSymbol] = $arData;
    }
    
    return $ar;
}

?>
