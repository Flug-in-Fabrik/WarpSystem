<?php

/**
 * @name WarpSystem
 * @main Securti\warpsystem\WarpSystem
 * @author ["Securti"]
 * @version 0.1
 * @api 3.10.0
 * @description 특별한 워프 시스템입니다.
 * 해당 플러그인 (WarpSystem)은 Fabrik-EULA에 의해 보호됩니다
 * Fabrik-EULA : https://github.com/Flug-in-Fabrik/Fabrik-EULA
 */
 
namespace Securti\warpsystem;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\Player;

use pocketmine\block\Block;

use pocketmine\item\Item;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\particle\DestroyBlockParticle;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

use pocketmine\utils\Config;

class WarpSystem extends PluginBase implements Listener{
   
  public $data;
  
  public $prefix = "§l§b[알림] §r§7";
  
  public static $instance;

  public static function getInstance(){

    return self::$instance;
  }
  public function onLoad(){
    
    self::$instance = $this;
  }
  public function onEnable(){

    $this->getServer()->getPluginManager()->registerEvents($this, $this);

    $a = new PluginCommand("이동", $this);
    $a->setPermission("");
    $a->setUsage("/이동");
    $a->setDescription("이동 관련 명령어입니다");
    $this->getServer()->getCommandMap()->register($this->getDescription()->getName(), $a);

    @mkdir($this->getDataFolder());
    $this->WarpData = new Config($this->getDataFolder() . "WarpData.yml", Config::YAML);
    $this->data = $this->WarpData->getAll();
    
    if(!isset($this->data["count"])){
    
      $this->data["count"] = 0;
      $this->save();
    }
    if(!isset($this->data["list"])){
    
      $this->data["list"]["re"] = 1;
      unset($this->data["list"]["re"]);
      
      $this->save();
    }
    if(!isset($this->data["details"])){
    
      $this->data["details"]["re"] = "1";
      unset($this->data["details"]["re"]);
      
      $this->save();
    }
  }
  public function onCommand(CommandSender $sender, Command $command, string $label, array $array) :bool{
    
    $prefix = $this->prefix;
    
    $command = $command->getName();
    
    $player = $sender;
    
    if(!$player instanceof Player) return true;
    
    $inventory = $player->getInventory();
    
    $item = Item::get(339, 20, 64);
     
    if($command === "이동"){
      
      if(count($array) == 2){
      
        if($player->isOp()){
        
          if($array[0] === "추가"){
          
            $c = $this->data["count"] + 1;
            
            $this->data["details"][$array[1]]["x"] = $player->getX();
            $this->data["details"][$array[1]]["y"] = $player->getY();
            $this->data["details"][$array[1]]["z"] = $player->getZ();
            $this->data["details"][$array[1]]["w"] = $player->getLevel()->getFolderName();
            $this->data["count"] = $this->data["count"] + 1;
            $this->data["list"][$c.""] = $array[1];
            $this->save();
            
            $player->sendMessage($prefix."§b".$array[1]."§f 워프를 추가하였습니다");
          }
          elseif($array[0] === "제거"){
          
            unset($this->data["details"][$array[1]]);
            
            for($i = 1; $i <= $this->data["count"]; $i++){
            
              if($this->data["list"][$i] === $array[1]){
              
                for($v = $i; $v <= $this->data["count"] +1; $v++){
                
                  if(isset($this->data["list"][$v])){
                  
                    $this->data["list"][$v - 1] = $this->data["list"][$v];
                  }
                  
                  if($v == $this->data["count"]){
                  
                    unset($this->data["list"][$v]);
                    $this->data["count"] = $this->data["count"] - 1;
                  }
                }
              }
            }
            
            $this->save();
            
            $player->sendMessage($prefix."§b".$array[1]."§f 워프를 제거하였습니다");
          }
          else{
          
            $player->sendMessage($prefix."/이동 추가 <문자열> - 워프를 추가합니다");
            $player->sendMessage($prefix."/이동 제거 <문자열> - 워프를 제거합니다");
          }
        }
        else{
        
          $this->UI($player);
        }
      }
      else{
      
        $this->UI($player);
      }
    }
    
    return true;
  }
  public function getUI(DataPacketReceiveEvent $e){

    $prefix = $this->prefix;
    
    $pack = $e->getPacket();
    $player = $e->getPlayer();

    if($pack instanceof ModalFormResponsePacket and $pack->formId == 22220000){

      $button = json_decode($pack->formData, true);
      
      if($button != 0){
      
        if(isset($this->data["list"][$button.""])){
        
          $de = $this->data["list"][$button.""];
          
          if(isset($this->data["details"][$de])){
          
            $inventory = $player->getInventory();
    
            $item = Item::get(339, 20, 1);
            
            if($inventory->contains($item)){
            
              $inventory->removeItem($item);
              
              $x = $this->data["details"][$de]["x"];
              $y = $this->data["details"][$de]["y"];
              $z = $this->data["details"][$de]["z"];
              $w = $this->getServer()->getLevelByName($this->data["details"][$de]["w"]);
              
              $pos = new Position($x, $y, $z, $w);
              $pos2 = new Position($x, (int) $y -1, $z, $w);
              $pos3 = new Position($x, $y, $z, $w);
              
              $player->teleport($pos);
              $player->sendMessage($prefix."이동이 완료되었습니다.");
              
              $w->addParticle(new DestroyBlockParticle($pos3, Block::get($w->getBlock($pos2)->getId(), $w->getBlock($pos2)->getDamage())));
            }
            else{
            
              $player->sendMessage($prefix."워프권이 없어 이동이 불가능합니다.");
            }
          }
          else{
          
            $player->sendMessage($prefix."데이터가 설정되지 않았습니다");
          }
        }
        else{
        
          $player->sendMessage($prefix."데이터가 설정되지 않았습니다");
        }
      }
    }
  }
  public function UI(Player $player){
  
    $array = [];
    $list = $this->data["list"];
    asort($list);
    
    $array[] = ["text" => "§l§b· §f메뉴 닫기"];
    
    if(count($list) > 0){
            
      for($i = 1; $i <= $this->data["count"]; $i++){
      
        $i2 = $this->data["list"][$i.""];
        
        $array[] = ["text" => "§l§b· §f".$i2];
      }
    }
          
    $encode = json_encode([

      "type" => "form",   
      "title" => "§l§b[WarpSystem]",    
      "content" => "",
      "buttons" => $array
    ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 22220000;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function save(){
  
    $this->WarpData->setAll($this->data); 
    $this->WarpData->save();
  }
}