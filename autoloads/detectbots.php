<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Detect Bots Interface
// SOFTWARE RELEASE: 1.7.0
// COPYRIGHT NOTICE: Copyright (C) 1999-2010 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//
class detectBots
{
	/**
	 * @var string
	 */
	private static $ini;
	/**
	 * 
	 * Enter description here ...
	 */
	function detectBots()
	{
		detectBots::$ini = eZINI::instance('detectbots.ini');
	}
	function operatorList()
	{
		return array(
					'is_bot',
					'get_ip',
					'get_browser_name');
	}
	function namedParameterPerOperator()
	{
		return true;
	}
	function namedParameterList()
	{
		return array(
					'is_bot'=>array(
								'ip'=>array(
											'type'=>'string',
											'required'=>false,
											'default'=>'')),
					'get_ip'=>array(),
					'get_browser_name'=>array());
	}
	function modify($tpl,$operatorName,$operatorParameters,&$rootNamespace,&$currentNamespace,&$operatorValue,&$namedParameters)
	{
		switch($operatorName)
		{
			case 'get_ip':
				return ($operatorValue = detectBots::getIp());
				break;
			case 'get_browser_name':
				return ($operatorValue = detectBots::getBrowserName());
				break;
			case 'is_bot':
				$currentIp = (array_key_exists('ip',$namedParameters) && !empty($namedParameters['ip']))?$namedParameters['ip']:detectBots::getIp();
				/**
				 * Found flag
				 */
				$isBot = false;
				/**
				 * Load specific IP's
				 */
				$specificIps = $this->getIni()->variable('Specifics','HideForIPs');
				/**
				 * BotsIniFiles
				 */
				$botsIPFiles = $this->getIni()->variable('Specifics','BotsIPFiles');
				$botsIp = array();
				if(is_array($botsIPFiles) && count($botsIPFiles))
				{
					foreach($botsIPFiles as $botsIPFile)
					{
						if(!empty($botsIPFile))
						{
							$botsIp = eZINI::instance($botsIPFile)->variable('Bots','HideForIPs');
							$botsIp = array_merge($botsIp,(is_array($botsIp) && count($botsIp))?$botsIp:array());
						}
					}
				}
				/**
				 * Merge all
				 */
				$ips = array_merge(array(),(is_array($specificIps) && count($specificIps))?$specificIps:array(),(is_array($botsIp) && count($botsIp))?$botsIp:array());
				if(count($ips) && !empty($currentIp))
				{
					$currentIpStart = implode('.',array_slice(explode('.',$currentIp),0,3));
					foreach($ips as $ip)
					{
						$ipStart = implode('.',array_slice(explode('.',$ip),0,3));
						if($currentIp == $ip || $currentIpStart == $ipStart)
						{
							$isBot = true;
							break;
						}
					}
				}
				/**
				 * Browsers names assigned to bot
				 */
				$contains = $this->getIni()->variable('Browsers','Contains');
				$browserName = detectBots::getBrowserName();
				/**
				 * Si on n'a pas détecté un bot, on vérifie par rapport au nom du navigateur
				 */
				if(!$isBot && !empty($browserName) && is_array($contains) && count($contains))
				{
					foreach($contains as $contain)
					{
						if(stripos($browserName,$contain) !== false)
						{
							$isBot = true;
							break;
						}
					}
				}
				/**
				 * Browsers names not assigned to bot
				 */
				$notContains = $this->getIni()->variable('Browsers','NotContains');
				/**
				 * Si on a détecté un bot, on vérifie par rapport au nom du navigateur que 
				 * ce n'est pas un non bot
				 */
				if(!empty($browserName) && is_array($notContains) && count($notContains))
				{
					foreach($notContains as $contain)
					{
						if(stripos($browserName,$contain) !== false)
						{
							$isBot = false;
							break;
						}
					}
				}
				return ($operatorValue = $isBot);
				break;
		}
	}
	/**
	 * Instanciate ini object
	 * @param string ini file name
	 * @return detectBots
	 */
	public static function instance($_fileName = 'detectbots.ini')
	{
		$self = new self();
		detectBots::$ini = eZINI::instance($_fileName);
		return $self;
	}
	/**
	 * Return ezIni object
	 * @return eZINI
	 */
	public function getIni()
	{
		return detectBots::$ini;
	}
	public static function isBot($_ip = '')
	{
		$rootNamespace = $currentNamespace = $operatorValue = $namedParameters = array();
		$namedParameters['ip'] = $_ip;
		return detectBots::instance()->modify('','is_bot','',$rootNamespace,$currentNamespace,$operatorValue,$namedParameters);
	}
	/**
	 * Method to get current user IP
	 * 
	 * @return string
	 */
	public static function getIp()
	{
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif(isset($_SERVER['HTTP_CLIENT_IP']))
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(isset($_SERVER['REMOTE_ADDR']))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = '127.0.0.1';
		return $ip;
	}
	public static function getBrowserName()
	{
		return (is_array($_SERVER) && array_key_exists('HTTP_USER_AGENT',$_SERVER))?$_SERVER['HTTP_USER_AGENT']:'';
	}
}
?>
