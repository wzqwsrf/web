<?php require_once('php/_entertainment.php'); ?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title><?php echo GetBlogTitle(20110509, true, false); ?></title>
<meta name="description" content="为什么我在网络日志上加入了Google AdSense的广告. 一周年投放广告的总结. GFW让这里成了Palmmicro.com唯一放Google的搜索框(AdSense for Search)的地方.">
<?php EchoInsideHead(); ?>
<link href="../../../common/style.css" rel="stylesheet" type="text/css" />
</head>

<body bgproperties=fixed leftmargin=0 topmargin=0>
<?php _LayoutTopLeft(); ?>

<div>
<h1><?php echo GetBlogTitle(20110509, true, false); ?></h1>
<p>2011年5月9日
<br />随着我网络日志中内容增多, 有时候我发现有必要搜索曾经写过什么题材和内容. 我记得大约一年多前看到过绿野一个摄影爱好者搞的莫非时光网站上有个Google搜索框. 我找到了Google Site Search却放弃了, 因为它每年要收至少100美元的服务费.
<br />在我使用Google Analytics和Google Webmaster的过去这一年中, 我经常会看到<a name="adsense">AdSense</a>和AdWords的链接. 但是直到上周我从北京到深圳的路上读了篇有关京东商城如何做网络推广的文章后, 才理解这2个服务的意思.
<br />随后我注册了AdSense服务, Google用了一周批准我的帐户. 现在我把Google的搜索框<font color=gray>AdSense for Search</font>以及投放的广告<font color=gray>AdSense for Content</font>放到了我所有的日志上, 就像本页最下面显示的一样.
<?php echo ImgWoody20060701(); ?>
</p>

<h3>Adsense周年小结</h3>
<p>2012年5月5日
<br />在我第一年的<a href="#adsense">Adsense</a>运营中, 大家阅读了15398次我的网络日志, 其中点击了50次广告. 给我的google帐户贡献了14.97美元的收入. 由于google满100美元支付一次, 我有希望在2019年把钱拿到手.
<br />另外根据Analytics的统计, 同期有8437个人访问了12487次<a href="../palmmicro/20080326cn.php">Palmmicro</a>网页, 看了总共74210个页面.
<br /><img src=../photo/20120505.jpg alt="Adsense anniversary summary and analytics statistics" />
</p>

<h3>Google的搜索框</h3>
<p>2015年10月4日
<br />由于<a href="../palmmicro/20100427cn.php">GFW</a>的影响, 从国内访问Palmmicro网站的时候Google的搜索框会导致所有Javascript延迟超过1分钟执行.
另外, 根据<a href="#adsense">Adsense</a>的统计, 在过去4年半这个搜索框一共也就被用过几十次, 估计都是我自己用的. 为改善用户体验, 我把它从其它页面都去掉了, 仅在本篇的中英文页面保留. 
</p>
<?php AdsenseSearchEngine(); ?>

<?php
function Echo20160122($strHead)
{
	$strHead = GetHeadElement($strHead);
	$strQuote = GetQuoteElement('Good news! On Jan 21, 2016, we sent you a payment for your Google AdSense earnings.');
	$strAdsense = GetNameLink('adsense', 'Adsense');
	$strSZ162411 = GetBlogLink(20150818);
	$strStock = GetBlogLink(20141016);

    echo <<<END
	$strHead
<p>2016年1月22日
<br />收到来自billing-noreply@google.com的一封信说{$strQuote} 去我的Bank of America的账户看，果然来了一笔108.11美元的收款。
<br />第一笔{$strAdsense}收入比2012年时候预计的来得早了3年，主要是因为过去几个月我的{$strSZ162411}净值页面吸引了很多新用户，现在差不多每天已经有1000次广告展示。
<br />有{$strStock}用户觉得我网站的古老排版很难看，尤其是在宽屏电脑上，右边总是空荡荡的一大片。我就把页面最底部的广告挪到了电脑用户的右边。
</p>
END;
}
	Echo20160122(GetNameTag('first', '第一笔').'Adsense收入');
?>

<h3><a name="second">第二笔</a>Adsense收入</h3>
<p>2018年7月23日
<br />进入7月后就一直在等Google给我发邮件, 昨天终于收到了来自payments-noreply@google.com的付款信. 时隔2年半后, 我的Bank of America的账户又有了一笔103.81美元的收款.
</p>

<h3>Google<a name="other">之外</a>的收入</h3>
<p>2018年10月30日
<br />进入移动互联网时代, Google赖以起家的羊毛出在猪身上的广告模式明显落伍了. 国内BAT慢慢变成了AT, 百度落伍的原因当然很多, 不过在我看来最重要的一点就是它没有成功的移动支付渠道. 
<br />努力拥抱知识付费时代, 我给自己的网站也加上了流行的微信打赏.
</p>
<?php EchoHtmlElement(GetWechatPay(1)); ?>

<h3><a name="third">第三笔</a>Adsense收入</h3>
<p>2020年10月24日
<br />进入8月后, XOP来了个死猫跳, 华宝油气持续折价导致网站访问量下降. 看到惨淡的<a href="#adsense">Adsense</a>广告收入我有点坐不住了, 觉得这样下去还是要等满两年半时间才能收到第三笔收入. 
于是我在9月初把手机用户的底部广告直接挪到了最上面, 然后又在原来底部的位置增加了一个上下文相关的广告. 广告的改动配合华宝油气开始持续溢价带来的网站访问量翻倍, 终于在9月底时凑满了第三个100美元.
<br />按惯例Google又多审查了20天, 扣除了它自认为的各种违规部分, 终于在10月21日付款105.13美元. 这次距离<a href="#second">第二笔</a>收入的时间是27个月.
<br />限购100块人民币, 史上蚊子最小和持续时间最长的SZ162411拖拉机溢价申购套利让我痛定思痛在9月下旬开始了AutoIt<?php EchoAutoTractorLink(); ?>车队的软件开发, 也再次让我重新体验到了2015年估值软件<a href="#first">刚开始</a>发布时候的用户热情. 
从十月到目前的数据看, 也许只要再等一年就能拿到第四笔Adsense收入了.
</p>

<?php
function Echo20211222($strHead)
{
	$strHead = GetHeadElement($strHead);
	$strThird = GetNameLink('third', '第三笔');
	$strChinaInternet = GetStockCategoryLink('chinainternet');
	$strSZ164906 = GetGroupStockLink('SZ164906', true);

    echo <<<END
	$strHead
<p>2021年12月22日 周三
<br />在十四个月后，收到了第四笔100.98美元的收入。跟一年多前{$strThird}时想象的不同，拖拉机软件并没有持续多久的热度，今年是靠一路下跌的{$strChinaInternet}维持住了网站的流量。 
最开始时候华宝油气一枝独秀的时代已经结束，在过去的三十天里{$strSZ164906}估值网页的访问量已经是华宝油气的三倍。
</p>
END;
}

function Echo20250121($strHead)
{
	$strHead = GetHeadElement($strHead);
	$strTurbify = GetExternalLink('https://www.turbify.com/', 'Turbify');
	$strYahoo = GetInternalLink('/res/translationcn.html#webhosting', 'Yahoo网站服务');

    echo <<<END
	$strHead
<p>2025年1月21日 周二
<br />在三年外加一个月后，终于收到了第五笔100.14美元的收入。其实网站的访问量一直在稳步增长，广告收入下降的主要原因是传统网页实在是落伍了，大家都在用手机APP，广告商的投放也都集中在手机APP上，网页的浏览和点击都是越来越不值钱了。 
<br />雪上加霜的是，疫情后美元疯狂贬值，现在叫{$strTurbify}的{$strYahoo}从2024年开始每个月收费从原来的12美元跳涨到了18美元，指望广告费能覆盖网站费用成了不可能的事情。
</p>
END;
}

	Echo20211222(GetNameTag('forth', '第四笔').'Adsense收入');
	Echo20250121(GetNameTag('fifth', '第五笔').'Adsense收入');
?>

</div>

<?php _LayoutBottom(); ?>

</body>
</html>
