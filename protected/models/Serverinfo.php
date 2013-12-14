<?php
/**
 * @author Craft-Soft Team
 * @version 1.0 beta
 * @copyright (C)2013 Craft-Soft.ru.  Все права защищены.
 * @package CS:Bans
 * @link http://craft-soft.ru/
 */

/**
 * Модель для таблицы "{{serverinfo}}".
 *
 * Доступные поля таблицы '{{serverinfo}}':
 * @property integer $id ID сервера
 * @property integer $timestamp Дата
 * @property string $hostname Название сервера
 * @property string $address Адрес
 * @property string $gametype Тип игры
 * @property string $rcon RCON пароль
 * @property string $amxban_version Версия плагина
 * @property string $amxban_motd Ссылка на MOTD
 * @property integer $motd_delay Время показа MOTD
 * @property integer $amxban_menu Меню (?)
 * @property integer $reasons Причины
 * @property integer $timezone_fixx Разница во времени
 */
class Serverinfo extends CActiveRecord
{
	private $connect;
	public $players = null;
	public $playersmax = null;
	public $name = null;
	public $map = null;
	public $game = null;
	public $os = null;
	public $osimg = null;
	public $secure = null;
	public $playersinfo = array();
	public $online = null;
	public $modimg = null;
	public $vacimg = null;
	public $contact = null;
	public $nextmap = null;
	public $timeleft = null;
	public $mapimg = null;

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return '{{serverinfo}}';
	}

	public function rules()
	{
		return array(
			array('motd_delay, amxban_menu, reasons, timezone_fixx', 'numerical', 'integerOnly'=>true),
			array('rcon', 'length', 'max'=>32),
			array('amxban_motd', 'length', 'max'=>250),
			array('id, timestamp, hostname, address, gametype, rcon, amxban_version, amxban_motd, motd_delay, amxban_menu, reasons, timezone_fixx', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array();
	}

	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'timestamp' => 'Дата',
			'hostname' => 'Имя сервера',
			'address' => 'Адрес',
			'gametype' => 'Мод',
			'rcon' => 'Rcon',
			'amxban_version' => 'Версия',
			'amxban_motd' => 'MOTD',
			'motd_delay' => 'Задержка перед MOTD',
			'amxban_menu' => 'Меню Amxban',
			'reasons' => 'Группы причин банов',
			'timezone_fixx' => 'Разница во времени',
			'map' => 'Карта'
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('timestamp',$this->timestamp);
		$criteria->compare('hostname',$this->hostname,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('gametype',$this->gametype,true);
		$criteria->compare('rcon',$this->rcon,true);
		$criteria->compare('amxban_version',$this->amxban_version,true);
		$criteria->compare('amxban_motd',$this->amxban_motd,true);
		$criteria->compare('motd_delay',$this->motd_delay);
		$criteria->compare('amxban_menu',$this->amxban_menu);
		$criteria->compare('reasons',$this->reasons);
		$criteria->compare('timezone_fixx',$this->timezone_fixx);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	protected function afterFind() {
		parent::afterFind();

		if(!$this->amxban_motd)
			$this->amxban_motd = 'http://'.$_SERVER['SERVER_NAME'] . Yii::app()->urlManager->baseUrl . DIRECTORY_SEPARATOR . 'motd.php?sid=%s&adm=%d&lang=%s';

		$sip = explode(':',  $this->address);
		include_once ROOTPATH . '/include/lgsl_query.php';
		$server = query_live('halflife', $sip[0], $sip[1], $sip[1], '0', 'spet');

		$fields_show  = array("name", "score", "time");
		$fields_hide  = array("teamindex", "pid", "pbguid");
		$fields = lgsl_sort_fields($server, $fields_show, $fields_hide, FALSE);
		$server = lgsl_sort_players($server);
		$rules = array();
		$this->online = $server['b']['status'];
		
		if($this->online)
		{
			$this->players = $server['s']['players'];
			$this->playersmax = $server['s']['playersmax'];
			$this->name = $server['s']['name'];
			$this->map = $server['s']['map'];
			$this->game = $server['s']['game'];
			$this->os = $server['e']['os'] == 'l' ? 'Linux' : 'Windows';
			$this->secure = $server['e']['anticheat'] == 0 ? FALSE : TRUE;
			$this->playersinfo = is_array($server['p']) ? $server['p'] : array();
			$this->timeleft = isset($server['e']['amx_timeleft'])?$server['e']['amx_timeleft']:false;
			$this->nextmap = isset($server['e']['amx_nextmap'])?$server['e']['amx_nextmap']:FALSE;
			$this->contact = isset($server['e']['sv_contact'])?$server['e']['sv_contact']:FALSE;
			$game = $this->gametype ? $this->gametype : $this->game;
			$this->modimg = Yii::app()->urlManager->baseUrl .
					DIRECTORY_SEPARATOR .
					"images".
					DIRECTORY_SEPARATOR .
					"games" .
					DIRECTORY_SEPARATOR .
					$game .
					".gif";
			$this->vacimg = Yii::app()->urlManager->baseUrl .
					DIRECTORY_SEPARATOR .
					"images".
					DIRECTORY_SEPARATOR .
					($this->secure ? "vac.png" : "no_vac.png");
			$this->osimg = Yii::app()->urlManager->baseUrl .
					DIRECTORY_SEPARATOR .
					"images".
					DIRECTORY_SEPARATOR .
					"os" .
					DIRECTORY_SEPARATOR .
					$this->os.
					".png";

			if(!$this->online)
			{
				$mapimage = Yii::app()->urlManager->baseUrl ."/images/maps/noresponse.jpg";
			}
			elseif(is_file(ROOTPATH ."/images/maps/" . $game . '/' . $this->map . ".jpg"))
			{
				$mapimage = Yii::app()->urlManager->baseUrl . "/images/maps/" . $game . '/' . $this->map . ".jpg";
			}
			else
			{
				$mapimage = Yii::app()->urlManager->baseUrl . "/images/maps/" . $game . "/noimage.jpg";
			}

			$this->mapimg = CHtml::image($mapimage, $this->map, array('title' => $this->map, 'class' => 'img-polaroid'));

		}
		return TRUE;
	}

	public function rconCommand($command)
	{
		$addr = explode(':', $this->address);

		$rcon = new Rcon;
		$rcon->Connect($addr[0], $addr[1], $this->rcon);

		$test = $rcon->RconCommand('echo Hi');

		if($test == 'Bad rcon_password.' || $test == 'No password set for this server.' || $test != 'Hi')
			return FALSE;

		return $rcon->RconCommand(CHtml::encode ($command));
	}

	public function getPlayersInfo()
	{
		if(!$this->rcon)
			return FALSE;

		$q = $this->rconCommand('amx_list');

		if(!$q)
			return FALSE;

		$players = array();
		foreach(explode("\x0A", $q) as $p)
		{
			$i = explode("\xFC", $p);

			switch($i[4])
			{
				case 0:
					$type = 'Игрок';
					break;
				case 1:
					$type = 'Бот';
					break;
				case 2:
					$type = 'HLTV';
					break;
				default :
					$type = 'Неизвестен';
			}

			$players[] = array(
				'nick' => $i[0],
				'userid' => $i[1],
				'steamid' => $i[2],
				'ip' => $i[3],
				'playertype' => $type,
				'immunity' => $i[5]
			);
		}
		return $players;
	}

	public static function getCommands()
	{
		return array(
			'' => 'Выберите команду',
			'amx_reloadadmins' => 'Перезагрузить список админов',
			'restart' => 'Перезапустить карту/плагины',
			'stats' => 'Команда stats',
			'status' => 'Команда status',
			'amx_plugins' => 'Список AMX плагинов',
			'amx_modules' => 'Список AMX модулей',
			'meta list' => 'Список модулей MetaMod'
		);
	}

	public static function getAllServers($all = TRUE, $id = false)
	{
		$model = Serverinfo::model()->findAll();

		$return = array();

		if($all)
			$return['0'] = 'Выберите сервер';

		foreach($model as $server)
		{
			$return[$id ? $server->id : $server->address] = $server->hostname;
		}

		if($all)
			$return['unknown'] = 'Любой сервер';

		return $return;
	}

	public function afterSave() {
		if($this->isNewRecord)
			Syslog::add(Logs::LOG_ADDED, 'Добавлен новый сервер <strong>' . $this->address . '</strong>');
		else
			Syslog::add(Logs::LOG_EDITED, 'Изменены детали сервера <strong>' . $this->hostname . '</strong>');
		return parent::afterSave();
	}

	public function afterDelete() {
		Syslog::add(Logs::LOG_DELETED, 'Удален сервер <strong>' . $this->address . '</strong>');
		return parent::afterDelete();
	}

}