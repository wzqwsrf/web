<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/dateimagefile.php');
require_once('../../php/ui/netvaluehistoryparagraph.php');

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
    	$csv = new PageCsvFile();
   		EchoNetValueHistoryParagraph($ref, $csv, $acct->GetStart(), $acct->GetNum(), $acct->IsAdmin());
   		$csv->Close();
   		
   		if ($csv->HasFile())
   		{
   			$jpg = new DateImageFile();
   			if ($jpg->Draw($csv->ReadColumn(2), $csv->ReadColumn(1)))
   			{
   				EchoHtmlElement($csv->GetLink().'<br />'.$jpg->GetAll(STOCK_DISP_POSITION, $ref->GetSymbol()));
   			}
   		}
    }
    $acct->EchoLinks();
}    

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetMetaDisplay(NETVALUE_HISTORY_DISPLAY);
    $str .= '页面。用于某基金历史净值超过一定数量后的显示。最近的基金净值记录一般会直接显示在该基金页面。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetTitleDisplay(NETVALUE_HISTORY_DISPLAY);
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
