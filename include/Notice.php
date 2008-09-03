<?php

class Notice
{
	function Notice( $title, $msg )
	{
		$this->title = $title;
		$this->message = $msg;
		$this->stamp = date('m/d/y h:i:s',time());
	}
	
	function Render()
	{
		return "<div class='notice'><h2>".$this->title."</h2><em>".$this->stamp."</em><p>".$this->message."</p></div>";
	}
}

?>