<?php
require_once('htmlelement.php');

function EchoHtmlElement($strContent, $strTag = 'p', $arAttribute = false)
{
	$str = GetHtmlElement($strContent, $strTag, $arAttribute);
    echo <<<END

	$str
END;
}

function EchoTitle($strTitle)
{
	EchoHtmlElement($strTitle, 'title');
}

function EchoHeading($strHeading)
{
	EchoHtmlElement($strHeading, 'h1');
}

function EchoDocType()
{
	echo '<!DOCTYPE html>';
}

function EchoCharset()
{
	echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
}

function EchoCSS()
{
	echo '<link href="/common/style.css" rel="stylesheet" type="text/css" />';
}

function EchoViewPort()
{
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
}

?>
