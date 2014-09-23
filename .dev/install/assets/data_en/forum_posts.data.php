<?php
return array (
  1 => 
  array (
    'id' => '1',
    'parent' => '0',
    'forum' => '1',
    'topic' => '1',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207143466',
    'status' => 'a',
    'subject' => 'postfix',
    'text' => '[B] I have noticed this problem, and this time I am able to reproduce it: I
 have postfix 2.2.10 installed, and after I rsync the repository, I am
 still unable to update the postfix via urpmi. What is the problem?
 
 root@firewall main]# urpmi.update -a && urpmi --auto-select
 examining MD5SUM file
 examining synthesis file [/var/lib/urpmi/synthesis.hdlist.annvix.cz]
 copying source hdlist (or synthesis) of "ports"...
 ...copying done
 examining synthesis file [/var/lib/urpmi/synthesis.hdlist.ports.cz]
 The package(s) are already installed
 
 
 root@firewall main]# ls -l postfix-2.2.11-6178avx.i586.rpm
 -rw-r--r--  1 ying users 3633814 Oct  9 17:25
 postfix-2.2.11-6178avx.i586.rpm
 [root@firewall main]# rpm -qa | grep postfix
 postfix-2.2.10-5348avx

Hmmm... that\'s odd.  The hdlist is definitely updated:

vdanen@build ~ $ packdrake /work/annvix/releases/1.2-RELEASE/i586/main/media_info/hdlist.cz|grep postfix
f        57172 postfix-2.2.11-6178avx.i586

I\'m not sure why you\'re not getting it.  What happens if you urpmi the
file directly?  Does urpmi still tell you it\'s installed?

-- 
{FEE30AD4 : 7F6C A60C 06C2 4811 FA1C  A2BC 2EBC 5E32 FEE3 0AD4}
mysql> SELECT * FROM users WHERE clue > 0;
Empty set (0.00sec)[/B]',
    'new_topic' => '1',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  2 => 
  array (
    'id' => '2',
    'parent' => '0',
    'forum' => '1',
    'topic' => '1',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207143623',
    'status' => 'a',
    'subject' => 'Re:postfix',
    'text' => '[I]I\'m not sure why you\'re not getting it.  What happens if you urpmi the
 file directly?  Does urpmi still tell you it\'s installed?
 
actually, did it think that it is a new version? just like kernel, it
won\'t just \'upgrade\' itself? since i totally changed the version number?
If this is the case, I need to add Obsolete or something in the rpm spec
file?[/I]',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  3 => 
  array (
    'id' => '3',
    'parent' => '0',
    'forum' => '1',
    'topic' => '1',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207143721',
    'status' => 'a',
    'subject' => 'Re:postfix',
    'text' => '[B]Hmmm... that\'s odd.  The hdlist is definitely updated:
 
 vdanen@build ~ $ packdrake /work/annvix/releases/1.2-RELEASE/i586/main/media_info/hdlist.cz|grep postfix
 f        57172 postfix-2.2.11-6178avx.i586


yes, i have that too..

 I\'m not sure why you\'re not getting it.  What happens if you urpmi the
 file directly?  Does urpmi still tell you it\'s installed?
 
this is the funny part. first time i do urpmi postfix, it returns me
postfix is installed. then second time i type urpmi postfix, it install
it! (and uninstall the previous version.)[/B]',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  4 => 
  array (
    'id' => '4',
    'parent' => '0',
    'forum' => '1',
    'topic' => '2',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207143892',
    'status' => 'a',
    'subject' => 'linux',
    'text' => 'Nearly 2 weeks trying to resolve problems with ATI 2400 HD+ LCD 22" (VdeoSeven). 
Bored ... Fed up ... 
I am new in Linux world. But to be honest, I am really disappointed and going to drop down et go back to Windows (Sorry for insult ... Windows).   

What will it be when I will have to manage and/or develop ... OpenGL ... Apache ... and co ??',
    'new_topic' => '1',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  5 => 
  array (
    'id' => '5',
    'parent' => '0',
    'forum' => '1',
    'topic' => '2',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207143960',
    'status' => 'a',
    'subject' => 'Re:linux',
    'text' => '[U]Your problem is not linux but ATI. when they start to care like Nvidia does then the Ati graphics problems will be much better. I use and have used Nvidia since many year ago. I have a laptop with ati and it sucks but what can you do but do the best. so don\'t blame linux for the hardware mfgr shortcomings.
_________________
To many whinners not enough time. 
You can only teach those willing to learn. 
If you don\'t learn something today; Then the day is wasted.[/U]',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  6 => 
  array (
    'id' => '6',
    'parent' => '0',
    'forum' => '1',
    'topic' => '2',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144029',
    'status' => 'a',
    'subject' => 'Re:linux',
    'text' => '[I][B]If you want to deal with Linux with not issues as far Video Cards. nVidia is the way to go. Always work even with nVidia Quadro NVS 285 on my Dell Precision 490. Even works way better in Linux than does on MS. I can stretch a movie over 2 monitors on wide screen view. MS fails to do it. 

Get a nVidia and give it another shot, you will see the difference. 

Like the previous guy said. ATI is not a good supporter of Linux and nVidia is. And you will be wondering, why works best in MS... the answer is very simple. ATI builds drivers for MS, MS does not build drivers. 

On this case is not Linux fault, also is not Linux problem neither, is ATI\'s issue. Nobody calls MS when the Video Adapter does not works. You can contact them and tell them that their card does not works in Linux. And post back what they say. 

By the way, I am, not starting a flame war here, so do not feel offended 
_________________[/B][/I]',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  7 => 
  array (
    'id' => '7',
    'parent' => '0',
    'forum' => '1',
    'topic' => '2',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144116',
    'status' => 'a',
    'subject' => 'Re:linux',
    'text' => 'Hello RJ549 and all of you, 
First, Thanks. 
THen ... 
I installed with Ubuntu to install the ATIxxx.run from AMD-ATI web site (No 64 bits driver as I know). 
Nothing better. The propriatary driver is "refused". Of course Catalyst Center doesn\'t run because it is not the good driver. 
So, I installed Mandrva One. Nothing better. 
At the end I\'ve just installed Mandriva PowerPack bits. 
I tried to choose the propriatary Driver in the list (HD 2xxx ). 
I didn\'t work. After lots of tests I\'ve choosen the resolution 1280x1024 with the propriatary driver and .... Catalyst runned. 
The resolution which are offed are really poor. 
I also had acces to 3D Desktop. I tested the different 3D Desktop options but I didn\'t have the Cube. 
One last thing. When I reboot I have to redo the config.',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  8 => 
  array (
    'id' => '8',
    'parent' => '0',
    'forum' => '1',
    'topic' => '2',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144294',
    'status' => 'a',
    'subject' => 'Re:linux',
    'text' => 'The proprietary driver in stock 2008 for the HD 2xxx cards is the very first release made for those cards, 8.41. You may well be better off using the backports version of 8.44, now. See this post on my blog: 

[URL=http://www.happyassassin.net/2007/12/27/a-mandriva-christmas-present-latest-nvidia-and-ati-drivers-for-cooker-2008-and-2007-spring/]to webpage[/URL]
_________________
Adam Williamson | Editor, Mandriva Community Newsletter | Mandriva Club Monkey | PR Monkey | Mandriva Bugmaster | Packager | General dogsbody | awilliamson A T mandriva D 0 T com',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  9 => 
  array (
    'id' => '9',
    'parent' => '0',
    'forum' => '1',
    'topic' => '3',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144474',
    'status' => 'a',
    'subject' => 'windows',
    'text' => 'Hi, when Windows Explorer (file mngr) is open and I click onto any avi file everything freezes. This isn',
    'new_topic' => '1',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  10 => 
  array (
    'id' => '10',
    'parent' => '0',
    'forum' => '1',
    'topic' => '3',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144512',
    'status' => 'a',
    'subject' => 'Re:windows',
    'text' => '[B]The video preview utility in explorer has never been particularly good. I wish Microsoft would just toss it away. Here',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  11 => 
  array (
    'id' => '11',
    'parent' => '0',
    'forum' => '1',
    'topic' => '3',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144560',
    'status' => 'a',
    'subject' => 'Re:windows',
    'text' => 'Hi anonymous, many tks, I found it and here',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  12 => 
  array (
    'id' => '12',
    'parent' => '0',
    'forum' => '1',
    'topic' => '3',
    'user_id' => '1',
    'user_name' => 'test',
    'created' => '1207144596',
    'status' => 'a',
    'subject' => 'Re:windows',
    'text' => 'shmediaoff.bat that has this line in it: 
REGSVR32 /U SHMEDIA.DLL 

and then shmediaon.bat (to turn preview for files that works) back on: 
REGSVR32 SHMEDIA.DLL',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  13 => 
  array (
    'id' => '13',
    'parent' => '0',
    'forum' => '2',
    'topic' => '4',
    'user_id' => '4',
    'user_name' => 'ser',
    'created' => '1207832295',
    'status' => 'a',
    'subject' => 'common problems',
    'text' => 'I am using Eclipse 3.3 and oXygen 8.2 under Linux, and find when I am editing an XSL file with the editor window maximized (under the oXygen XML perspective) if I pause in the middle of an element for more than a second the "Problems" window pops up with an error, stealing focus from the editor. I don\'t mind the realtime syntax checking (in fact I like it) if it didn\'t keep popping that window up and stealing focus. 

I\'ve never had any problems using the standalone version (8.1 or 8.2) and this is my first attempt at using the eclipse plugin, although co-workers (using earlier versions of eclipse on windows) assured me that does not happen on theirs. 

-Thanks!',
    'new_topic' => '1',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  14 => 
  array (
    'id' => '14',
    'parent' => '0',
    'forum' => '2',
    'topic' => '4',
    'user_id' => '5',
    'user_name' => 'odlman',
    'created' => '1207832661',
    'status' => 'a',
    'subject' => 'Re:common problems',
    'text' => 'Hello, 

I cannot reproduce the problem. I tried on a SUSE Linux with the oXygen plugin build number 2007062505 in Eclipse 3.3 build number I20070625-1500. What is your window manager? 


Regards',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  15 => 
  array (
    'id' => '15',
    'parent' => '0',
    'forum' => '2',
    'topic' => '4',
    'user_id' => '6',
    'user_name' => 'kingargyle',
    'created' => '1207832929',
    'status' => 'a',
    'subject' => 'Re:common problems',
    'text' => 'I\'m experiencing the same issue under Eclipse 3.3 and Oxygen 8.2, under Windows. It is most noticeable when the Editor is maximized, and there are errors in what ever you are editing. I\'ve experience it in the XML, WSDL, XQuery, and XSL editors.',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  16 => 
  array (
    'id' => '16',
    'parent' => '0',
    'forum' => '2',
    'topic' => '4',
    'user_id' => '6',
    'user_name' => 'kingargyle',
    'created' => '1207832985',
    'status' => 'a',
    'subject' => 'Re:common problems',
    'text' => 'I can reproduce it too with the editor maximized. It seems to be a bug in the Eclipse 3.3 platform. In previous versions of Eclipse the focus does not go to the Problems view when a marker is added to this view. We will see if we can find a workaround. You can avoid the problem by disabling "Validate as you type" in Window -> Preferences -> oXygen -> Editor -> Document Checking. 


Regards',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  17 => 
  array (
    'id' => '17',
    'parent' => '0',
    'forum' => '2',
    'topic' => '4',
    'user_id' => '4',
    'user_name' => 'ser',
    'created' => '1207833178',
    'status' => 'a',
    'subject' => 'Re:common problems',
    'text' => 'Ok, I was coming back to answer your original question (I was running under KDE (kwm) but wanted to test to make sure it wasn\'t that so switched over to Gnome with the same result) when I saw your reply below. 

I had found turning the delay up to 5 seconds allowed me to keep the validation but gave me enough time to keep everything valid.. but was still pretty annoying. For now I think I will stick to standalone since I use IntelliJ primarily anyway. 

Thanks!',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  18 => 
  array (
    'id' => '18',
    'parent' => '0',
    'forum' => '2',
    'topic' => '5',
    'user_id' => '6',
    'user_name' => 'kingargyle',
    'created' => '1207833408',
    'status' => 'a',
    'subject' => 'Feature Request',
    'text' => 'AFAICT Oxygen 9.1 does not implement the subject attribute. Current implementation highlights the context node and while rules can be rewritten to use a different context node, for performance and clarity reasons using subject attribute would be useful. 

Example from a (simplified) DITA rule:

[B]<pattern>
  <rule context="topic"
        subject="body/p">
    <report test="not(shortdesc | abstract) and
                  count(body/*) = 1 and
                  body/p">
      In cases where a topic contains only one paragraph, then it is preferable to include this text in the shortdesc element and leave the topic body empty.
    </report>
  </rule>
</pattern>[/B]',
    'new_topic' => '1',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  19 => 
  array (
    'id' => '19',
    'parent' => '0',
    'forum' => '2',
    'topic' => '5',
    'user_id' => '4',
    'user_name' => 'ser',
    'created' => '1207833500',
    'status' => 'a',
    'subject' => 'Re:Feature Request',
    'text' => 'Hello,

Yes, the subject attribute is useful for creating a more precise context in an ISO Schematron rule but this attribute is not implemented yet. However I added your request to our issue tracker for a future version of oXygen.


Thank you',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  20 => 
  array (
    'id' => '20',
    'parent' => '0',
    'forum' => '2',
    'topic' => '6',
    'user_id' => '7',
    'user_name' => 'xchaotic',
    'created' => '1207837176',
    'status' => 'a',
    'subject' => 'XSL debugging in Oxygen tutorial',
    'text' => 'Hi.

I often struggle with XSL transformations, often not written by me and I usually spend most of the time pinpointing where the problem is.

I feel I am not taking full advantage of the XSL debugging capabilities found in Oxygen. In fact I rarely switch to debug view.

Is there a resource somewhere on the web, other than chapters 10 and 11 in the user guide that teaches how to effectively use debugging capabilities in Oxygen, or debugging XSL in general?

Thanks in advance for any pointers.

So far I have found these to be useful:


[URL=http://www.dpawson.co.uk/xsl/sect2/N2126.html]www.dpawson.co.uk/xsl/sect2/N2126.html[/URL]
[URL=http://oxygenxml.com/demo/profiling/XSLT_profiling.html]www.oxygenxml.com/demo/profiling/XSLT_profiling.html[/URL]
[URL=http://oxygenxml.com/demo/XslRefactoring/XSLRefactoring.html]oxygenxml.com/demo/XslRefactoring/XSLRefactoring.html[/URL]
[URL=http://oxygenxml.com/demo/XslEditing/XSLEditing.html]oxygenxml.com/demo/XslEditing/XSLEditing.html[/URL]',
    'new_topic' => '1',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
  21 => 
  array (
    'id' => '21',
    'parent' => '0',
    'forum' => '2',
    'topic' => '6',
    'user_id' => '5',
    'user_name' => 'odlman',
    'created' => '1207837409',
    'status' => 'a',
    'subject' => 'Re:XSL debugging in Oxygen tutorial',
    'text' => 'Hello,

We do not have a general XSLT debugging tutorial but we will consider adding such a tutorial to the list of demo videos. You should place some breakpoints where you want to stop the execution. When the execution stops at a breakpoint you should watch some variables in the Variables view or some XPath expressions in the XWatch view. The values are refreshed when you press the buttons Step Into, Step Over, Step Out, etc. The other views (Trace, Stack, Templates, etc) may help you too.


Regards',
    'new_topic' => '0',
    'edit_name' => '',
    'edit_time' => '0',
    'show_edit_by' => '0',
    'use_sig' => '1',
    'use_emo' => '1',
    'icon_id' => '0',
    'poster_ip' => '192.168.1.25',
    'language' => '0',
    'activity' => '0',
  ),
);