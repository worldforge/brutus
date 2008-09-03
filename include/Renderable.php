<?php

require_once("libs/Smarty.class.php");
require_once("Notice.php");

class Renderable
{
	var $smarty;
	var $template;
	
	function Renderable( $template )
	{
		$this->template = $template;
		
		$this->smarty = new Smarty();
 		$this->smarty->template_dir = getcwd().'/templates';
		$this->smarty->compile_dir = getcwd().'/templates_c';
		$this->smarty->cache_dir = getcwd().'/cache';
		$this->smarty->config_dir = getcwd().'/configs';
		$this->smarty->register_modifier("word","smarty_modifier_word");
		$this->smarty->assign("basedir",strToLower(basename(getcwd()))."/");
	}
	
	function Render()
	{
		$data = $this->smarty->fetch($this->template);
		return $data;
	}

	function RenderAuthRequired( $params )
	{
		$renderable = new Renderable(getcwd()."/templates/authrequired.html.tpl");
		$renderable->smarty->assign("params",$params);
		return $renderable->Render();
	}

}

function smarty_modifier_word( $text, $word="", $links=true )
{
	$array = explode(" ",$text);
	if($word == "")
	{
		$word = "123456789!@#$%^&*()";
	}
	for($i=0;$i<sizeOf($array);$i++)
	{
		if( $links )
		{
			$array[$i] = preg_replace('/(\w+:\/\/)(\S+)/',' <a href="\\1\\2" target="_blank">\\1 \\2</a>', $array[$i]);
			if( stristr($array[$i],"target=\"_blank\">") )
			{
				$array[$i] = str_replace("+"," ",$array[$i]);
				$array[$i] = str_replace("%20"," ",$array[$i]);
			}
		}
		
		if( stristr(strtolower($array[$i]),strtolower($word)) )
		{
			$array[$i] = "<em>".$array[$i]."</em>";
		}
	}
	$result = implode(" ",$array);
	return $result;
}

?>