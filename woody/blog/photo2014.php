<?php 
require('php/_blogphoto.php');

function GetMetaDescription($bChinese)
{
	return 'Pictures from Woody 2014 blog. Including PA1688 eHOG 1-port FXS gateway internal PCB and Chinese stock portfolio etc.';
}

function EchoAll($bChinese)
{
	$strSapphire = GetHtmlElement('Dec 4 The Origin of '.GetBlogLink(20141204, $bChinese).' '.ImgWorriedWoody($bChinese));
	$strStock = GetBlogPictureParagraph(20141016, 'ImgPortfolio20141016', $bChinese);
	
    echo <<<END
$strSapphire
$strStock

<p>Apr 5 <a href="pa1688/20140405.php">The Good, the Bad and the Ugly</a>
<br /><img src=../../pa1688/user/ehog/pcb.jpg alt="PA1688 eHOG 1-port FXS gateway internal PCB." /></p>
END;
}

require('../../php/ui/_disp.php');
?>
