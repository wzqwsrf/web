<?php
require_once('_stock.php');
require_once('_emptygroup.php');
require_once('../../php/dateimagefile.php');

function _echoAhHistoryItem($csv, $ref, $h_ref, $cny_ref, $record)
{
	$strDate = $record['date'];
	if ($strHKCNY = $cny_ref->GetClose($strDate))
	{
		$strClose = rtrim0($record['close']);
		$ar = array($strDate, $strHKCNY, $strClose);
		
		if ($strCloseH = $h_ref->GetClose($strDate))
		{
			$fAh = $ref->GetPriceRatio($strDate);
			$csv->Write($strDate, $strClose, $strCloseH, $strHKCNY, strval_round($fAh));
			
			$ar[] = $strCloseH;
			$ar[] = GetRatioDisplay($fAh);
			$ar[] = GetRatioDisplay(1.0 / $fAh);
		}
		
		EchoTableColumn($ar);
	}
}

function _echoAhHistoryParagraph($ref, $h_ref, $iStart, $iNum, $bAdmin)
{
	$strSymbol = $ref->GetSymbol();
 	
    $str = GetAhCompareLink();
	$his_sql = GetStockHistorySql();
    $strStockId = $ref->GetStockId();
    $strMenuLink = StockGetMenuLink($strSymbol, $his_sql->Count($strStockId), $iStart, $iNum);
    $str .= ' '.$strMenuLink; 
    if ($bAdmin)		$str .= ' '.GetUpdateStockHistoryLink($ref).' '.GetUpdateStockHistoryLink($h_ref);

	$cny_ref = $ref->GetCnyRef();
	$ah_col = new TableColumnRatio('A/H');
	EchoTableParagraphBegin(array(new TableColumnDate(),
								   new TableColumnStock($cny_ref),
								   new TableColumnStock($ref),
								   new TableColumnStock($h_ref),
								   $ah_col,
								   new TableColumnRatio('H/A')
								   ), $strSymbol.'ahhistory', $str);

   	$csv = new PageCsvFile();
	if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result))		_echoAhHistoryItem($csv, $ref, $h_ref, $cny_ref, $record);
        mysqli_free_result($result);
    }
    $csv->Close();
    
    $str = $strMenuLink;
    if ($csv->HasFile())
    {
    	$jpg = new DateImageFile();
    	if ($jpg->Draw($csv->ReadColumn(4), $csv->ReadColumn(1)))		$str .= '<br />'.$csv->GetLink().'<br />'.$jpg->GetAll($ah_col->GetDisplay(), $strSymbol);
    }
    EchoTableParagraphEnd($str);
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
		$ref = new AhPairReference($ref->GetSymbol());
		if ($h_ref = $ref->GetPairRef())
		{
   			_echoAhHistoryParagraph($ref, $h_ref, $acct->GetStart(), $acct->GetNum(), $acct->IsAdmin());
		}
    }
    $acct->EchoLinks('chaos');
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetMetaDisplay(AH_HISTORY_DISPLAY);
    $str .= '页面. 按中国A股交易日期排序显示. 同时显示港币人民币中间价历史, 提供跟Yahoo或者Sina历史数据同步的功能. 仅包括2014-01-01以后的数据.';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetTitleDisplay(AH_HISTORY_DISPLAY);
}

    $acct = new SymbolAccount();
?>
