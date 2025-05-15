<?php require_once('php/_20150818.php'); ?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title><?php echo GetBlogTitle(20150818, true, false); ?></title>
<meta name="description" content="根据SPDR标普油气开采指数ETF(XOP)、标普油气开采指数(^SPSIOP)和美元对人民币的汇率计算QDII基金华宝油气(SZ162411)净值的PHP程序的开发过程记录。">
<?php EchoInsideHead(); ?>
<link href="../../../common/style.css" rel="stylesheet" type="text/css" />
</head>

<body bgproperties=fixed leftmargin=0 topmargin=0>
<?php _LayoutTopLeft(); ?>

<?php DemoPrefetchData(); ?>

<div>
<h1><?php echo GetBlogTitle(20150818, true, false); ?></h1>
<p>2015年8月18日
<br />眼看Qualcomm收购CSR<?php echo GetBlogLink(20141016); ?>的现金快要到账，最近我在琢磨在A股中国特色的QDII基金华宝油气和美股XOP之间套利。每天看Yahoo新浪等网站的股票行情，时不时还要用鼠标点开计算器算算转换价格，时间长了后有点烦。
<br />后来我想起来5年前学习的<?php echo GetBlogLink(20100905); ?>，于是打算写我的第二个PHP程序，统一把套利需要常看的行情显示在一起。
同时根据SPDR标普油气开采指数ETF(XOP)、标普油气开采指数(^SPSIOP)、以及美元对人民币的汇率计算<?php echo GetGroupStockLink(FUND_DEMO_SYMBOL, true); ?>净值。今天出了第一版，记录下相关开发过程以备日后查阅。A股的QDII基金缺乏及时的信息更新，希望这里能够补上这个生态位空缺。
<br />谢谢<?php EchoXueqiuId('6188729918', 'abkoooo'); ?>帮助提供了新浪实时美股数据接口的格式。
美股、A股、期货和汇率都用新浪实时的数据接口：<?php EchoSinaDataLink('gb_xop,sz162411,hf_CL,USDCNY'); ?>
<br />一开始发现无论怎么弄<?php echo GetCodeElement('fopen'); ?>打开这些链接都会失败，估计是我用的Yahoo网站服务不支持<?php echo GetCodeElement('allow_url_fopen'); ?>。 
在网上找解决方法，发现应该用早就有的curl。抄了2段curl代码，仿照<?php echo GetCodeElement('file_get_contents'); ?>函数的名字加了个<?php echo GetCodeElement('url_get_contents'); ?>函数。
<br />为提高页面反应速度，使用2个文件<?php EchoSinaDebugLink('gb_xop'); ?>和<?php EchoSinaDebugLink('sz162411'); ?>分别保存最后更新的股票数据，同时实施以下优化：
</p>
<?php echo GetListElement(array('跟文件时间在同一分钟内的页面请求直接使用原来文件内的数据。',
								  '美股盘后交易结束后的页面请求直接使用gb_xop.txt内的美股数据。',
								  'A股闭市后的页面请求直接使用sz162411.txt内的A股数据。'));
?>
<p>类似的，原油期货数据缓存在文件<?php EchoSinaDebugLink('hf_cl'); ?>，美元人民币汇率数据在usdcny.txt。
<br /><?php echo GetQuoteElement('所有的代码最终都会烂到无法维护，成功的项目就是在烂掉之前发布出去的。'); ?>
<?php echo ImgCompleteLenna(); ?>
</p>

<h3><a name="sma">SMA</a></h3>
<p>2015年8月20日
<br />为严格交易规则, 套利过程中我打算简单的在均线上下单买卖XOP. 这个版本加入下一个交易日XOP预计的常用SMA均线值,
以及当前成交价格相对于华宝油气对应净值的溢价.
<br />作为一个懒人, 我给自己预定的交易模式是盘前下单, 交易时间不盯盘. 关注过新浪等网站提供的均线指标的都知道, 这些值是随着当天的交易一直在变动的. 
因此我需要在盘前下单时先算出一个不动点用于买卖.
<br />从最简单的5日线说起:
<br />A5 = (X0 + X1 + X2 + X3 + X4) / 5;
<br />其中X0是当天交易价, X1-X4是前4个交易日的收盘价. 为方便起见, 可以把 (X1 + X2 + X3 + X4) 写成 ∑X4. 按这个模式, 任意日的简单均线SMA可以表示成:
<br />An = (X0 + ∑Xm) / n; 其中m =  n - 1;
<br />在什么时候会有一个不动点可以用于买卖呢? 显然是An = X0的时候, 这样An = (An + ∑Xm) / n, 从这个简单的一元一次方程可以解出An不依赖于X0的表达式:
<br />An = ∑Xm / (n - 1); 或者 An = ∑Xm / m;
<br />这样就很清楚了, 当我说5日线的时候, 我实际算的是前4个交易日收盘价的平均值. 当我说20周线的时候, 我实际算的是前19周每周最后一个交易日收盘价的平均值.
这样算出来的不动点是极限值, 所以我整天装神弄鬼说XOP过了什么什么均线算强势, 没过什么什么均线算弱势. 而这些装神弄鬼的背后, 其实用到的都是小学数学.
<br />XOP历史数据每天只需要更新一次, 采用Yahoo股票历史数据: <?php EchoExternalLink(GetYahooStockHistoryUrl('XOP')); ?>,
<br />同样每天只需要更新一次的还有华宝油气基金官方净值, 来自于<?php EchoSinaDataLink('f_162411'); ?>,
使用文件<?php EchoSinaDebugLink('f_162411'); ?>缓存, 因为不知道什么时候更新当日数据, 只好采用一个小时更新一次的笨办法.
<br />增加调试文件<?php echo GetDebugFileLink(); ?>用于临时查看数据.
</p>

<?php
	Echo20150821(GetNameTag('mobiledetect', '检测手机'));
	Echo20150824('增加'.GetNameTag('stockhistory', STOCK_HISTORY_DISPLAY).'页面');
	EchoPage20150827('qdiihk');
?>

<h3>股票<a name="mystocktransaction">交易</a>记录</h3>
<p>2015年9月13日
<br />跟我的第一个PHP程序结合起来, 用户登录后可以输入相关股票交易记录. 根据交易记录计算华宝油气和XOP对冲交易策略和数据.
<br />交易记录的输入和处理分别在文件/woody/res/php/<b>_edittransactionform.php</b>和<b>_submittransaction.php</b>. 
同时修改<a href="20100529cn.php">Visual C++</a>的Woody的网站工具对_editXXXform.php名字格式的自动生成对应的_submitXXX.php文件. 
</p>

<h3>开始自己写PHP的class类</h3>
<p>2015年11月7日
<br />分离数据和用户界面代码，把QDII用到的股票数据部分放到<font color=olive>StockReference</font>类中，用在<font color=olive>QdiiAccount</font>类中。
<br />继续整理代码，为热心吃螃蟹的用户们增加<a href="../../res/sh513100cn.php">纳指ETF</a>、<a href="../../res/sz160717cn.php">恒生H股</a>、<a href="../../res/sz160216cn.php">国泰商品</a>、<a href="../../res/sz160416cn.php">石油基金</a>和<a href="../../res/sz163208cn.php">诺安油气</a>等页面.
</p>

<?php
	Echo20160108('增加'.GetNameTag('fundhistory', FUND_HISTORY_DISPLAY).'页面');
	Echo20160126('统一数据显示格式');
	Echo20160127('ETF和'.GetNameTag('lof'));
	Echo20160216('增加'.GetNameTag('calibrationhistory', CALIBRATION_HISTORY_DISPLAY).'页面');
	Echo20160222('增加'.GetNameTag('netvaluehistory', NETVALUE_HISTORY_DISPLAY).'页面');
	Echo20160226('周期三意味着'.GetNameTag('chaos', '混沌'));
	Echo20160423('新浪实时港股数据');
	Echo20160515('近几年来最低级的错误');
?>

<h3><?php EchoNameTag('myportfolio', MY_PORTFOLIO_DISPLAY); ?></h3>
<p>2016年6月5日
<br />王小波总是不忘记唠叨他写了自己用的编辑软件，在20年前我是暗自嘲笑的。没想到过了这么些年以后，我也开始写自己用的炒股软件了。不同的年龄段心态是完全不同的。
<br /><?php echo GetMyPortfolioLink('email=woody@palmmicro.com'); ?>功能刚完成的时候页面出来得奇慢无比，而接下来刷新就会快很多。因为对自己的MySQL水平没有自信心，我一头扎进了优化数据库的工作中。
优化了一些明显的问题，例如扩展了stockgroupitem表的内容，把stocktransaction表中groupitem_id相同的交易预先统计好存在stockgroupitem表中，避免每次都重新查询stocktransaction表然后重新计算一次。
不过折腾了一大圈后并没有明显的改善，倒是在这个过程中理清了原来闭着眼睛写的代码的内在逻辑，看出来了问题的根源。
<br />在按member_id查询<?php EchoNameTag('mystockgroup', TABLE_STOCK_GROUP); ?>表找到这个人所有的<?php echo GetMyStockGroupLink('email=woody@palmmicro.com'); ?>后，我会对每个stockgroup直接构造<?php echo GetCodeElement('MyStockGroup'); ?>类，
在它原来的构造函数代码中，会马上对该stockgroup中的每个stock构建一个<?php echo GetCodeElement('MyStockTransaction'); ?>类, 而<?php echo GetCodeElement('MyStockTransaction'); ?>的构造函数又需要这个stock的<?php echo GetCodeElement('MyStockReference'); ?>类作为参数,
如果没有现成的实例可用，就会新构造一个。结果就是在首次统计持仓盈利的过程中，我会把几乎所有股票的数据都去新浪拿一遍，难怪那么慢。 
<br />找到问题就好办了，首先判断stockgroup中stock对应的groupitem_id到底有没有交易记录，没有的话就不去构造<?php echo GetCodeElement('MyStockTransaction'); ?>类。另外预先统计好有交易记录的stock，统一去预取一下新浪数据。
<br />随后我把预取数据的思路用在了所有需要读取新浪数据的地方，包括华宝油气净值计算在内，所有的页面反应速度都有不同程度的提升。原来我说因为网站服务器在美国所以访问慢的理由看来并不是那么准确的！
<?php echo GetWoodyImgQuote('pig.jpg', '一头特立独行的猪'); ?>
</p> 

<?php
	Echo20160615('东方财富美元人民币中间价'.GetNameTag('uscny'));
	EchoPage20160818('qdii');
?>

<h3><?php EchoNameTag('thanousparadox', THANOUS_PARADOX_DISPLAY); ?></h3>
<p>2016年9月18日
<br />不知不觉中宣传和实践华宝油气和XOP跨市场套利已经快2年了. 期间碰到过<?php EchoXueqiuId('4389829893', 'LIFEFORCE'); ?>这种自己动手回测验证一下能赚钱就果断开干的, 
也有<?php EchoXueqiuId('8871221437', '老孙'); ?>这种数学爱好者回测验证一下能赚个年化10%后就袖手旁观的,
还有常胜将军<?php EchoXueqiuId('1980527278', 'Billyye'); ?>这种觉得华宝油气可以看成无非是XOP延长了的盘前盘后交易没有多少套利意义的. 
最气人的是thanous这种, 总是喜欢说大资金如何牛, 如果白天华宝油气在大交易量下溢价, 晚上XOP必然是要涨的, 彻底否定套利的根基.
<br />最近几个月华宝油气折价多溢价少, 经历了几次溢价的情况后, 发现<?php EchoXueqiuId('5421738073', 'thanous'); ?>的说法基本靠谱, 我于是开始按他的名字命名为小心愿定律. 中秋节前最后一个交易日华宝油气又溢价了, 
<?php EchoXueqiuId('6900406005', '大熊宝宝--林某人'); ?>建议我实际测算一下, 正好放假闲着也是闲着, 就削尖铅笔搞了个新页面测试<?php echo GetThanousParadoxLink(FUND_DEMO_SYMBOL); ?>.
我网站记录了从去年底以来所有的华宝油气数据, 跑了下从去年底到现在的统计结果没有觉得小心愿定律能成立, 于是改名为小心愿佯谬. 但是去掉春节前后华宝油气因为停止申购导致的长期溢价的影响, 只考虑最近100个交易日的情况后, 
有趣的结果出现了:
<br /><img src=../photo/20160918.jpg alt="Screen shot of test Thanous Law on Sep 18, 2016" />
<br />这里只统计了94个数据, 因为美股休市没有交易的日子被忽略了. 在华宝油气折价交易的情况下, 当晚XOP依旧是涨跌互现没有什么规律. 但是在平价和溢价的时候, 小心愿定律的确是明显大概率成立的!
<br />说白了, 如果你发现了什么交易上的规律, 只是因为交易得不够多而已.
</p>

<?php
	Echo20161008('新浪国内期货实时数据接口的字段意义');
	Echo20161017('新浪实时美股数据接口的字段意义');
	Echo20161020(GetNameTag('weixin', '微信公众号').'查询A股股票数据');
	Echo20161028('查询A股基金数据');
	Echo20170128('增加'.GetNameTag('ahcompare', AH_COMPARE_DISPLAY).'页面');
?>

<h3><a name="bollinger">布林</a>线</h3>
<p>2017年4月2日
<br /><a href="#sma">SMA</a>均线交易XOP很有效, 但是有时候会在价格突破所有均线后失去用武之地, 因此我把布林线也加入进了交易系统, 跟SMA放同一个表格中显示.
<br />同样为避免半夜盯盘, 我还是需要一个不动的布林线交易点. 这时候小学数学就不够了, 要用到一点初中数学知识. 沿用上面的SMA表达方式, 布林线的出发点依然是简单均线SMA, 可以换个符号写成:
<br />B = An = (X0 + ∑Xm) / n; 其中m = n - 1;
<br />在计算出B后, 继续计算标准差σ:
<br />σ² = ((X0 - B)² + (X1 - B)² + (X2 - B)² + ... + (Xm - B)²) / n;
<br />B和σ都计算出来后, 布林上轨是B + 2 * σ; 布林下轨是B - 2 * σ;
<br />现在来计算用来交易的不动点, 为简化起见, 先只考虑布林下轨, 就是说当天交易价格X0刚好到布林下轨的情况, 用这个价格计算对应的布林值. 写成条件是:
<br />X0 = B - 2 * σ;
<br />带入到上面计算B的公式后得到:
<br />B = (B - 2 * σ + ∑Xm) / n
<br />从而解出σ = (∑Xm - (n - 1) * B) / 2; 或者 σ = (∑Xm - m * B) / 2;
<br />再把条件 X0 = B - 2 * σ; 带入到计算σ的公式后得到:
<br />σ² = (4 * σ² + (X1 - B)² + (X2 - B)² + ... + (Xm - B)²) / n;
<br />定义∑Xm² = X1² + X2² + ... Xm²; 上面可以写成:
<br />(n - 4) * σ² = ∑Xm² - 2 * ∑Xm * B + m * B²;
<br />带入上面解出的σ = (∑Xm - m * B) / 2; 最后得到一个B的一元二次方程:
<br />(n - 4) * (∑Xm - m * B)² = 4 * ∑Xm² - 8 * ∑Xm * B + 4 * m * B²;
<br />令 k = n - 4; 写成标准的ax²+bx+c=0的格式:
<br />a = k * m² - 4 * m;
<br />b = (8 - 2 * k * m) * ∑Xm;
<br />c = k * (∑Xm)² - 4 * ∑Xm²;
<br /><img src=../photo/root.jpg alt="The roots of quadratic equation with one unknown" />
<br />做计算机最擅长的事情, 解出这个方程的2个根得到2个不同B值. 
然后数学的神奇魅力出现了, 虽然列方程的时候只考虑了布林下轨, 解出B的2个值以及对应的σ后, 却同时得到了不依赖于当天交易价X0的布林上轨和布林下轨值.
<br />实际应用中我采用大家都用的20天布林线, 也就是说n = 20, 而我是用前19个交易日的收盘价算的当日不动点的布林上轨和布林下轨, 作为交易价格.
不像简单均线SMA的不动点, 可以从一元一次方程中解出一个很容易理解的表述: 20天的SMA不动点就是前19天收盘价的平均值.
解布林线一元二次方程得到的结果就没有一个类似的容易理解的表述了, 很难说它是什么, 但是可以很简单的说它不是什么, 它不是只算19天的布林上轨和布林下轨.
事实上, 因为考虑的都是极限因素, 20天布林上下轨不动点的开口要比只算前19天的布林上下轨大, 就是说, 下轨更低一点而上轨更高一点.
<br /><img src=../photo/20170402.jpg alt="Script for bollinger quadratic equation with one unknown" />
<br />感觉好久没做这么复杂的数学了, 把计算过程拍了个照片留念一下.
<br /><font color=gray>你永远比你想象中更勇敢 -- 相信梦想</font>
</p>

<?php
	Echo20171001('200日和50日'.GetNameTag('ema').'均线');
	Echo20180327('走火入魔的'.GetNameTag('nextsma', 'T+1').'均线');
	Echo20180404('增加'.GetNameTag('adrhcompare', ADRH_COMPARE_DISPLAY).'页面');
	Echo20180405('增加'.GetNameTag('abcompare', AB_COMPARE_DISPLAY).'页面');
	Echo20180413('中国外汇交易中心的中间价接口');
?>

<h3><?php EchoNameTag('nvclosehistory', NVCLOSE_HISTORY_DISPLAY); ?></h3>
<p>2018年5月3日
<br />交易了几年XOP下来, 发现它的收盘价经常跟净值有个1分2分的偏差, 不知道这其中是否有套利机会.
</p>
<?php EchoNvCloseDemo(); ?>
<p>增加这个页面倒是让我突然下了决心删除英文版本. 压死骆驼的最后一根稻草是这行代码, 混在其中的中文冒号让我恶向胆边生, 彻底放弃了本来就几乎没有什么浏览量的英文版本股票软件.
</p>
<blockquote><code>echo UrlGetQueryDisplay('symbol').($bChinese ? '净值和收盘价比较' :  ' NetValue Close History Compare');</code></blockquote>
<p>从软件开发的角度来说, 遍布我PHP代码的1000多个$bChinese肯定意味着某种代码结构缺陷, 希望这次代码清理完成后能让我醒悟过来.
<br />冷静下来后仔细想想, 发现自己早有停止英文版的意图背后其实有个更深层的原因. 三年来的各种跨市场套利经历, 让我深深体会到了对手盘的重要性和A股韭菜的可贵, 从而不愿意留个英文版让外面的世界进来抢着割这么嫩的韭菜.
<br /><font color=gray>If you've been playing poker for half an hour and you still don't know who the patsy is, you're the patsy. — Warren Buffett</font>
<?php echo ImgBuffettCards(); ?>
</p>

<?php
	Echo20180620('chinaindex');
	Echo20191025('增加'.GetNameTag('fundposition', FUND_POSITION_DISPLAY).'页面');
	Echo20200113('华宝油气的C类份额');
	Echo20200326('国泰商品已经只剩大半桶油');
	
function Echo20210624($strHead)
{
	$strHead = GetHeadElement($strHead);
	$strKWEB = GetHoldingsLink('KWEB', false);
	$strQDII = _getStockMenuLink('qdii');
	$strSZ164906 = GetGroupStockLink('SZ164906');
	$strFundHistory = GetNameLink('fundhistory', FUND_HISTORY_DISPLAY);
	$strLof = _getLofLink();
	$strElementary = GetNameLink('elementary', '小学生');
	$strImage = ImgMrFox();
	$strAdr = GetNameLink('adrhcompare', 'ADR');
	$strSource = GetSecondListingLink();
	$strUpdate = DebugIsAdmin() ? GetInternalLink('/php/test/updatesecondlisting.php', '更新二次回港上市数据') : '';
	
    echo <<<END
	$strHead
<p>2021年6月24日
<br />虽然原则上来说XOP也可以使用这个页面，但是它其实是为同时有港股和美股的{$strKWEB}持仓准备的。
<br />{$strQDII}基金总是越跌规模越大，流动性越好，前些年是华宝油气，而今年最热门的变成了中概互联。按SZ162411对应XOP的模式，中概互联的小弟SZ164906之前是用KWEB估值的。
不过因为有1/3的港股持仓，它的净值在港股交易时段会继续变化，所以原来的{$strSZ164906}页面其实没有什么实际用处。唯一的好处是在{$strFundHistory}中累积了几年的官方估值误差数据，帮我确认了用KWEB持仓估值的可行性。
<br />跟A股{$strLof}基金每个季度才公布一次前10大持仓不同，美股ETF每天都会公布自己的净值和详细持仓比例。因为KWEB和SZ164906跟踪同一个中证海外中国互联网指数H11136，这样可以从KWEB官网下载持仓文件后，根据它的实际持仓估算出净值。然后SZ164906的参考估值也就可以跟随白天的港股交易变动了。
<br />写了快6年的估值软件终于从{$strElementary}水平进化到了初中生水平，还是有些成就感的。暑假即将来到，了不起的狐狸爸爸要开始教已经读了一年小学的娃在Roblox上编程了。
$strImage
<br />KWEB的持仓中混合了部分二次回港上市的股票，需要单独处理一下。虽然它们跟港股在美股的{$strAdr}是不同的概念，但是在数据结构上是一致的，犹豫了一分钟后，我就把这两者合并了。
<br />数据来源：{$strSource}	{$strUpdate}
</p>
END;
}

	Echo20210624('增加'.GetNameTag('holdings', HOLDINGS_DISPLAY).'页面');
	Echo20210714('增加'.GetNameTag('fundshare', FUND_SHARE_DISPLAY).'页面');
	Echo20210728('chinainternet');
	Echo20220914('qdiimix');
	Echo20230521('qdiijp');
	Echo20230525('qdiieu');
	Echo20230530('增加全球芯片LOF和海外科技LOF的估值');
	EchoPage20240419('qqqfund');
	Echo20240606('印度基金LOF的实时估值');
	
function Echo20250316($strHead)
{
	$strHead = GetHeadElement($strHead);
	$strQdii = _getStockMenuLink('qdii');
	$strOvernightTrading = GetBlogLink(20250223);
	
    echo <<<END
	$strHead
<p>2025年3月16日
<br />在{$strQdii}中写过，实时估值用于做美油期货CL和SZ162411的对冲交易。这本身其实是一个很模糊的对冲，SZ162411和XOP一样同时受美股大盘的影响，不光是跟油价，而且天然气价格对它们的一些成分股价格也有很大的影响。
随着从去年开始的美股OVERNIGHT夜盘交易的流动性越来越好，用夜盘XOP实时对冲SZ162411显然是更好更正确的选择，因此我新写了{$strOvernightTrading}工具，然后今天减掉了华宝油气等四个原油股票基金在老的网页和公众号上的实时估值部分。
</p>
END;
}

	Echo20250316('华宝油气不再需要实时估值');
?>

</div>

<?php _LayoutBottom(); ?>

</body>
</html>
