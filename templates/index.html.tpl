<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$appTitle} - {$verb}</title>
	<meta name="generator" content="BBEdit 8.7" />
	<meta name="robots" content="noindex, nofollow">
	<link rel="stylesheet" type="text/css" href="/{$basedir}css/master.css" />
	<script type="text/javascript" src="/{$basedir}scriptaculous/prototype.js"></script>
	<script type="text/javascript" src="/{$basedir}scriptaculous/scriptaculous.js"></script>
	<script type="text/javascript" src="/{$basedir}brutus.js"></script>
	<script type="text/javascript">
	var global_url_prefix = "{$globals.AJAXPREFIX}";
	</script>
</head>
<body onload="initialize();">
<div id="root">
	<div id="header">
		<div class="headerlabel">
		<h1><a href="/{$basedir}">brutus.worldforge.org</a></h1>
		<em>better than a cooked chicken</em>
		</div>
		<div class="headerform">
			<form id="searchform" action="/{$basedir}index.php" method="get" onsubmit="return submitAjaxSearchForm(this);">
				<div class="input">
					<input type="text" name="q" id="q" value="{if $q != ""}{$q}{else}Search{/if}" {literal}onfocus="if(this.value=='Search'){this.value='';$('lower').value='';$('upper').value='';}" onblur="if(this.value==''){this.value='Search';}"{/literal} /><br />
					<input type="hidden" name="type" id="type" value="{$type}" />
					<input type="hidden" name="lower" id="lower" value="{$lower}" />
					<input type="hidden" name="upper" id="upper" value="{$upper}" />
				</div>
			</form>
			{include file="storedsearches.html.tpl"}
		</div>
	</div>
	<div id="menu">
		<ul>
			{foreach from=$menu item="menuItem" key="key"}
				{if $menuItem.ifAuth}
					{if $smarty.session.id != "" and
						$smarty.session.user != ""}
						<li><a href="/{$basedir}index.php?verb={$menuItem.verb}" title="{$key}">{$menuItem.label}</a></li>
					{/if}
				{elseif $menuItem.ifNotAuth}
					{if $smarty.session.id == "" and
						$smarty.session.user == ""}
					<li><a href="/{$basedir}index.php?verb={$menuItem.verb}" title="{$key}">{$menuItem.label}</a></li>
					{/if}
				{else}
					<li><a href="/{$basedir}index.php?verb={$menuItem.verb}" title="{$key}">{$menuItem.label}</a></li>
				{/if}
			{/foreach}
			<li>
				<div id="loginData">
					<div id="loginForm">
						{if $session_user.user != ""}
							<div>Welcome back {$session_user.user}</div>
						{else}
							<form style="opacity:1.0;" method="post" action="{$basedir}/index.php?verb=Authenticate" onsubmit="return submitAjaxLoginForm(this);">
								<input type="text" name="user" value="Nickname" onfocus="if(this.value=='Nickname')this.value='';" onblur="if(this.value=='')this.value='Nickname';" />
								<input type="password" name="password" value="Nickname" onfocus="if(this.value=='Nickname')this.value='';" onblur="if(this.value=='')this.value='Nickname';" />
								<input type="submit" class="submit" value="Go" />
							</form>
						{/if}
					</div>
				</div>
			</li>
		</ul>
	</div>
	<div id="body">
		{if $body|@sizeOf > 0 or
			$notices|@sizeOf > 0}
			{if $body|@sizeOf > 0}
				{foreach from=$body item="bodyItem"}
					{$bodyItem}
				{/foreach}
			{/if}
			{if $notices|@sizeOf > 0}
				<div class="notices">
					<h1>Notices</h1>
					{foreach from=$notices item="notice"}
						{$notice->Render()}
					{/foreach}	
				</div>
			{/if}
		{else}
			<div id="static">
			<h1>Welcome to Brutus!</h1>
			
			<p>
				Brutus is a viewer for the WorldForge IRC chat logs.
			</p>
			
			<h2>What is an Account?</h2>
			
			<p>
				You don't need an account to search!<br />
				<br />
				As you participate in IRC discussions, your nickname tags who you are.<br />
				As you use Brutus, you'll want to keep track of searches you perform and also have the ability to tag discussions with more insightful information.<br />
				Accounts make this possible by linking who you are here, with who you are there.
			</p>
			
			<h2>Can I search for Anything?</h2>
			
			<p>
				All logs will be parsed.<br />
				<br />
				Seriously though, you might not want to try doing a search on the letter 'a' just yet.
			</p>
			</div>
		{/if}
	</div>
	<div id="footer">
	
	</div>
</div>
</body>
</html>
