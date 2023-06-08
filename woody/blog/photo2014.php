<?php 
require('php/_blogphoto.php');

function GetMetaDescription($bChinese)
{
	return 'Pictures from Woody 2014 blog. Including screen shot of AR1688 Manager.exe Spanish mfc120u.dll error message and Chinese stock portfolio etc.';
}

function EchoAll($bChinese)
{
	$strSapphire = GetBlogLink('20141204', $bChinese);
	$strWorried = ImgWorriedWoody($bChinese);
	
    echo <<<END
<p>Dec 4 The Origin of $strSapphire
$strWorried
</p>

<p>Oct 16 Rules for Giant Chinese State-owned <a href="entertainment/20141016.php">Stock</a>
<br /><img src=photo/20141016.jpg alt="Screen shot of my Chinese A stock portfolio as of Oct 16 2014" /></p>

<p>June 15 Upgrade to <a href="entertainment/20140615.php">Visual Studio</a> 2013
<br /><img src=photo/mfc120u.png alt="Screen shot of AR1688 Manager.exe Spanish mfc120u.dll error message." /></p>

<p>Apr 5 <a href="pa1688/20140405.php">The Good, the Bad and the Ugly</a>
<br /><img src=../../pa1688/user/ehog/pcb.jpg alt="PA1688 eHOG 1-port FXS gateway internal PCB." /></p>
END;
}

require('../../php/ui/_disp.php');
?>
