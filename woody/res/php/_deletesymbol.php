<?php
require_once('_stock.php');
require_once('_emptygroup.php');

function _deleteHasAbPair($strSymbol)
{
	$sql = new AbPairSql();
	if ($strSymbolB = $sql->GetPairSymbol($strSymbol))
	{
		DebugString('(A)B stock existed: '.$strSymbolB);
		return true;
	}
	else if ($strSymbolA = $sql->GetSymbol($strSymbol))
	{
		DebugString('A(B) stock existed: '.$strSymbolA);
		return true;
	}
	return false;
}

function _deleteHasAhPair($strSymbol)
{
	$sql = new AhPairSql();
	if ($strSymbolH = $sql->GetPairSymbol($strSymbol))
	{
		DebugString('(A)H stock existed: '.$strSymbolH);
		return true;
	}
	else if ($strSymbolA = $sql->GetSymbol($strSymbol))
	{
		DebugString('A(H) stock existed: '.$strSymbolA);
		return true;
	}
	return false;
}

function _deleteHasAdrPair($strSymbol)
{
	$sql = new AdrPairSql();
	if ($strSymbolH = $sql->GetPairSymbol($strSymbol))
	{
		DebugString('(ADR)H existed: '.$strSymbolH);
		return true;
	}
	else if ($strAdr = $sql->GetSymbol($strSymbol))
	{
		DebugString('ADR(H) existed: '.$strAdr);
		return true;
	}
	return false;
}

function _deleteHasFundPair($strSymbol)
{
	$sql = new FundPairSql();
	if ($strIndex = $sql->GetPairSymbol($strSymbol))
	{
		DebugString('(Fund) Index existed: '.$strIndex);
		return true;
	}
	else if ($strFund = $sql->GetSymbol($strSymbol))
	{
		DebugString('Fund (Index) existed: '.$strFund);
		return true;
	}
	return false;
}

function _deleteHasCalibration($strStockId)
{
   	$calibration_sql = GetCalibrationSql();
	$iTotal = $calibration_sql->Count($strStockId);
	if ($iTotal > 0)
	{
		DebugVal($iTotal, 'Calibration history existed');
		$calibration_sql->DeleteAll($strStockId);
	}
	return false;
}

function _deleteStockSymbol($ref)
{
	$strSymbol = $ref->GetSymbol();
	$strStockId = $ref->GetStockId();

	DebugString('Deleting... '.$strSymbol);
	if (_deleteHasAbPair($strSymbol))					return;
	else if (_deleteHasAdrPair($strSymbol))			return;
	else if (_deleteHasAhPair($strSymbol))			return;
	else if (_deleteHasFundPair($strSymbol))			return;
	else if (_deleteHasCalibration($strStockId))		return;
/*	else if (($iTotal = SqlCountFundPurchaseByStockId($strStockId)) > 0)
	{
		DebugVal($iTotal, 'Fund purchase existed');
		return;
	}
*/
	if (SqlDeleteStockGroupItemByStockId($strStockId))
	{
		SqlDeleteStockEma($strStockId);
		SqlDeleteStockHistory($strStockId);
		SqlDeleteNavHistory($strStockId);
		SqlDeleteStock($strStockId);
		DebugString('已删除');
		SwitchRemoveFromSess('symbol='.$strSymbol);
	}
}

class _DeleteSymbolAccount extends SymbolAccount
{
    public function AdminProcess()
    {
	    if ($ref = $this->GetSymbolRef())
	    {
	        _deleteStockSymbol($ref);
	    }
	}
}

?>
