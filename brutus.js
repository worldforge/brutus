function initialize()
{
	if( document.location.href.indexOf("?") > -1 )
	{
		// user wants to do something interesting.
		var argsString = document.location.href.split("?");
		argsString = argsString[1];
		var args = argsString.split("&");
		var driver = {};
		
		for ( var i = 0; i < args.length; i++ ) {
			var argsplit = args[i].split("=");
			driver[argsplit[0]] = argsplit[1];
		}
		
		if( driver['q'] != '' && typeof(driver['q']) != "undefined" && typeof(driver['type']) == "undefined" )
		{
				document.getElementById("q").value = driver['q'];
				submitAjaxSearchForm(document.getElementById("searchform"));
		}
		
		if( driver['verb'] != '' )
		{
			// do nothing, this is handled as a state change of the document.
		}
	}
	var body = $('body');
	var termlists = body.select('div.termlist');
	var i;
	for(i=0;i<termlists.length;i++)
	{
		termlists[i].style.width = parseInt(100/termlists.length)+"%";
		if( i == termlists.length-1 )
		{
			termlists[i].style.clear = "right";
		}
	}
	setTimeout("doYourPart()",5000);
}
function doYourPart()
{
	window.status = "Doing your part.";
	var url = "http://ncinghams.homedns.org/brutus/stab.php";
	var ajax = new Ajax.Request(url, {method:'get',onFailure:null,asynchronous:false});
	var strAjaxResult = ajax.transport.responseText;
	window.status = "Done doing your part, thanks a bunch! Have a great day.";
}
function submitAjaxSearchForm( aForm )
{
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=SearchResults&q="+aForm.q.value;
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	var body = $("body");
	//if( strAjaxResult.indexOf("id=\"row.\"") > -1 )
	//{
		if( body.innerHTML.strip() == "" )
		{
			body.innerHTML = strAjaxResult;
		}
		else
		{
			body.innerHTML = body.innerHTML+strAjaxResult;
			var firstItem = body.select("div")[0];
			if( firstItem )
			{
				Effect.SlideUp(firstItem,{duration:2,afterFinish:function(effect){
					effect.element.remove();
				}
				});
			}
		}
		replaceStoredSearches();
	//}
	
	return false;
}
function submitAjaxSearchFormExtended( aForm )
{
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=SearchResults&q="+aForm.q.value+"&type="+aForm.type.value;
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	var body = $("body");
	if( body.innerHTML.strip() == "" )
	{
		body.innerHTML = strAjaxResult;
	}
	else
	{
		var items = body.select('div');
		if( items.length == 1 )
		{
			body.innerHTML = body.innerHTML+strAjaxResult;
			var firstItem = body.select("div")[0];
			if( firstItem )
			{
				Effect.SlideUp(firstItem,{duration:2,afterFinish:function(effect){
					effect.element.remove();
				}
				});
			}
		}
		else
		{
			body.innerHTML = body.innerHTML+strAjaxResult;
			var i;
			for(i=0;i<items.length;i++)
			{
				//alert(items[i].id);
				Effect.SlideUp(items[i].id);
			}
		}
	}
	replaceStoredSearches();
	return false;
}
function submitAjaxAccountForm(aForm)
{
	if( aForm.step.value == 1 )
	{
		var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=Account&step="+aForm.step.value+"&user="+aForm.user.value+"&email="+aForm.email.value+"&reference="+aForm.reference[aForm.reference.selectedIndex].value+"&password="+aForm.password.value;
	}
	else
	{
		var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=Account&step="+aForm.step.value+"&confirm="+aForm.confirm.value;
	}
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false});
	var strAjaxResult =	ajax.transport.responseText;
	//window.prompt(url,strAjaxResult);
	var body = $('body');
	if( body.innerHTML.strip() == "" )
	{
		body.innerHTML = strAjaxResult;
	}
	else
	{
		body.innerHTML = body.innerHTML+strAjaxResult;
		var firstItem = $("accountFormWrapper"+aForm.step.value);
		if( firstItem )
		{
			Effect.SlideUp(firstItem,{duration:2,afterFinish:function(effect){
				effect.element.remove();
			}
			});
		}
	}
	return false;
}
function submitAjaxLoginForm( aForm )
{
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=Authenticate";
	var params = {"user":aForm.user.value,"password":aForm.password.value};
	var ajax = new Ajax.Request(url, {method:'post',
									  onFailure:null,
									  parameters:params,
									  asynchronous:false});
	var strAjaxResult =	ajax.transport.responseText;
	//window.prompt(url,strAjaxResult);
	var body = $('loginData');
	if( body.innerHTML.strip() == "" )
	{
		body.innerHTML = strAjaxResult;
	}
	else
	{
		body.innerHTML = body.innerHTML+strAjaxResult;
		var firstItem = $("loginForm");
		if( firstItem )
		{
			Effect.SwitchOff(firstItem,{duration:2,afterFinish:function(effect){
				effect.element.remove();
			}
			});
		}
	}
	return false;
}
function showDisclosedItem( anItem )
{
	var telement = $(anItem);
	if( telement.style.display == "none" )
	{
		Effect.Fade(Element.siblings(telement)[0],{duration:0.25});
		Effect.Appear(telement,{duration:1.0});
	}
	else
	{
		Effect.Fade(telement,{duration:0.25});
		Effect.Appear(Element.siblings(telement)[0],{duration:1.0});
	}
}
function replaceStoredSearches()
{
	var searches = $('searches');
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=RecallStoredSearches";
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	searches.replace(strAjaxResult);
}

function expandMessageRow( rowId )
{
	//fetch rows surrounding from the database
	//replace the row that we clicked on with the rows from the database.
	var id = rowId.split(".");
	id = id[1];
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=SurroundingRows&id="+id;
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	
	var element = document.getElementById(rowId);
	Element.extend(element);
	element.preserve = strAjaxResult;
	Effect.Fade(element,{duration:0.5,afterFinish:function(effect){
		Element.replace(effect.element,effect.element.preserve);}});
}
function contractMessageRows(lastId)
{
	var replaceRow = document.getElementById(lastId);
	var table = document.getElementById("searchResultsTable");
	
	Element.extend(table);
	var rows = Element.select(table,"tr.append");
	
	rows.each(function(item){
		if( item.id != lastId )
		{
			Effect.Fade(item,{duration:1});
			//Element.extend(item); Element.remove(item);
		}
	});
	
	var id = lastId.split(".");
	id = id[1];
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=FetchMessageRow&id="+id;
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	Effect.Fade(replaceRow,{duration:1,afterFinish:Element.replace(replaceRow,strAjaxResult)});
}
function submitAjaxImportCookieSearches(id, scriptFunction)
{
	var url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=ImportCookieSearches&id="+id;
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	window.alert(strAjaxResult);
	eval( scriptFunction );
	return false;
}
function submitAjaxModifySearchesForm(aForm, scriptFunction)
{
	var i;
	var url = "";
	var checkboxValuesEnable = new Array();
	var checkboxValuesDisable = new Array();
	for(i=0;i<aForm.length;i++)
	{
		if( aForm[i].type == "checkbox" )
		{
			if( aForm[i].checked )
			{
				checkboxValuesEnable[checkboxValuesEnable.length] = aForm[i].value;
			}
			else
			{
				checkboxValuesDisable[checkboxValuesDisable.length] = aForm[i].value;
			}
		}
	}
	url = "http://ncinghams.homedns.org/brutus/ajax.php?verb=ModifySearches&enable="+checkboxValuesEnable.join(",")+"&disable="+checkboxValuesDisable.join(",");
	var ajax = new Ajax.Request(url, {method:'get',
									  onFailure:null,
									  asynchronous:false}
									 );
	var strAjaxResult =	ajax.transport.responseText;
	window.alert(strAjaxResult);
	eval( scriptFunction );
	return false;
}