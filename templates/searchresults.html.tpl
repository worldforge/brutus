<div class="results" id="searchResults{$results|@serialize|md5}">
	<table id="searchResultsTable{$subset}">
		{if $caption or $showDateControls}
			<caption>
				{if $showDateControls}
					{math equation="x-y" x=$lower y=1296000 assign="backward"}
					{math equation="x-y" x=$upper y=1296000 assign="forward"}
					<a href="javascript:$('lower').value='{$backward|date_format:"%Y-%m-%d"}';$('upper').value='{$forward|date_format:"%Y-%m-%d"}';submitAjaxSearchFormExtended($('searchform'));void(0);">&lt;-</a>
				{/if}
				{$caption}
				{if $showDateControls}
					{math equation="x+y" x=$lower y=1296000 assign="backward"}
					{math equation="x+y" x=$upper y=1296000 assign="forward"}
					<a href="javascript:$('lower').value='{$backward|date_format:"%Y-%m-%d"}';$('upper').value='{$forward|date_format:"%Y-%m-%d"}';submitAjaxSearchFormExtended($('searchform'));void(0);">-&gt;</a>
				{/if}
			</caption>
		{/if}
		<tr>
			<th>Date</th>
			<th>User</th>
			<th>Message</th>
			{if $showOps != false}<th>Operations</th>{/if}
		</tr>
		{include file="searchresultsrows.html.tpl"}
	</table>
</div>