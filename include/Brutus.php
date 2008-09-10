<?php

if( !class_exists("Brutus") )
{

session_start();

require_once("brutus.config.php");
require_once("Database.php");
require_once("libs/Smarty.class.php");
require_once("Notice.php");
require_once("Renderable.php");

/**
 * Brutus is the mate of Brenda. He likes to keep things organized. She is a scatterbrain.
 * @category   Net
 * @package    com.coalmarch.Brutus
 * @author     Original Author <tingham@coalmarch.com>
 * @copyright  Granted to Worldforge via Attribution (Seek Life Elsewhere Grant)
 * @license    GNU GPLv2+
 * @version    Release: 1.0
 * @link       http://www.coalmarch.com/products/widgets/brutus-irc-log-parser.php
 * @since      Class available since Release 1.0
 */
class Brutus
{
	var $database;
	var $renderable;
	var $notices;
	var $body;
	
 	/**
 	 * @function Brutus:Brutus
 	 * Main constructor.
 	 * @author tingham
 	 * @created 8/26/08 9:51 PM
 	 **/
 	function Brutus( $params="" )
 	{
 		if( is_array($params) )
 		{
 			// match any submitted parameters to instance variables.
 			foreach($params as $key=>$value)
 			{
 				$this->{$key} = $value;
 			}
 		}
 		if( $this->rootLogDirectoryPath == "" )
 		{
 			$this->rootLogDirectoryPath = "data/";
 		}
 		if( $this->scratchDirectoryPath == "" )
 		{
 			$this->scratchDirectoryPath = "scratch/";
 		}
 		if( $this->maxSearchResultsLimit == "" )
 		{
 			$this->maxSearchResultsLimit = 10;
 		}
 		if( $this->maxExecutionLimit == "" )
 		{
 			$this->maxExecutionLimit = 25;
 		}
 		if( $this->basedir == "" )
 		{
 			$this->basedir = "brutus/";
 		}
 		
 		$this->database = new Database($this->scratchDirectoryPath."db.sqlite");
 		$this->database->maxExecutionLimit = $this->maxExecutionLimit;
 		
 		$this->renderable = new Smarty();
 		$this->renderable->template_dir = getcwd().'/templates';
		$this->renderable->compile_dir = getcwd().'/templates_c';
		$this->renderable->cache_dir = getcwd().'/cache';
		$this->renderable->config_dir = getcwd().'/configs';
		$this->renderable->assign("basedir",$this->basedir);
		
		
		$verbs = array(
			"browse the logs" => array("verb"=>"BrowseTable","label"=>"Browse"),
			"create a new account"=> array("verb"=>"Account","label"=>"Account","ifNotAuth"=>true),
			"get some help"=> array("verb"=>"Help","label"=>"Help"),
			"access your vitals"=> array("verb"=>"Profile","label"=>"Profile","ifAuth"=>true)
					  );
		$this->renderable->assign("menu",$verbs);
		$this->notices = array();
		$this->body = array();
		
		if( $_SESSION['id'] == "" )
 		{
			$stored_searches = $_COOKIE['stored_searches'];
			$stored_searches = explode(",",$stored_searches);
		}
		else
		{
			$findSearches = array("explicit"=>true,"all"=>true,"table"=>"searches","order"=>"lastrun DESC","attributes"=>array("user"=>$_SESSION['id'],"active"=>1));
			$searches_results = $this->database->select($findSearches);
			$stored_searches = array();
			foreach($searches_results as $index=>$search)
			{
				$stored_searches[] = $search['data'];
			}
		}
		
		$this->RecallChannelSearches(&$stored_searches);
		
		$this->renderable->assign("session_user",array("user"=>$_SESSION['user'],"email"=>$_SESSION['email'],"settings"=>unserialize ($_SESSION['settings']) ) );
		$this->renderable->assign("stored_searches",$stored_searches);
		$this->renderable->assign("globals",$GLOBALS);
 	}
 	
 	
 	/**
 	 * @function Brutus:verb
 	 * Do something.
 	 * @author tingham
 	 * @created 8/26/08 10:17 PM
 	 **/
 	function verb( $request )
 	{
 		if( is_callable( array("Brutus",$request) ) )
 		{
 			$params = array();
 			$keys = array_keys($_GET);
 			foreach($keys as $key)
 			{
 				$params[$key] = $_GET[$key];
 			}
 			
 			$keys = array_keys($_POST);
 			foreach($keys as $key)
 			{
 				$params[$key] = $_POST[$key];
 			}
 			
			call_user_func( array("Brutus",$request), $params );
			
			$this->renderable->assign("verb",$request);
 		}
 		else
 		{
 			$notice = new Notice("Undefined Verb Invoked.","A method was called &ldquo;{$request}&rdquo; which is not defined.");
 			$this->notices[] = $notice;
 			$this->renderable->assign("notices",$this->notices);
 		}
 	}
 	
 	/**
 	 * @function Brutus:BrowseTable
 	 * @author tingham
 	 * @created 9/2/08 2:23 AM
 	 **/
 	function BrowseTable( $params )
 	{
 		if( is_array($params) )
 		{
 			$this->body[] = "<div id='browseTable'>";
 			$channels = array();
 			$this->RecallChannelSearches(&$channels);
			$renderable = new Renderable(getcwd()."/templates/termlist.html.tpl");
			$renderable->smarty->assign("results",$channels);
			$this->body[] = $renderable->Render();
 			
			// 5 Most popular searches.
			$statement = "select distinct(data) as Term, sum(runcount) as Count from searches group by data order by sum(runcount) DESC limit 5;";
			$mostpopular = $this->database->query($statement);
			if( $mostpopular )
			{
				$mostpopular_result = $this->database->fetchAll($mostpopular);//->fetchAll(SQLITE_ASSOC);
			}
			$renderable = new Renderable(getcwd()."/templates/termlist.html.tpl");
			$renderable->smarty->assign("results",$mostpopular_result);
			$renderable->smarty->assign("fieldA","Term");
			$renderable->smarty->assign("fieldB","Count");
			$this->body[] = $renderable->Render();
			//$this->body[] = var_export($mostpopular_result, true);
			
			// 5 Most recent searches.
			$statement = "select distinct(data) as Term, lastrun as Date from searches group by data order by lastrun DESC limit 5;";
			$mostrecent = $this->database->query($statement);
			if( $mostrecent )
			{
				$mostrecent_result = $this->database->fetchAll($mostrecent);//->fetchAll(SQLITE_ASSOC);
			}
			$renderable = new Renderable(getcwd()."/templates/termlist.html.tpl");
			$renderable->smarty->assign("results",$mostrecent_result);
			$renderable->smarty->assign("fieldA","Term");
			$renderable->smarty->assign("fieldB","Date");
			$this->body[] = $renderable->Render();
			//$this->body[] = var_export($mostrecent_result, true);
			
			if( $_SESSION['id'] != "" )
			{
				if( stristr($GLOBALS['DSN'],"mysql") )
				{
					$user = addslashes($_SESSION['user']);
				}
				else
				{
					$user = sqlite_escape_string ( $_SESSION['user'] );
				}
				$statement = "select * from messages where content like '".$user.":%' order by stamp DESC limit 20;";
				$messages = $this->database->query($statement);
				if( $messages )
				{
					$messages_result = $this->database->fetchAll($messages);//->fetchAll(SQLITE_ASSOC);
				}
				$renderable = new Renderable(getcwd()."/templates/searchresults.html.tpl");
				$renderable->smarty->assign("results",$messages_result);
				$renderable->smarty->assign("subset","messages");
				$renderable->smarty->assign("showHeaders",true);
				$renderable->smarty->assign("showOps",false);
				$renderable->smarty->assign("caption","The Last ".(sizeOf($messages_result))." Posts Regarding You.");
				$this->body[] = "<br /><br />".$renderable->Render()."<br /><br />";
			}
						
			// Last 20 question marks.
			$statement = "select * from messages where content like '%?' order by stamp DESC limit 20;";
			$questions = $this->database->query($statement);
			if( $questions )
			{
				$questions_result = $this->database->fetchAll($questions);//->fetchAll(SQLITE_ASSOC);
			}
			$renderable = new Renderable(getcwd()."/templates/searchresults.html.tpl");
			$renderable->smarty->assign("results",$questions_result);
			$renderable->smarty->assign("subset","questions");
			$renderable->smarty->assign("showHeaders",true);
			$renderable->smarty->assign("showOps",false);
			$renderable->smarty->assign("caption","The Last ".(sizeOf($questions_result))." Questions Asked in All Channels");
			$this->body[] = $renderable->Render()."<br /><br />";//$this->body[] = var_export($questions_result, true);

			// Last 20 lines from most active channel.
			$statement = "select distinct(channel) as channelName, count(*) as total from messages group by channel order by total DESC;";
			$mostactive = $this->database->db->query($statement);
			if( $mostactive )
			{
				$mostactive_result = $this->database->fetchAll($mostactive);//->fetch(SQLITE_ASSOC);
				$mostactive_result = array_shift($mostactive_result);
			}
			
			if( stristr($GLOBALS['DSN'],"mysql") )
			{
				$channel = addslashes($mostactive_result['channelName']);
			}
			else
			{
				$channel = sqlite_escape_string ( $mostactive_result['channelName'] );
			}
			$statement = "select * from messages where channel = '".$channel."' order by stamp DESC limit 20;";
			$mostactivelines = $this->database->query($statement);
			if( $mostactivelines )
			{
				$mostactivelines_result = $this->database->fetchAll($mostactivelines);//->fetchAll(SQLITE_ASSOC);
			}
			$renderable = new Renderable(getcwd()."/templates/searchresults.html.tpl");
			$renderable->smarty->assign("results",$mostactivelines_result);
			$renderable->smarty->assign("showHeaders",true);
			$renderable->smarty->assign("showOps",false);
			$renderable->smarty->assign("subset","mostactive");
			$renderable->smarty->assign("caption",(sizeOf($mostactivelines_result))." Recent Lines from the Most Active Channel");
			//$renderable->smarty->assign("q",$params['q']);
			$this->body[] = $renderable->Render()."<br /><br />";//var_export($mostactivelines_result, true);
			
 			$this->body[] = "</div>";
 		}
 	}
 	/**
 	 * @function Brutus:ModifySearches
 	 * @author tingham
 	 * @created 9/2/08 1:53 AM
 	 **/
 	function ModifySearches( $params )
 	{
 		if( is_array($params) )
 		{
 			$modCountEnable = 0;
 			$modCountDisable = 0;
 			$str = "";
 			if( isset($params['enable']) )
 			{
 				$enable = explode(",",$params['enable']);
				foreach($enable as $id)
				{
					if( trim($id) != "" )
					{
						$str = $str." ".$id;
						if( $this->database->SetSearchActive( array("id"=>$id,"active"=>1) ) )
						{
							$modCountEnable++;
						}
					}
				}
 			}
 			if( isset($params['disable']) )
 			{
 				$disable = explode(",",$params['disable']);
				foreach($disable as $id)
				{
					if( trim($id) != "" )
					{
						$str = $str." ".$id;
						if( $this->database->SetSearchActive( array("id"=>$id,"active"=>0) ) )
						{
							$modCountDisable++;
						}
					}
				}
 			}
 			print "Action Complete\nDisabled: $modCountDisable Searches.\nEnabled: $modCountEnable Searches.\n";
 		}
 	}
 	
 	/**
 	 * @function Brutus:ImportCookieSearches
 	 * @author tingham
 	 * @created 9/2/08 12:59 AM
 	 **/
 	function ImportCookieSearches( $params )
 	{
 		if( is_array($params) )
 		{
 			if( isset($params['id']) )
 			{
 				// fetch all cookie search data.
 				$stored_searches = $_COOKIE['stored_searches'];
				$stored_searches_array = explode(",",$stored_searches);
		
 				// iterate and insert into searches for user=>id.
 				foreach($stored_searches_array as $data)
 				{
 					if( trim($data) != "" )
 					{
						$this->database->InsertSearch(array("user"=>$params['id'],
															"data"=>$data,
															"lastrun"=>time(),
															"runcount"=>1,
															"active"=>1));
					}
 				}
 				$_COOKIE['stored_searches'] = "";
 				print "Imported The Following Items:\n".$stored_searches;
 			}
 		}
 	}
 	
 	/**
 	 * @function Brutus:Profile
 	 * @author tingham
 	 * @created 9/1/08 11:57 PM
 	 **/
 	function Profile( $params )
 	{
 		if( isset($_SESSION['id']) )
 		{
			if( is_array($params) )
			{
				if( isset($_SESSION['id']) )
				{
					$renderable = new Renderable(getcwd()."/templates/profile.html.tpl");
					$findUser = array("explicit"=>true,"all"=>true,"table"=>"user","attributes"=>array("id"=>$_SESSION['id']));
					$user_results = $this->database->select($findUser);
					$renderable->smarty->assign("user",array_pop($user_results));
					
					$findSearches = array("explicit"=>true,"all"=>true,"table"=>"searches","order"=>"lastrun DESC","attributes"=>array("user"=>$_SESSION['id']));
					$searches_results = $this->database->select($findSearches);
					$search_results_prep = array();
					foreach($searches_results as $index=>$search)
					{
						$search['totalPopularity'] = $this->database->FetchTotalSearchPopularity($search['data']);
						$search_results_prep[$index] = $search;
					}
					$renderable->smarty->assign("searches",$search_results_prep);					
					
					$this->body[] = $renderable->Render();
				}
			}
		}
		else
		{
			$this->body[] = Renderable::RenderAuthRequired($params);
		}
 	}
 	/**
 	 * @function Brutus:Approve
 	 * @author tingham
 	 * @created 9/1/08 9:16 PM
 	 **/
 	function Approve( $params )
 	{
 		if( isset($_SESSION['id']) )
 		{
			if( is_array($params) )
			{
				if( isset($params['key']) )
				{
					$findUsers = array("explicit"=>true,"all"=>true,"table"=>"user","attributes"=>array("confirmed"=>0));
					$user_results = $this->database->select( $findUsers );
					$renderable = new Renderable(getcwd()."/templates/approveconfirm.html.tpl");
					$user_results_prep = array();
					foreach($user_results as $index=>$user)
					{
						$match = md5(serialize($user));
						if( $match == $params['key'] )
						{
							$user_results_prep[$index] = $user;
						}
					}
					$renderable->smarty->assign("users",$user_results_prep);
					$renderable->smarty->assign("key",$params['key']);
					
					if( isset($params['confirmed']) )
					{
						if( $params['confirmed'] == 1 )
						{
							// mark confirmed
							// send welcome email
							if( $this->database->ConfirmUser( array("id"=>$params['id']) ) )
							{
								$renderable->smarty->assign("message","User account approved successfully.");
								$renderable->smarty->assign("confirmed",1);
							}
							else
							{
								$notice = new Notice("Database Error","There was an error attempting to update the user record.");
								$this->notices[] = $notice;
							}
						}
						elseif( $params['confirmed'] == 0 )
						{
							// mark declined
							// send declined email
							if( $this->database->DeclineUser( array("id"=>$params['id']) ) )
							{
								$renderable->smarty->assign("message","User account declined successfully.");
								$renderable->smarty->assign("confirmed","0");
							}
							else
							{
								$notice = new Notice("Database Error","There was an error attempting to update the user record.");
								$this->notices[] = $notice;
							}
						}
					}
					$this->body[] = $renderable->Render();
				}
			}
		}
		else
		{
			$this->body[] = Renderable::RenderAuthRequired($params);
		}
 	}
 	
 	/**
 	 * @function Brutus:AuthenticatePassthru
 	 * @author tingham
 	 * @created 9/1/08 11:02 PM
 	 **/
 	function AuthenticatePassthru( $params )
 	{
 		if( is_array($params) )
 		{
 			//var_dump($params);
 			$params['silent'] = true;
 			if( $this->Authenticate($params) )
 			{
				$request = array();
				if( isset($params['Vverb']) )
				{
					$params['verb'] = $params['Vverb'];
				}
				foreach($params as $key=>$value)
				{
					$_POST[$key] = $value;
				}
				$requestString = implode("&",$request);
				//print("Location: http://".$_SERVER['SERVER_NAME']."/".$this->basedir."index.php?".$request);
				$this->verb($params['verb']);
			}
			else
			{
				print "Login Failure.";
			}
 		}
 	}
 	/**
 	 * @function Brutus:Authenticate
 	 * @author tingham
 	 * @created 8/31/08 11:23 PM
 	 **/
 	function Authenticate( $params )
 	{
 		if( is_array($params) )
 		{
 			if( isset($params['user']) &&
 				isset($params['password']) )
 			{
 				$findUser = array("explicit"=>true,
 								  "all"=>true,
 								  "table"=>"user",
 								  "attributes"=>array("user"=>$params['user'],"password"=>crypt($params['password'],'BT'),
 								  					  "confirmed"=>1));
				$user_result = $this->database->Select($findUser);
				if( $user_result )
				{
					$user_row = array_pop($user_result);
					$_SESSION['id'] = $user_row['id'];
					$_SESSION['user'] = $user_row['user'];
					$_SESSION['email'] = $user_row['email'];
					$_SESSION['settings'] = $user_row['settings'];
					if( $params['silent'] == false)
					{
						print "<div>Welcome Back ".$user_row['user']."</div>";
					}
					else
					{
						return true;
					}
				}
 			}
 			else
 			{
 				if( $params['silent'] == false )
 				{
 					print "<div>Seriously?</div>";
 				}
 				else
 				{
 					return false;
 				}
 			}
 		}
 	}
 	/**
 	 * @function Brutus:Account
 	 * Displays the account screen or receives a registration request.
 	 * @author tingham
 	 * @created 8/30/08 11:56 PM
 	 **/
 	function Account( $params )
 	{
 		// 1. Show the form.
 		// 2. Receive the form and send an email.
 		// 3. Notify selected person that an identification request came in.
 		// 4. Generate authenticated link (build a php script that can be fired to activate the account.)
 		if( is_array($params) )
 		{
 			$id = FetchValueFrom($params,"id");
 			$user = FetchValueFrom($params,"user");
 			$email = FetchValueFrom($params,"email");
 			$reference = FetchValueFrom($params,"reference");
 			$password = FetchValueFrom($params,"password");
 			$step = $_GET['step'];
 			$message = FetchValueFrom($params,"message");
 			
 			$known_users = $this->database->KnownUsers();
 			
	 		$renderable = new Renderable(getcwd()."/templates/accountform.html.tpl");
	 		$renderable->smarty->assign("known_users",$known_users);
 			switch( $step )
 			{
 				case 1:
 					if( $user != "" &&
 						strpos ( $email, "@" ) > 0 &&
 						intval ( $reference ) > 0 &&
 						$password != "" )
					{
						$id = $this->database->InsertUser( array("user"=>$user,
																 "email"=>$email,
																 "reference"=>$reference,
																 "password"=>$password,
																 "settings"=>"",
																 "confirmed"=>0) );
						if( $id == -1 )
						{
							//user exists
							print "user exists.";
							return;
						}
						elseif( $id == 0 )
						{
							//database error.
							print "database error";
							return;
						}
						else
						{
							print file_get_contents("http://".$_SERVER['SERVER_NAME']."/".$this->basedir."ajax.php?verb=Account&id=".$id."&step=2");
							return;
						}
					}
					else
					{
						//Header("Location: http://".$_SERVER['SERVER_NAME']."/".$this->basedir."ajax.php?verb=Account&message=".urlencode("Please ensure that all fields have been filled out."));
							$renderable->smarty->assign("step",1);
							$renderable->smarty->assign("message","Please ensure that all fields have been filled out.");
 							$renderable->smarty->assign("user",array("user"=>$user,
																	 "email"=>$email,
																	 "reference"=>$reference,
																	 "password"=>""));
							print $renderable->Render();
					}
 					break;
 				case 2:
 					
					$findUser = array("explicit"=>true,
												  "all"=>true,
												  "table"=>"user",
												  "attributes"=>array("id"=>$id));
					$result = $this->database->select($findUser);
					if( $result )
					{
						$user_record = array_pop($result);
	
						$findReference = array("explicit"=>true,
											   "all"=>true,
											   "table"=>"user",
											   "attributes"=>array("id"=>$user_record['reference']));
	
						$reference_result = $this->database->select($findReference);
						if( sizeOf($reference_result) > 0 )
						{
							$reference_record = array_shift($reference_result);
							$toEmail = $reference_record['email'];
							$toUser = $reference_record['user'];
						}
						else
						{
							//bail because there's no reference.
							$toEmail = "brutus@coalmarch.com";
							$toUser = "Brutus";
						}
						
						$mailKey = md5(serialize($user_record));
						$bodyText = "Dear $toUser,\n\nSomeone claiming to be ".$user_record['user']." <".$user_record['email']."> has requested that you confirm that they in fact own the nickname they are registering for on Brutus. If you haven't spoken to them recently do not confirm this action without first logging into IRC or emailing the person (if you know them that well) to make sure that they are who they say they are.\nThanks for doing your part.";
	
						$bodyText .= "\n\nApprove: http://".$_SERVER['SERVER_NAME']."/".$this->basedir."index.php?verb=Approve&key=".$mailKey."\n\n";
						$bodyText .= "\n\nDecline: http://".$_SERVER['SERVER_NAME']."/".$this->basedir."index.php?verb=Decline&key=".$mailKey."\n\n";
						$headers = 'To: '.$toUser." <$toEmail>\r\n";
						$headers .= "From: Brutus <brutus@coalmarch.com>\r\n";
						$headers .= "Reply-To: Brutus <brutus@coalmarch.com>\r\n";
						print $headers;
						$sendmail = mail($toEmail,'Worldforge Brutus Account Verification Request',$bodyText,$headers);// mail ( $toEmail, "Worldforge Brutus Account Verification Request", $bodyText, $headers );
						
						$renderable->smarty->assign("step",2);
						$renderable->smarty->assign("user",$user_record);
						$renderable->smarty->assign("sendmail",$sendmail);
						$renderable->smarty->assign("mail","<pre>".$bodyText."</pre>");
						print $renderable->Render();
						return;
					}
 					break;
 				default: /* step 1 */
 					$renderable->smarty->assign("step",1);
 					$renderable->smarty->assign("user",array("user"=>$user,
 															 "email"=>$email,
 															 "reference"=>$reference,
 															 "password"=>""));
					$renderable->smarty->assign("message",$message);
 					break;
 			}
 			$this->body[] = $renderable->Render();
 		}
 	}
 	
 	/**
 	 * @function Brutus:SearchForm
 	 * Retrieves the search form template and assigns it to the body of the document.
 	 * @author tingham
 	 * @created 8/27/08 10:22 PM
 	 **/
 	function SearchForm( $params )
 	{
 		$renderable = new Renderable(getcwd()."/templates/searchform.html.tpl");
 		
 		$this->body[] = $renderable->Render();
		$this->renderable->assign("body",$this->body);
 	}
 	
 	/**
 	 * @function Brutus:SearchResults
 	 * @author tingham
 	 * @created 8/27/08 10:40 PM
 	 **/
 	function SearchResults( $params )
 	{
 		$bStoreSearch = false;
 		if( is_array($params) )
 		{
 			if( isset($params['q']) )
 			{
 				$renderable = new Renderable(getcwd()."/templates/searchresults.html.tpl");
 				
 				if( isset($params['type']) )
 				{
 					switch ( $params['type'] )
 					{
 					    case "recentChannel":
 					    	$query = array('explicit'=>true,
										   'all'=>true,
										   'table'=>"messages",
										   'order'=>"stamp ASC",
										   'attributes'=> array('channel'=>$params['q']));
 					    	if( isset($params['lower']) )
							{
								$lower = strToTime(date('Y-m-01',strToTime($params['lower'])));
							}
							else
							{
								$lower = strToTime(date('Y-m-01	',time()-31*24*60*60));
							}
							if( isset($params['upper']) )
							{
								$upper = strToTime(date('Y-m-01',strToTime($params['upper'])));
							}
							else
							{
								$upper = time();
							}
							if( isset($lower) )
							{
								$query['attributes']['stamp >='] = intVal($lower);
							}
							if( isset($upper) )
							{
								$query['attributes']['stamp <='] = intVal($upper);
							}
							$results = $this->database->select($query);
							$renderable->smarty->assign("lower",$lower);
							$renderable->smarty->assign("upper",$upper);
 					    	$renderable->smarty->assign("showDateControls",true);
 					    	$renderable->smarty->assign("caption","Looking at &ldquo;".ucfirst($params['q'])."&rdquo; for ".date('F, Y',$lower)." thru ".date('F, Y',$upper));
 					        break;
						case "recentUser":
							$params['q'] = "user:".$params['q'];
							$this->SearchResults($params);
							return;
							break;
 					    default:
 					        
 					        break;
 					}
 					
 				}
 				elseif( stristr($params['q'],"user:") )
 				{
					$user = explode(":",$params['q']);
					$user = $user[1];
					$query = array(
									  'explicit'=>true,
									  'all'=>true,
									  'table'=>"messages",
									  'order'=>"stamp ASC",
									  'attributes'=> array('user'=>$user));
					if( isset($params['lower']) )
					{
						$lower = strToTime($params['lower']);
					}
					else
					{
	 					$lower = time()-31*24*60*60;
 					}
 					
 					if( isset($params['upper']) )
 					{
 						$upper = strToTime($params['upper']);
 					}
 					else
 					{
 						$upper = time();
 					}
 					
 					if( isset($lower) )
 					{
						$query['attributes']['stamp >='] = intVal($lower);
 					}
 					
 					if( isset($upper) )
 					{
 						$query['attributes']['stamp <='] = intVal($upper);
 					} 					
					
					$results = $this->database->select($query);
					$renderable->smarty->assign("lower",$lower);
					$renderable->smarty->assign("upper",$upper);
			    	$renderable->smarty->assign("showDateControls",true);
 					$renderable->smarty->assign("caption","Looking at User: &ldquo;".$user."&rdquo; for ".date('F, Y',$lower)." thru ".date('F, Y',$upper));
 				}
 				else
 				{
 					$bStoreSearch = true;
	 				$results = $this->database->searchMessages($params['q']);
	 			}
 				if( sizeOf($results) > 0 &&
 					$bStoreSearch ) //don't store alternative searches.
 				{
 					if( $_SESSION['id'] == "" )
 					{
						if( isset($_COOKIE['stored_searches']) )
						{
							$stored_searches = $_COOKIE['stored_searches'];
						}
						else
						{
							$stored_searches = "";
						}
						$stored_searches = explode(",",$stored_searches);
						if( !in_array( $params['q'], $stored_searches ) )
						{
							$stored_searches = array_reverse($stored_searches);
							array_push ( $stored_searches, $params['q'] );
							$stored_searches = array_reverse($stored_searches);
						}
						setcookie ( "stored_searches",implode(",",$stored_searches),time()+(24*60*60*30),"/",$_SERVER['SERVER_NAME'],FALSE,FALSE );
						$this->database->InsertSearch(array("user"=>-1,
														   "data"=>$params['q'],
														   "lastrun"=>time(),
														   "runcount"=>1,
														   "active"=>1));
					}
					else
					{
						$this->database->InsertSearch(array("user"=>$_SESSION['id'],
														   "data"=>$params['q'],
														   "lastrun"=>time(),
														   "runcount"=>1,
														   "active"=>1));
					}
 				}
 				$renderable->smarty->assign("results",$results);
 				$renderable->smarty->assign("showHeaders",true);
 				if( $params['type'] != "recentChannel" )
				{
					$renderable->smarty->assign("showOps",true);
				}
 				$renderable->smarty->assign("q",$params['q']);
 				if( stristr($_SERVER['REQUEST_URI'],"ajax.php") )
 				{
	 				print $renderable->Render();
	 			}
	 			else
	 			{
	 				$this->body[] = $renderable->Render();
	 			}
 			}
 		}
 	}
 	
 	/**
 	 * @function Brutus:SurroundingRows
 	 * @author tingham
 	 * @created 8/27/08 11:46 PM
 	 **/
 	function SurroundingRows( $params )
 	{
 		if( is_array($params) )
 		{
 			if( isset($params['id']) )
 			{
 				//print "<tr><td colspan='4'>will replace with id ".$params['id']."</td></tr>";
 				$results = $this->database->selectSurroundingMessages($params['id']);
 				$renderable = new Renderable(getcwd()."/templates/searchresultsrows.html.tpl");
 				$renderable->smarty->assign("results",$results);
 				$renderable->smarty->assign("append","append");
 				$renderable->smarty->assign("selectedId",$params['id']);
 				$renderable->smarty->assign("showHeaders",true);
 				$renderable->smarty->assign("showOps",true);
 				print $renderable->Render();
 			}
 		}
 	}
 	
 	/**
 	 * @function Brutus:FetchMessageRow
 	 * @author tingham
 	 * @created 8/28/08 10:44 PM
 	 **/
 	function FetchMessageRow( $params )
 	{
 		if( is_array($params) )
 		{
 			if( isset($params['id']) )
 			{
 				$results = $this->database->select( array("explicit"=>true,"table"=>"messages","attributes"=>array("id"=>$params['id'])) );
 				$renderable = new Renderable(getcwd()."/templates/searchresultsrows.html.tpl");
 				$renderable->smarty->assign("results",$results);
 				$renderable->smarty->assign("showHeaders",false);
 				$renderable->smarty->assign("showOps",true);
				print $renderable->Render();
 			}
 		}
 	}
 	
 	/**
 	 * @function Brutus:RecallStoredSearches
 	 * @author tingham
 	 * @created 8/29/08 12:03 AM
 	 **/
 	function RecallStoredSearches()
 	{
 		if( $_SESSION['id'] == "" )
 		{
			$stored_searches = $_COOKIE['stored_searches'];
			$stored_searches = explode(",",$stored_searches);
		}
		else
		{
			$findSearches = array("explicit"=>true,"all"=>true,"table"=>"searches","order"=>"lastrun DESC","attributes"=>array("user"=>$_SESSION['id'],"active"=>1));
			$searches_results = $this->database->select($findSearches);
			$stored_searches = array();
			foreach($searches_results as $index=>$search)
			{
				$stored_searches[] = $search['data'];
			}
		}
		$this->RecallChannelSearches(&$stored_searches);
		
		$renderable = new Renderable(getcwd()."/templates/storedsearches.html.tpl");
 		$renderable->smarty->assign("stored_searches",$stored_searches);
 		print $renderable->Render();
 	}
 	
 	/**
 	 * @function Brutus:RecallChannelSearches
 	 * @author tingham
 	 * @created 8/29/08 9:35 PM
 	 **/
 	function RecallChannelSearches( $stored_searches )
 	{	
 		$channels = $this->database->selectDistinct("channel","messages");
		foreach($channels as $id=>$row)
		{
			$keys = array_keys($row);
			$channel = $row[$keys[0]];
			$stored_searches[ucfirst ( $channel )]['term'] = $channel;
			$stored_searches[ucfirst ( $channel )]['type'] = "recentChannel";
			$stored_searches[ucfirst ( $channel )]['alert'] = "Show recent posts from &ldquo;".ucfirst($channel).".&rdquo;";
		}
 	}
 	
 	/**
 	 * @function Brutus:RemoveStoredSearch
 	 * @author tingham
 	 * @created 8/29/08 12:47 AM
 	 **/
 	function RemoveStoredSearch( $params )
 	{
 		if( is_array($params) )
 		{
 			if( isset($params['term']) )
 			{
				$stored_searches = $_COOKIE['stored_searches'];
				$stored_searches = explode(",",$stored_searches);
				for($i=0;$i<sizeOf($stored_searches);$i++)
				{
					if( $stored_searches[$i] == $params['term'] )
					{
						$stored_searches[$i] = "";
					}
				}
				$stored_searches = array_unique ( $stored_searches );
				setcookie ( "stored_searches",implode(",",$stored_searches),time()+(24*60*60*30),"/",$_SERVER['SERVER_NAME'],FALSE,FALSE );
 			}
 		}
 	}
 	
 	/**
 	 * @function Brutus:resolveUnparsedLogs
 	 * Looks through $rootLogDirectoryPath for any files that are out of date to their
 	 * datacache counterparts or cases where the datacache counterpart is missing and
 	 * generates a datacache.
 	 * @todo  This should move to mysql.
 	 * @author tingham
 	 * @created 8/26/08 10:05 PM
 	 **/
 	function resolveUnparsedLogs( $params="" ) {
 		if( is_array($params) )
 		{
 			// this method likely won't take any until we do db.
 		}
 		$cacheFilesArray = $this->parsedLogs();
 		if( !is_array($cacheFilesArray) )
 		{
 			print "I am the living dead.";
 			die();
 		}
 		
 		$rootPath = getcwd()."/".$this->rootLogDirectoryPath;
 		$rootDir = opendir($rootPath);
 		
 		$filesArray = array();
 		
 		$processedFiles = 0;
 		while( $item = readdir($rootDir) )
 		{
 			if( $item != "." && $item != ".." )
 			{
 				//not a control node.
 				$baseItemPath = $rootPath."/".$item;
 				if( is_dir($baseItemPath) )
 				{
					$subDir = opendir($baseItemPath);
 					while($subItem = readdir($subDir))
 					{
						$baseFileName = $baseItemPath."/".$subItem;
						$checkFileName = md5($baseFileName);
						
						if( !isset($cacheFilesArray[$checkFileName]) )
						{
							if( stristr($baseFileName,".irc") )
							{
								print("\nParsing file $baseFileName.<br />\n");
								$processedFiles = $processedFiles+1;
								$this->parseLogData( $baseFileName );
								$this->ArchiveLog( $baseFileName );
								if( stristr($_SERVER['REQUEST_URI'],"ajax.php") )
								{
									if( $processedFiles >= 2 )
									{
										return;
									}
								}
								else
								{
									if( $processedFiles >= 250 )
									{
										return;
									}
								}
							}
						}
					}
				}
 			}
 		}
 	}
 	
 	/**
 	 * @function Brutus:parseLogData
 	 * Opens a log file and parses the data. If this file has already been interred into the database
 	 * we probably won't need it again.
 	 * @author tingham
 	 * @created 8/27/08 2:19 AM
 	 **/
 	function parseLogData( $fromFile="", $params="" )
 	{
 		if( is_array($params) )
 		{
 			
 		}
 		
 		if( $fromFile == "" )
 		{
 			print("Creature of the wheel, lord of the infernal engine.<br />\n");
 			return;
 		}

		if( !file_exists($fromFile) )
		{
			print("I let it all slip away.");
			return;
		}
		
 		// run the file
 		// eg. 22:15:34 kai:    I need a "hmm" key ;)
 		// [time] [user]:....[message]
 		//(\d\d:\d\d:\d\d\W)(.*:\W\W\W\W)(.*)
 		//$matches = array();
 		//$m = preg_match('/(\d\d:\d\d:\d\d\W)(.*:\W\W\W\W)(.*)/','22:15:34 kai:    I need a "hmm" key ;)',&$matches);
 		//print("Matched $m<br />\n");
 		//var_dump($matches);
 		
 		$dataset = array();
		$input = file($fromFile);
		$outputName = md5($fromFile);
		$datepart = substr($fromFile,0,strpos($fromFile,".irc")-4);
		$datematches = array();
		$d = preg_match('/(\d\d\d\d)(\d\d)(\d\d)/',$datepart,&$datematches);
		
		$date = $datematches[1]."-".$datematches[2]."-".$datematches[3];
		
		$channel = basename( $fromFile, ".irc" );
		$channel = substr($channel,0,strpos($channel,$datematches[1].$datematches[2].$datematches[3]));
		$pattern = '/(\d\d:\d\d:\d\d\W)(.*:\W\W\W\W)(.*)/';
		$processedLines = 0;
		$this->database->maxExecutionLimit = sizeOf($input);
		//print("Chewing through ".sizeOf($input)." lines.<br />\n");

		$baseTime = strtotime($date);
		
		//print("BaseTime: ".$baseTime."\n");
		
		$lastTimeArray = array("00","00","00");
		
		foreach($input as $line)
		{
			print(".");
			if( strlen($line) > 8 )
			{
				$matches = array();
				$m = preg_match($pattern,trim($line),&$matches);
				if( $m > 0 )
				{
					
					$currentTimeArray = explode(":",trim($matches[1]));
					//print("\nCurrent: ".intVal($currentTimeArray[0])." Last: ".intVal($lastTimeArray[0])."\n");
					if( intVal($currentTimeArray[0]) < intVal($lastTimeArray[0]) )
					{
						// advance the day by one.
						//print("\nBase Time Advancing due to rollover.\n");
						$baseTime = $baseTime + (24*60*60);
					}
					
					$stamp = strtotime(date('Y-m-d',$baseTime)." ".trim($matches[1]));
					
					//print("\nStamp: $stamp ".date('Y-m-d h:i:s',$stamp)."\n");
					
					$lastTimeArray = $currentTimeArray;
					
					$record = array("channel" => $channel,
									"stamp" => $stamp,
									"user" => trim($matches[2],' ..:'),
									"content" => trim($matches[3]));
					$dataset[] = $record;
					$processedLines = $processedLines + $this->database->insertMessage( $record );
				}
				else{ /* dead line */ }
			}
		}
		$this->database->maxExecutionLimit = $this->maxExecutionLimit;
		$completeTag = md5($fromFile);
		$path = getcwd()."/".$this->scratchDirectoryPath."processed/$completeTag";
		exec ( "touch $path" );
		$handle = fopen($path,'w');
		fwrite($handle,sizeOf(file($fromFile)));
		fclose($handle);
		//print("\nDone parsing $fromFile<br />\n");
 	}
 	
 	/**
 	 * @function Brutus:ArchiveLog
 	 * @author tingham
 	 * @created 9/9/08 10:26 PM
 	 **/
 	function ArchiveLog( $file )
 	{
 		if( $file != "" )
 		{
 			print("Archive Log: $file\n");
 			if( file_exists($file) )
 			{
 				$basename = basename ( $file );
 				$dirpath = str_replace($basename,"",$file);
				$dirpath = explode("/",$dirpath);
				$dirpath = "/".$dirpath[ sizeOf($dirpath)-3 ]."/".$dirpath[ sizeOf($dirpath)-2 ]."/";
 				print("Creating Folder ".$dirpath."\n");
 				$didmake = @mkdir ( getcwd()."/archives".$dirpath,0777,true );
				$cachefile = md5($file);
				$didmove = @rename ( $file, getcwd()."/archives".$dirpath.$basename );
				if( $didmove )
				{
					print "\n File Relocated. \n";
					@unlink( getcwd()."/scratch/processed/$cachefile" );
				}
 			}
 			else
 			{
 				print "File does not exist at: $file\n";
 			}
 		}
 	}
 	/**
 	 * @function Brutus:parsedLogs
 	 * @return Array of files from the cache directory with mod dates.
 	 * @author tingham
 	 * @created 8/26/08 10:11 PM
 	 **/
 	function parsedLogs() {
 		$scratchPath = getcwd()."/".$this->scratchDirectoryPath."processed/";
 		$scratchDir = opendir($scratchPath);
 		$filesArray = array();
 		while($item = readdir($scratchDir))
 		{
 			$filesArray[$item] = $item;
 		}
 		return $filesArray;
 	}
 	
 	/**
 	 * @function Brutus:Render
 	 * @author tingham
 	 * @created 8/31/08 12:50 AM
 	 **/
 	function Render(  )
 	{
 		$this->renderable->assign("body",$this->body);
 		$this->renderable->display('index.html.tpl');
 	}
	function __destruct()
	{
		
	}
}	

/**
 * @function Brutus:FetchValueFrom
 * @author tingham
 * @created 8/31/08 9:56 PM
 **/
function FetchValueFrom( $array, $key )
{
	if( is_array($array) )
	{
		if( isset($array[$key]) )
		{
			return $array[$key];
		}
		elseif( isset($_POST[$key]) )
		{
			return $_POST[$key];
		}
		elseif( isset($_GET[$key]) )
		{
			return $_GET[$key];
		}
		elseif( isset($_SESSION[$key]) )
		{
			return $_SESSION[$key];
		}
	}
	return null;
}

}
?>
