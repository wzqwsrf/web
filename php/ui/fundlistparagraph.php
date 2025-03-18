<?php

function _getFundPairLink($ref)
{
	static $arSymbol = array();
	
	$strSymbol = $ref->GetSymbol();
	if (in_array($strSymbol, $arSymbol))		return $ref->GetDisplay();
	
	$arSymbol[] = $strSymbol;
	return $ref->GetMyStockLink();
}

function _echoFundListItem($ref, $sql, $last_sql, $callback)
{
    $strStockId = $ref->GetStockId();
    $fRatio = $ref->GetRatio();
    $fFactor = $ref->GetFactor();
	$ar = array();
	
	$ar[] = SymCalibrationHistoryLink($ref);
    $ar[] = _getFundPairLink($ref->GetPairRef());
    $ar[] = GetNumberDisplay($fRatio);
    $ar[] = GetNumberDisplay($fFactor);
    $ar[] = $sql->GetDateNow($strStockId);
    if ($callback)
    {
    	$ar[] = call_user_func($callback, $fRatio, $fFactor);
    }
    else
    {
    	if ($strVal = $last_sql->ReadVal($strStockId, true))	$ar[] = $ref->GetPriceDisplay($strVal);
    }
    RefEchoTableColumn($ref, $ar);
}

function EchoFundListParagraph($arRef, $callback = false)
{
	$str = GetFundListLink();
	EchoTableParagraphBegin(array(new TableColumnSymbol(),
								   new TableColumnSymbol('跟踪'),
								   new TableColumnPosition(),
								   new TableColumnCalibration(),
								   new TableColumnDate(),
								   ($callback ? new TableColumnConvert() : new TableColumn('参考值'))
								   ), 'fundlist', $str);
	
	$sql = GetCalibrationSql();
	$last_sql = new LastCalibrationSql();
	foreach ($arRef as $ref)		_echoFundListItem($ref, $sql, $last_sql, $callback);
    EchoTableParagraphEnd();
}

?>
