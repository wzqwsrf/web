<?php
require_once('htmlelement.php');

function EchoNewLine($str)
{
    echo <<<END

    $str
END;
}

function EchoHtmlElement($strContent, $strTag = 'p', $arAttribute = false)
{
	EchoNewLine(GetHtmlElement($strContent, $strTag, $arAttribute));
}

function EchoTitle($strTitle)
{
	EchoHtmlElement($strTitle, 'title');
}

function EchoHeading($strHeading)
{
	EchoHtmlElement($strHeading, 'h1');
}

function EchoNobody()
{
	$bChinese = UrlIsEnglish() ? false : true;
	$strLang = $bChinese ? 'zh-Hans' : 'en';
	
    echo <<<END
<!DOCTYPE html>
<html lang="$strLang">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
END;

	EchoHead($bChinese);
	
    echo <<<END2

</head>

END2;
}

function EchoCSS()
{
	EchoNewLine('<link href="/common/style.css" rel="stylesheet" type="text/css" />');
}

function EchoViewPort()
{
	EchoNewLine('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
}

?>
