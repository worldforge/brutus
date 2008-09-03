{assign var="lockDate" value=""}
{if $selectedId == ""}{assign var="selectedId" value="-1"}{/if}

{foreach from=$results item="row" key="index"}
	{assign var="tmpDate" value=$row.stamp|date_format}
	{if $showHeaders}
		{if $tmpDate != $lockDate && $append == ""}
			<tr>
				<td colspan="{if $showOps != false}4{else}3{/if}" class="headline">{$tmpDate} in {$row.channel}</td>
			</tr>
			{assign var="lockDate" value=$tmpDate}
		{/if}
	{/if}
	<tr id="row.{$row.id}{$subset}" class="{$append}{if $selectedId==$row.id} selected{/if}">
		<td class="time">{$row.stamp|date_format:"%H:%I:%S"}</td>
		<td class="user">{$row.user}</td>
		<td class="content">
			{if $row.content|strlen > 120 && $row.content|substr_count:" " < 8}
				<div id="disclosure{$row.id}{$subset}" class="disclosure" style="display:none;">
					<div class="close"><a href="javascript:showDisclosedItem('displayed{$row.id}');void(0);">(close)</a></div>
					<div class="item">{$row.content|word:$q}</div>
				</div>
				<div id="displayed{$row.id}{$subset}" class="displayed"><a href="javascript:showDisclosedItem('disclosure{$row.id}');void(0);" class="trans">{$row.content|truncate:120:"&hellip;":true|word:$q:false}</a></div>
			{else}
				{$row.content|word:$q}
			{/if}
		</td>
		{if $showOps != false}
		<td class="ops">
			{if $selectedId!=$row.id}
				{assign var="hideContract" value="style='display:none;'"}
				{assign var="hideExpand" value=""}
			{else}
				{assign var="hideContract" value=""}
				{assign var="hideExpand" value="style='display:none;'"}
			{/if}
			{if $append == "append"}
				{assign var="hideExpand" value="style='display:none;'"}
			{/if}
			<a href="javascript:contractMessageRows('row.{$row.id}');void(0);" id="c{$row.id}{$subset}" class="contract" {$hideContract}>Contract</a>
			<a href="javascript:expandMessageRow('row.{$row.id}');void(0);" id="e{$row.id}{$subset}" class="expand" {$hideExpand}>Expand</a>
		</td>
		{/if}
	</tr>
{/foreach}