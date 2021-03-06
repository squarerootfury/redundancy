<?php
	/**
	* Nys.Router.php
	*/
	namespace Redundancy\Nys;
		
	require './nys/Nys.Controller.php';
	/**
	 * PHP UI routing module
	 * @file
	 * @author  squarerootfury <me@0fury.de>	 
	 *
	 * @section LICENSE
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License as
	 * published by the Free Software Foundation; either version 3 of
	 * the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful, but
	 * WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
	 * General Public License for more details at
	 * http://www.gnu.org/copyleft/gpl.html
	 *
	 * @section DESCRIPTION
	 *
	 * PHP UI routing module
	 */	
	class Router{
		/**
		* The current object of the UI Controller
		*/
		private $controller;	
		/**
		* The constructor
		* @todo grab language from settings/ config
		*/
		public function __construct(){
			$GLOBALS['Router'] = $this;
			$this->controller = new UIController();
			if (!isset($_SESSION))
				session_start();                        
                        //Set the cookies if needed
                        $this->CookieInteraction();
                     
			if (isset($_SESSION["lang"]))
				$lang = $_SESSION["lang"];
			else			
				$lang = $this->DoRequest('Kernel','GetConfigValue',json_encode(array("Program_Language")));                        
			$GLOBALS['Language'] = $this->DoRequest('Kernel.InterfaceKernel','SetCurrentLanguage',json_encode(array($lang)));
		}
		/**
		* Interacts with the cookies, creates or deletes them (if needed)
		*/
		public function CookieInteraction(){
                    if (isset($_COOKIE["r2token"]) && isset($_COOKIE["r2lang"]) && !isset($_GET["logout"]) && !isset($_SESSION["Token"]) && !isset($_SESSION["lang"])){
                       
                        if (empty($_SESSION)){
                            $_SESSION["lang"] = filter_input(INPUT_COOKIE, "r2lang");
                            $_SESSION["Token"] = filter_input(INPUT_COOKIE, "r2token");
                        }    
                    }	
                    else if (isset($_GET["logout"])){
                        setcookie("r2lang","", time() -300); 
                        setcookie("r2token","", time() -300); 
                    }   
		}	
		/**
		* Triggers the logout if the session token is not valid anymore (for example when it is already expired.)
		*/
		private function TriggerLogoutIfNeeded(){
			if (isset($_SESSION["Token"])){
				$args = array($_SESSION['Token']);			
				$user = $this->DoRequest('Kernel.UserKernel','GetUser',json_encode($args));
				if (is_null($user)){
					$this->controller->LogOut($this);
					return false;
				}
				else
					return true;
			}
			else{
				return false;
			}
		}
		private function TryToExecuteRoute($routes){
			$executed = false;
			foreach ($routes as $key => $value) {
				if (isset($_GET[$key])){
					call_user_func_array(array($this->controller,$value), array($this));
					$executed = true;
					break;
				}
			}
			if (!$executed)
				call_user_func_array(array($this->controller,$routes["default"]), array($this));
		}
		/**
		* Routes the user to the wanted view
		* @param $url the current url
		*/	
		public function Route($url){					
			$banned = $this->DoRequest('Kernel.SystemKernel','IsMyIPBanned',json_encode(array()));
			if ($banned){
				$this->controller->Banned($this);
				return;
			}	
			$this->TriggerLogoutIfNeeded();
			$loggedInRoutes = array(
				"main" => "main",
				"info"=>"Info",
				"logout"=>"LogOut",
				"files"=>"Files",
				"upload"=>"Upload",
				"detail" => "Detail",
				"download" => "Download",
				"account" => "Account",
				"shares" => "Shares",
				"zipfolder" => "DownloadZip",
				"history" => "Changes",
				"admin"=> "Admin",
				"search"=>"Search",
				"update"=>"Update",	
				"default"=>"main", //The main route redirects to the old one. IN result, files is displayed.	
				"newfolder" =>"NewFolder",	
				"share" => "Share",	
				"settings" =>"Settings",
			);
			$notLoggedInRoutes = array(
				"info"=>"Info",
				"login" => "LogIn",
				"share"=>"Share",
				"shared"=>"SharedDownload",
				"register"=>"Register",
				"default"=>"LogIn",
				"resetpass"=>"ResetPass",
				"requestpass" => "RequestPass",
			);
			if (isset($_SESSION['Token']) && !empty($_SESSION["Token"])){
				$this->TryToExecuteRoute($loggedInRoutes);				
			}		
			else{			
				$this->TryToExecuteRoute($notLoggedInRoutes);	
			}									
		}		
		/**
		* POST-Request helper method
		* @param $module the module
		* @param $method the method
		* @param $args the arguments (json-decoded)
		* @return the response content
		*/
		public function DoRequest($module,$method,$args){	
			
			$domain = $_SERVER['HTTP_HOST'];
			$prefix = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] != "off" && !empty($_SERVER["HTTPS"])) ? 'https://' : 'http://';
			$relative = str_replace('index.php','',$_SERVER['SCRIPT_NAME']).'Includes/api.inc.php';				
			
			$postdata = http_build_query(
			    array(
			        'module' => $module,
					'method' => $method,
					'args' => $args,
					'ip' => $_SERVER['REMOTE_ADDR']
			    )
			);
 
			$opts = array('http' =>
			    array(
			    	'ignore_errors' => true,
			        'method'  => 'POST',
			        'header'  => 'Content-type: application/x-www-form-urlencoded',
			        'content' => $postdata,
			        'user_agent' => (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'Nys'
			    )
			);
 
			$context  = stream_context_create($opts);
 
			$resp = file_get_contents($prefix.$domain.$relative, false, $context);
			
			//When the file content is raw, dont do any json operations
			if ($method =="GetContentOfFile")
				return $resp;
			if (is_int(json_decode($resp))){	
				header('HTTP/1.1 403 Forbidden');				
				//Special handling if the file upload is used.
				if ($method=='UploadFileWrapper'){
					header('Content-type: text/plain');						
					exit('##R_ERR_'.$resp);
				}
			}				
					
			return json_decode($resp);
		}
		/**
		* Redirects the user to a page. POST-Data will be lost
		* @param string $to the target page
		* @param bool $denied determines if the action was redirected because insufficient permissions. If true, the main view will inject an R_ERR_15 (Access denied error message)
		*/
		function DoRedirect($to,$denied = false){
			if ($denied != false){
				header('Location:?'.$to."&rd=1");
			}
			else{				
				header('Location:?'.$to);
			}			
			exit;
		}		
	}	
?>
