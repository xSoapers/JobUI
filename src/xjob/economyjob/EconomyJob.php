<?php

namespace xjob\economyjob;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

class EconomyJob extends PluginBase implements Listener{
	/** @var Config */
	private $jobs;
	/** @var Config */
	private $player;

	/** @var  EconomyAPI */
	private $api;

	/** @var EconomyJob   */
	private static $instance;

	public function onEnable(){
		@mkdir($this->getDataFolder());
		if(!is_file($this->getDataFolder()."jobs.yml")){
			$this->jobs = new Config($this->getDataFolder()."jobs.yml", Config::YAML, yaml_parse($this->readResource("jobs.yml")));
		}else{
			$this->jobs = new Config($this->getDataFolder()."jobs.yml", Config::YAML);
		}
		$this->player = new Config($this->getDataFolder()."players.yml", Config::YAML);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->api = EconomyAPI::getInstance();
		self::$instance = $this;
	}

	private function readResource($res){
		$path = $this->getFile()."resources/".$res;
		$resource = $this->getResource($res);
		if(!is_resource($resource)){
			$this->getLogger()->debug("Tried to load unknown resource ".TextFormat::AQUA.$res.TextFormat::RESET);
			return false;
		}
		$content = stream_get_contents($resource);
		@fclose($content);
		return $content;
	}

	public function onDisable(){
		$this->player->save();
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled true
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$job = $this->jobs->get($this->player->get($player->getName()));
		if($job !== false){
			if(isset($job[$block->getID().":".$block->getDamage().":break"])){
				$money = $job[$block->getID().":".$block->getDamage().":break"];
				if($money > 0){
					$this->api->addMoney($player, $money);
					$player->sendPopup("§b+ Money for Job");
				}else{
					$this->api->reduceMoney($player, $money);
				}
			}
		}
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled true
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$job = $this->jobs->get($this->player->get($player->getName()));
		if($job !== false){
			if(isset($job[$block->getID().":".$block->getDamage().":place"])){
				$money = $job[$block->getID().":".$block->getDamage().":place"];
				if($money > 0){
					$this->api->addMoney($player, $money);
					$player->sendPopup("§b+ Money for Job");
				}else{
					$this->api->reduceMoney($player, $money);
				}
			}
		}
	}

	/**
	 * @return EconomyJob
	*/
	public static function getInstance(){
		return static::$instance;
	}

	/**
	 * @return array
	 */
	public function getJobs(){
		return $this->jobs->getAll();
	}

	/**
	 * @return array
	 *
	 */
	public function getPlayers(){
		return $this->player->getAll();
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
		switch(array_shift($params)){
			default:
				$this->FormJob($sender);
		}
		return true;
	}
	
	public function FormJob($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
				}
				switch($result){
					case "0";
					$this->FormJobJoin($player);
					break;
					
					case "1";
					$player->sendMessage("§7[§bx§6Jobs§7] §aYour Job : ".$this->player->get($player->getName()));
					break;
					
					case "2";
					$this->FormInfo($player);
					break;
					
					case "3";
					$job = $this->player->get($player->getName());
					$this->player->remove($player->getName());
					$player->sendMessage("§7[§bx§6Jobs§7] §cYou have left this job. \"$job\"");
					break;
					
				}
			});
			$form->setTitle("§bJobUI");
			$job = $this->player->get($player->getName());
			$form->setContent("Your job $job");
			$form->addButton("Join\nJoin Job", 1, "http://avengetech.me/items/271-0.png");
			$form->addButton("Status\nStatus Job", 1, "http://avengetech.me/items/271-0.png");
			$form->addButton("About\nAbout, Money", 1, "http://avengetech.me/items/271-0.png");
			$form->addButton("Retire\nBack", 1, "http://avengetech.me/items/271-0.png");
			$form->sendToPlayer($player);
			return $form;
	}
	
	public function FormJobJoin($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
				}
				switch($result){
					case "0";
					$this->player->set($player->getName(), "tree-cutter");
					$player->sendMessage("§7[§6Jobs§7] §aYour Job is now §eTree Cutter");
					break;
					
					case "1";
					$this->player->set($player->getName(), "miner");
					$player->sendMessage("§7[§6Jobs§7] §aYour Job is now §eMiner");
					break;
					
					case "2";
					$this->player->set($player->getName(), "melon");
					$player->sendMessage("§7[§6Jobs§7] §aYour Job is now §eMelon");
					break;
					
					case "3";
					$this->player->set($player->getName(), "pumpkin");
					$player->sendMessage("§7[§6Jobs§7] §aYour Job is now §ePumpkin");
					break;
					
					case "4";
					$this->player->set($player->getName(), "flower");
					$player->sendMessage("§7[§6Jobs§7] §aYour Job is now §eFlower");
					break;
					
				}
			});
			$form->setTitle("§bJobUi");
			$form->addButton("Tree Cutter\n2$", 1, "http://avengetech.me/items/17-0.png");
			$form->addButton("Miner\n$1", 1, "http://avengetech.me/items/1-0.png");
			$form->addButton("Melon\n5$", 1, "http://avengetech.me/items/103-0.png");
			$form->addButton("Pumpkin\n5$", 1, "http://avengetech.me/items/86-0.png");
			$form->addButton("Flower\n1$", 1, "http://avengetech.me/items/37-0.png");
			$form->sendToPlayer($player);
			return $form;
	}
	
	public function FormInfo($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
		$result = $data[0];
					
		if($result === null){
			return true;
		}
			switch($result){
				case 0:
				break;
			}
		});
		$form->setTitle("Job Info");
		$form->setContent("JobUI\n\nThank You For\nUse JobUI\ngithub.com/xSoapers\nVersion: TWICE\n\n§bCopyright All Right Resrerved");
		$form->addButton("Okey!");	
		$form->sendToPlayer($player);
	}
}
