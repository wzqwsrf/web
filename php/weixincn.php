<?php
require_once('weixin.php');
require_once('stockbot.php');

function _wxDebug($strUserName, $strText, $strSubject)
{   
	$str = GetInfoElement('用户：').$strUserName;
	$str .= '<br />'.$strText;
	$str .= '<br />'.GetWeixinLink();
    trigger_error($strSubject.'<br />'.$str);
}

function _wxEmailInfo()
{
	return '发往'.ADMIN_EMAIL.'邮箱。'.BOT_EOL;
}

class WeixinStock extends WeixinCallback
{
    public function __construct() 
    {
    	SqlConnectDatabase();
    }

    function GetVersion()
    {
    	return WX_DEBUG_VER.' '.GetDevLink('palmmicro/20161014cn.php');
    }

	function GetUnknownText($strContents, $strUserName)
	{
		if ($strSymbol = BuildChinaFundSymbol($strContents))	{}
		else if ($strSymbol = BuildChinaStockSymbol($strContents))	{}
		else	$strSymbol = $strContents;
		
		if (stripos($strContents, 'http') !== false)	$strDebug = $strContents;
		else												$strDebug = GetXueqiuLink(new StockSymbol($strSymbol), $strContents).' '.GetMyStockLink($strSymbol, '更新数据');

		_wxDebug($strUserName, GetRemarkElement('内容：').$strDebug, 'Wechat message');
		$str = $strContents.BOT_EOL;
		$str .= '本公众号目前只提供部分股票交易和净值估算自动查询。因为没有匹配到信息，此消息内容已经'._wxEmailInfo();
		return $str;
	}

	public function OnText($strText, $strUserName)
	{
		LogBotVisit(TABLE_WECHAT_BOT, $strText, $strUserName);
	    
        if (strpos($strText, '商务合作') !== false)	return '请把具体合作内容和方式'._wxEmailInfo();
        
        if ($str = StockBotGetStr($strText, $this->GetVersion()))		return $str;
		return $this->GetUnknownText($strText, $strUserName);
	}

	function OnEvent($strContents, $strUserName)
	{
		switch ($strContents)
		{
		case 'subscribe':
			$str = '欢迎订阅。本账号为自动回复，不提供人工咨询服务。请用语音或者键盘输入要查找的内容，例如【162411】或者【中概】。';
			break;
			
		case 'unsubscribe':
			$str = '再见';
			break;
			
		case 'MASSSENDJOBFINISH':
			$str = '收到群发完毕';		// Mass send job finish
			break;
			
		default:
			$str = '(未知Event)';
			_wxDebug($strUserName, $str, 'Wechat '.$strContents);
			break;
		}
		return $str.BOT_EOL;
	}

	function OnImage($strUrl, $strUserName)
	{
		$strContents = '未知图像信息';
    
		if ($img = url_get_contents($strUrl))
		{
			$size = strlen($img);
			$strFileName = DebugGetWechatImageName(substr(md5($strUserName.DebugGetTime()), 16)); 
			$fp = @fopen($strFileName, 'w');  
			fwrite($fp, $img);  
			fclose($fp);  
//      	unset($img, $url);

        	$strLink = GetInternalLink(UrlGetPathName($strFileName));
        	$strContents .= "(已经保存到{$strLink})";
        }
    
        return $this->GetUnknownText($strContents, $strUserName);
    }
}

    $acct = new WeixinStock();
    $acct->Run();
?>
