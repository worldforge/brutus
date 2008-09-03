<div class="results" id="searchResults{$results|@serialize|md5}">
	<table id="searchResultsTable{$subset}">
		{if $caption}
			<caption>{$caption}</caption>
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