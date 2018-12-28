<?php

require_once __DIR__ . '/yf_unit_tests_setup.php';

class class_security_test extends yf\tests\wrapper
{
    public function test_xss_clean()
    {
        $this->assertEquals(_class('security')->xss_clean('script'), 'script');
        $this->assertEquals(_class('security')->xss_clean('<script'), '&lt;script');
        $this->assertEquals(_class('security')->xss_clean('<img src="javascript:alert(document.cookie)" />'), '<img  />');
        $this->assertEquals(_class('security')->xss_clean('<img src= "javascript:alert(document.cookie)" />'), '<img  />');
        $this->assertEquals(_class('security')->xss_clean('<img src=\'pic.png\' onclick=\'location.href=xxx\' onmouseover=\'location.href=xxx\' />'), '<img src=\'pic.png\'   />');
        $this->assertEquals(_class('security')->xss_clean('javascript:alert(document.cookie)'), '[removed]alert&#40;[removed]&#41;');

        // https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet

        $this->assertEquals(_class('security')->xss_clean('\'\';!--"<XSS>=&{()}'), '\'\';!--"[removed]=&{()}');
        $this->assertEquals(_class('security')->xss_clean('\';alert(String.fromCharCode(88,83,83))//\';alert(String.fromCharCode(88,83,83))//";'), '\';alert&#40;String.fromCharCode(88,83,83&#41;)//\';alert&#40;String.fromCharCode(88,83,83&#41;)//";');
        $this->assertEquals(_class('security')->xss_clean('alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//--'), 'alert&#40;String.fromCharCode(88,83,83&#41;)//";alert&#40;String.fromCharCode(88,83,83&#41;)//--');
        $this->assertEquals(_class('security')->xss_clean('></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>'), '>[removed]">\'>[removed]alert&#40;String.fromCharCode(88,83,83&#41;)[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>'), '[removed][removed]');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=javascript:alert(\'XSS\')>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=JaVaScRiPt:alert(\'XSS\')>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=javascript:alert("XSS")>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<a onmouseover="alert(document.cookie)">xxs link</a>'), '<a >xxs link</a>');
        $this->assertEquals(_class('security')->xss_clean('<a onmouseover=alert(document.cookie)>xxs link</a>'), '<a >xxs link</a>');
        $this->assertEquals(_class('security')->xss_clean('<IMG """><SCRIPT>alert("XSS")</SCRIPT>">'), '<IMG >[removed]alert&#40;"XSS"&#41;[removed]">');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=# onmouseover="alert(\'xxs\')">'), '<IMG  >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC= onmouseover="alert(\'xxs\')">'), '<IMG  >');
        $this->assertEquals(_class('security')->xss_clean('<IMG onmouseover="alert(\'xxs\')">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=/ onerror="alert(String.fromCharCode(88,83,83))">'), '<IMG  >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC="javascript:alert(\'XSS\', \'\' );">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC="jav&#x0D;ascript:alert(\'XSS\');">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=java\0script:alert(\"XSS\")>'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=" &#14;  javascript:alert(\'XSS\');">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT/XSS SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed][removed]');
        $this->assertEquals(_class('security')->xss_clean('<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert("XSS")>'), '&lt;BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert&#40;"XSS"&#41;&gt;');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT/SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed][removed]');
        $this->assertEquals(_class('security')->xss_clean('<<SCRIPT>alert("XSS");//<</SCRIPT>'), '<[removed]alert&#40;"XSS"&#41;;//<[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT SRC=http://ha.ckers.org/xss.js?< B >'), '[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT SRC=//ha.ckers.org/.j>'), '[removed]');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC="javascript:alert(\'XSS\')"'), '<IMG ');
        $this->assertEquals(_class('security')->xss_clean('<iframe src=http://ha.ckers.org/scriptlet.html <'), '&lt;iframe src=http://ha.ckers.org/scriptlet.html &lt;');
        $this->assertEquals(_class('security')->xss_clean('\";alert(\'XSS\');//'), '\";alert&#40;\'XSS\'&#41;;//');
        $this->assertEquals(_class('security')->xss_clean('</TITLE><SCRIPT>alert("XSS");</SCRIPT>'), '&lt;/TITLE&gt;[removed]alert&#40;"XSS"&#41;;[removed]');
        $this->assertEquals(_class('security')->xss_clean('<INPUT TYPE="IMAGE" SRC="javascript:alert(\'XSS\');">'), '&lt;INPUT TYPE="IMAGE" SRC="[removed]alert&#40;\'XSS\'&#41;;"&gt;');
        $this->assertEquals(_class('security')->xss_clean('<BODY BACKGROUND="javascript:alert(\'XSS\')">'), '&lt;BODY BACKGROUND="[removed]alert&#40;\'XSS\'&#41;"&gt;');
        $this->assertEquals(_class('security')->xss_clean('<IMG DYNSRC="javascript:alert(\'XSS\')">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<IMG LOWSRC="javascript:alert(\'XSS\')">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<STYLE>li {list-style-image: url("javascript:alert(\'XSS\')");}</STYLE><UL><LI>XSS</br>'), '&lt;STYLE&gt;li {list-style-image: url("[removed]alert&#40;\'XSS\'&#41;");}&lt;/STYLE&gt;&lt;UL><LI>XSS</br>');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC=\'vbscript:msgbox("XSS")\'>'), '<IMG SRC=\'[removed]msgbox("XSS")\'>');
        $this->assertEquals(_class('security')->xss_clean('<IMG SRC="livescript:[code]">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('<BODY ONLOAD=alert(\'XSS\')>'), '&lt;BODY &gt;');
        $this->assertEquals(_class('security')->xss_clean('<BGSOUND SRC="javascript:alert(\'XSS\');">'), '&lt;BGSOUND SRC="[removed]alert&#40;\'XSS\'&#41;;"&gt;');
        $this->assertEquals(_class('security')->xss_clean('<BR SIZE="&{alert(\'XSS\')}">'), '<BR SIZE="&{alert&#40;\'XSS\'&#41;}">');
        $this->assertEquals(_class('security')->xss_clean('<LINK REL="stylesheet" HREF="javascript:alert(\'XSS\');">'), '&lt;LINK REL="stylesheet" HREF="[removed]alert&#40;\'XSS\'&#41;;"&gt;');
        $this->assertEquals(_class('security')->xss_clean('<LINK REL="stylesheet" HREF="http://ha.ckers.org/xss.css">'), '&lt;LINK REL="stylesheet" HREF="http://ha.ckers.org/xss.css"&gt;');
        $this->assertEquals(_class('security')->xss_clean('<STYLE>@import\'http://ha.ckers.org/xss.css\';</STYLE>'), '&lt;STYLE&gt;@import\'http://ha.ckers.org/xss.css\';&lt;/STYLE&gt;');
        $this->assertEquals(_class('security')->xss_clean('<META HTTP-EQUIV="Link" Content="<http://ha.ckers.org/xss.css>; REL=stylesheet">'), '&lt;META HTTP-EQUIV="Link" C&gt;');
        $this->assertEquals(_class('security')->xss_clean('<STYLE>BODY{-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")}</STYLE>'), '&lt;STYLE&gt;BODY{[removed]:url("http://ha.ckers.org/xssmoz.xml#xss")}&lt;/STYLE&gt;');
        $this->assertEquals(_class('security')->xss_clean('<STYLE>@im\port\'\ja\vasc\ript:alert("XSS")\';</STYLE>'), '&lt;STYLE&gt;@im\port\'\ja\vasc\ript:alert&#40;"XSS"&#41;\';&lt;/STYLE&gt;');
        $this->assertEquals(_class('security')->xss_clean('<IMG STYLE="xss:expr/*XSS*/ession(alert(\'XSS\'))">'), '<IMG >');
        $this->assertEquals(_class('security')->xss_clean('exp/*<A STYLE=\'no\xss:noxss("*//*");'), 'exp/*<A ');
        $this->assertEquals(_class('security')->xss_clean('xss:ex/*XSS*//*/*/pression(alert("XSS"))\'>'), 'xss:ex/*XSS*//*/*/pression(alert&#40;"XSS"&#41;)\'>');
        $this->assertEquals(_class('security')->xss_clean('<STYLE TYPE="text/javascript">alert(\'XSS\');</STYLE>'), '&lt;STYLE TYPE="text/javascript"&gt;alert&#40;\'XSS\'&#41;;&lt;/STYLE&gt;');
        $this->assertEquals(_class('security')->xss_clean('<STYLE>.XSS{background-image:url("javascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>'), '&lt;STYLE&gt;.XSS{background-image:url("[removed]alert&#40;\'XSS\'&#41;");}&lt;/STYLE&gt;&lt;A ></A>');
        $this->assertEquals(_class('security')->xss_clean('<STYLE type="text/css">BODY{background:url("javascript:alert(\'XSS\')")}</STYLE>'), '&lt;STYLE type="text/css"&gt;BODY{background:url("[removed]alert&#40;\'XSS\'&#41;")}&lt;/STYLE&gt;');
        $this->assertEquals(_class('security')->xss_clean('<XSS STYLE="xss:expression(alert(\'XSS\'))">'), '[removed]');
        $this->assertEquals(_class('security')->xss_clean('<XSS STYLE="behavior: url(xss.htc);">'), '[removed]');
        $this->assertEquals(_class('security')->xss_clean('¼script¾alert(¢XSS¢)¼/script¾'), '¼script¾alert&#40;¢XSS¢&#41;¼/script¾');
        $this->assertEquals(_class('security')->xss_clean('<META HTTP-EQUIV="refresh" CONTENT="0;url=javascript:alert(\'XSS\');">'), '&lt;META HTTP-EQUIV="refresh" C&gt;');
        $this->assertEquals(_class('security')->xss_clean('<META HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K">'), '&lt;META HTTP-EQUIV="refresh" C&gt;');
        $this->assertEquals(_class('security')->xss_clean('<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">'), '&lt;META HTTP-EQUIV="refresh" C&gt;');
        $this->assertEquals(_class('security')->xss_clean('<IFRAME SRC="javascript:alert(\'XSS\');"></IFRAME>'), '&lt;IFRAME SRC="[removed]alert&#40;\'XSS\'&#41;;"&gt;&lt;/IFRAME>');
        $this->assertEquals(_class('security')->xss_clean('<IFRAME SRC=# onmouseover="alert(document.cookie)"></IFRAME>'), '&lt;IFRAME SRC=# &gt;&lt;/IFRAME>');
        $this->assertEquals(_class('security')->xss_clean('<FRAMESET><FRAME SRC="javascript:alert(\'XSS\');"></FRAMESET>'), '&lt;FRAMESET&gt;&lt;FRAME SRC="[removed]alert&#40;\'XSS\'&#41;;">&lt;/FRAMESET&gt;');
        $this->assertEquals(_class('security')->xss_clean('<TABLE BACKGROUND="javascript:alert(\'XSS\')">'), '<TABLE BACKGROUND="[removed]alert&#40;\'XSS\'&#41;">');
        $this->assertEquals(_class('security')->xss_clean('<TABLE><TD BACKGROUND="javascript:alert(\'XSS\')">'), '<TABLE><TD BACKGROUND="[removed]alert&#40;\'XSS\'&#41;">');
        $this->assertEquals(_class('security')->xss_clean('<DIV STYLE="background-image: url(javascript:alert(\'XSS\'))">'), '<DIV >');
        $this->assertEquals(_class('security')->xss_clean('<DIV STYLE="background-image:\0075\0072\006C\0028\'\006a\0061\0076\0061\0073\0063\0072\0069\0070\0074\003a\0061\006c\0065\0072\0074\0028.1027\0058.1053\0053\0027\0029\'\0029">'), '<DIV >');
        $this->assertEquals(_class('security')->xss_clean('<DIV STYLE="background-image: url(&#1;javascript:alert(\'XSS\'))">'), '<DIV >');
        $this->assertEquals(_class('security')->xss_clean('<DIV STYLE="width: expression(alert(\'XSS\'));">'), '<DIV >');
        $this->assertEquals(_class('security')->xss_clean('<!--[if gte IE 4]> <SCRIPT>alert(\'XSS\');</SCRIPT> <![endif]-->'), '&lt;!--[if gte IE 4]> [removed]alert&#40;\'XSS\'&#41;;[removed] <![endif]--&gt;');
        $this->assertEquals(_class('security')->xss_clean('<BASE HREF="javascript:alert(\'XSS\');//">'), '&lt;BASE HREF="[removed]alert&#40;\'XSS\'&#41;;//"&gt;');
        $this->assertEquals(_class('security')->xss_clean('<OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>'), '&lt;OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"&gt;&lt;/OBJECT>');
        $this->assertEquals(_class('security')->xss_clean('EMBED SRC="http://ha.ckers.If you add the attributes allowScriptAccess="never" and allownetworking="internal" it can mitigate this risk.:org/xss.swf" AllowScriptAccess="always"></EMBED>'), 'EMBED SRC="http://ha.ckers.If you add the attributes allowScriptAccess="never" and allownetworking="internal" it can mitigate this risk.:org/xss.swf" AllowScriptAccess="always">&lt;/EMBED&gt;');
        $this->assertEquals(_class('security')->xss_clean('<EMBED SRC="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==" type="image/svg+xml" AllowScriptAccess="always"></EMBED>'), '&lt;EMBED SRC=[removed]PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==" type="image/svg+xml" AllowScriptAccess="always"&gt;&lt;/EMBED>');
        $this->assertEquals(_class('security')->xss_clean('<XML ID="xss"><I><B><IMG SRC="javas<!-- -->cript:alert(\'XSS\')"></B></I></XML><SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>'), '&lt;XML ID="xss"&gt;&lt;I><B><IMG ></B></I>&lt;/XML&gt;&lt;SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>');
        $this->assertEquals(_class('security')->xss_clean('<XML SRC="xsstest.xml" ID=I></XML><SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>'), '&lt;XML SRC="xsstest.xml" ID=I&gt;&lt;/XML><SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT SRC="http://ha.ckers.org/xss.jpg"></SCRIPT>'), '[removed][removed]');
        $this->assertEquals(_class('security')->xss_clean('<HTML><BODY><?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time"><?import namespace="t" implementation="#default#time2"><t:set attributeName="innerHTML" to="XSS<SCRIPT DEFER>alert("XSS")</SCRIPT>"></BODY></HTML>'), '&lt;HTML&gt;&lt;BODY>&lt ?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time">&lt;?import namespace="t" implementati><t:set attributeName="innerHTML" to="XSS[removed]alert&#40;"XSS"&#41;[removed]">&lt;/BODY&gt;&lt;/HTML>');
        $this->assertEquals(_class('security')->xss_clean('<!--#exec cmd="/bin/echo \'<SCR\'"--><!--#exec cmd="/bin/echo \'IPT SRC=http://ha.ckers.org/xss.js></SCRIPT>\'"-->'), '&lt;!--#exec cmd="/bin/echo \'&lt;SCR\'"--&gt;&lt;!--#exec cmd="/bin/echo \'IPT SRC=http://ha.ckers.org/xss.js&gt;&lt;/SCRIPT&gt;\'"--&gt;');
        $this->assertEquals(_class('security')->xss_clean('<? echo(\'<SCR)\';echo(\'IPT>alert("XSS")</SCRIPT>\'); ?>'), '&lt;? echo(\'<SCR)\';echo(\'IPT>alert&#40;"XSS"&#41;[removed]\'); ?&gt;');
        $this->assertEquals(_class('security')->xss_clean('<META HTTP-EQUIV="Set-Cookie" Content="USERID=<SCRIPT>alert(\'XSS\')</SCRIPT>">'), '&lt;META HTTP-EQUIV="Set-Cookie" C&gt;');
        $this->assertEquals(_class('security')->xss_clean('<HEAD><META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=UTF-7"> </HEAD>+ADw-SCRIPT+AD4-alert(\'XSS\');+ADw-/SCRIPT+AD4-'), '&lt;HEAD&gt;&lt;META HTTP-EQUIV="CONTENT-TYPE" C> &lt;/HEAD&gt;+ADw-SCRIPT+AD4-alert&#40;\'XSS\'&#41;;+ADw-/SCRIPT+AD4-');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT a=">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed]" SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT =">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed]" SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('SCRIPT a=">" \'\' SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), 'SCRIPT a="&gt;" \'\' SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT "a=\'>\'" SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed]\'" SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT a=`>` SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed]` SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT a=">\'>" SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed]\'>" SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('<SCRIPT>document.write("<SCRI");</SCRIPT>PT SRC="http://ha.ckers.org/xss.js"></SCRIPT>'), '[removed][removed]("<SCRI");[removed]PT SRC="http://ha.ckers.org/xss.js">[removed]');
        $this->assertEquals(_class('security')->xss_clean('<A HREF="javascript:document.location=\'http://www.google.com/\'">XSS</A>'), '<A locati>XSS</A>');
    }
}
