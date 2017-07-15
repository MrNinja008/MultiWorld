<?php

namespace MultiWorld;

use MultiWorld\Event\EventListener;
use MultiWorld\Generator\AdvancedGenerator;
use MultiWorld\Generator\BasicGenerator;
use MultiWorld\Task\DelayedTask\RegisterGeneratorTask;
use MultiWorld\Util\ConfigManager;
use MultiWorld\Util\LanguageManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class MultiWorld extends PluginBase {

    const NAME = "MultiWorld";
    const VERSION = "1.3.0 [BETA 2]";
    const AUTHOR = "GamakCZ";
    const GITHUB = "https://github.com/CzechPMDevs/MultiWorld/";

    /** @var  MultiWorld */
    public static $instance;

    // prefix
    public static $prefix;

    ##\
    ### > Events
    ##/

    /** @var  EventListener */
    public $listener;

    ##\
    ### > Utils
    ##/

    /** @var  ConfigManager */
    public $configmgr;

    /** @var  LanguageManager */
    public $langmgr;


    ##\
    ### > Generators
    ##/

    /** @var  BasicGenerator */
    public $bgenerator;

    /** @var  AdvancedGenerator */
    public $agenerator;

    ##\
    ### > Tasks
    ##/

    /** @var  RegisterGeneratorTask */
    public $registerGeneratorTask;


    public function onEnable() {
        // INSTANCE
        self::$instance = $this;

        // events
        $this->listener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->listener, $this);

        // utils
        $this->configmgr = new ConfigManager($this);
        $this->langmgr = new LanguageManager($this);
        $this->configmgr->initConfig();
        $this->langmgr->loadLang();

        // generators
        $this->bgenerator = new BasicGenerator($this);
        $this->agenerator = new AdvancedGenerator($this);

        // tasks
        if(is_file($this->getDataFolder()."/config.yml")) {
            if(strval($this->getConfig()->get("plugin-version")) != "1.3.0") {
                $this->getServer()->getPluginManager()->disablePlugin($this);
                $this->getLogger()->critical(self::getPrefix()."§cConfig is old. Delete config to start MultiWorld.");
            }
        }


        if(strval($this->getDescription()->getName()) != self::NAME || strval($this->getDescription()->getVersion()) != self::VERSION) {
            $this->getServer()->getPluginManager()->disablePlugin($this);
            $this->getLogger()->critical(self::getPrefix()."§cDownload plugin form github! (https://github.com/CzechPMDevs/MultiWorld)");
        }


        if($this->isEnabled()) {
            if($this->isPhar()) {
                $this->getLogger()->info("\n§5**********************************************\n".
                    "§6 ---- == §c[§aMultiWorld§c]§6== ----\n".
                    "§9> Version: §e{$this->getDescription()->getVersion()}\n".
                    "§9> Author: §eCzechPMDevs :: GamakCZ, Kyd\n".
                    "§9> GitHub: §e".self::GITHUB."\n".
                    "§9> Package: §ePhar\n".
                    "§9> Language: §e".LanguageManager::getLang()."\n".
                    "§5*********************************************");
            }
            else {
                $this->getLogger()->info("\n§5**********************************************\n".
                    "§6 ---- == §c[§aMultiWorld§c]§6== ----\n".
                    "§9> Version: §e{$this->getDescription()->getVersion()}\n".
                    "§9> Author: §eCzechPMDevs :: GamakCZ, Kyd\n".
                    "§9> GitHub: §egithub.com/CzechMPDevs/MultiWorld\n".
                    "§9> Package: §esrc\n".
                    "§9> Language: §e".LanguageManager::getLang()."\n".
                    "§5*********************************************");
            }
        }
        else {
            $this->getLogger()->info(self::getPrefix()."§6Submit issue to ".self::GITHUB."/issues");
        }

    }

    public function onDisable() {
        $this->getLogger()->info("§aMultiWorld is disabled!");
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
        if(!($sender instanceof Player)) {
            return;
        }
        if($cmd->getName() == "multiworld") {
            if(isset($args[0])) {
                switch (strtolower($args[0])) {
                    case "help":
                    case "?":
                        if(!$sender->hasPermission("mw.cmd.help")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        $sender->sendMessage(LanguageManager::translateMessage("help-0")."\n".
                        LanguageManager::translateMessage("help-1")."\n".
                        LanguageManager::translateMessage("help-2")."\n".
                        LanguageManager::translateMessage("help-3")."\n".
                        LanguageManager::translateMessage("help-4")."\n");
                        break;
                    case "create":
                    case "add":
                    case "generate":
                        if(!$sender->hasPermission("mw.cmd.create")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        if(empty($args[1])) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("create-usage"));
                            break;
                        }
                        $this->bgenerator->generateLevel($args[1], $args[2], $args[3]);
                        $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("create.generating")));
                        break;
                    case "teleport":
                    case "tp":
                    case "move":
                        if(!$sender->hasPermission("mw.cmd.teleport")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        if(empty($args[1])) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("teleport-usage"));
                            break;
                        }
                        if(!Server::getInstance()->isLevelGenerated($args[1])) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("teleport-levelnotexists"));
                            break;
                        }
                        if(!Server::getInstance()->isLevelLoaded($args[1])) {
                            Server::getInstance()->loadLevel($args[1]);
                            $this->getLogger()->debug(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("teleport-load")));
                        }
                        if(isset($args[2])) {
                            $player = $this->getServer()->getPlayer($args[2]);
                            if($player->isOnline()) {
                                $player->teleport(Server::getInstance()->getLevelByName($args[1])->getSafeSpawn(), 0, 0);
                                $player->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("teleport-done-1")));
                                $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], str_replace("%2", $player->getName(), LanguageManager::translateMessage("teleport-done-2"))));
                                break;
                            }
                            else {
                                $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("teleport-playernotexists"));
                                break;
                            }
                        }
                        else {
                            $sender->teleport(Server::getInstance()->getLevelByName($args[1])->getSafeSpawn(), 0, 0);
                            $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("teleport-done-1")));
                        }
                        break;
                    case "import":
                        if(!$sender->hasPermission("mw.cmd.import")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        if(empty($args[1])) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("import-usage"));
                            break;
                        }
                        $zipPath = ConfigManager::getDataPath()."levels/{$args[1]}.zip";
                        if(!file_exists($zipPath)) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("import-zipnotexists"));
                            break;
                        }
                        $zip = new \ZipArchive;
                        $zip->open($zipPath);
                        $zip->extractTo(ConfigManager::getDataPath()."worlds/");
                        $zip->close();
                        unset($zip);
                        $this->getServer()->loadLevel($args[1]);
                        $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("import-done"));
                        break;
                    case "list":
                    case "ls":
                    case "levels":
                    case "worlds":
                        if(!$sender->hasPermission("mw.cmd.list")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        $list = scandir(ConfigManager::getDataPath()."worlds");
                        unset($list[0]);
                        unset($list[1]);
                        $list = implode(", ", $list);
                        $sender->sendMessage(self::getPrefix().str_replace("%1", $list, LanguageManager::translateMessage("list-done")));
                        break;
                    case "load":
                        if(!$sender->hasPermission("mw.cmd.load")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        if(empty($args[1])) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("load-usage"));
                            break;
                        }
                        if(!$this->getServer()->isLevelGenerated($args[1])) {
                            $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("load-levelnotexists")));
                            break;
                        }
                        $this->getServer()->loadLevel($args[1]);
                        $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("load-done")));
                        break;
                    case "unload":
                        if(!$sender->hasPermission("mw.cmd.unload")) {
                            $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                            break;
                        }
                        if(empty($args[1])) {
                            $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("unload-usage"));
                            break;
                        }
                        if(!$this->getServer()->isLevelGenerated($args[1])) {
                            $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("unload-levelnotexists")));
                            break;
                        }
                        if(!$this->getServer()->isLevelLoaded($args[1])) {
                            $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("unload-unloaded")));
                            break;
                        }
                        $this->getServer()->unloadLevel($this->getServer()->getLevelByName($args[1]));
                        $sender->sendMessage(self::getPrefix().str_replace("%1", $args[1], LanguageManager::translateMessage("unload-done")));
                        break;
                }
            }
            else {
                if(!$sender->hasPermission("mw.cmd.help")) {
                    $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                }
                else {
                    $sender->sendMessage(self::getPrefix().LanguageManager::translateMessage("default-usage"));
                }
            }
        }

    }

    /**
     * @return MultiWorld
     */
    public static function getInstance() {
        return self::$instance;
    }

    /**
     * @return string
     */
    public static function getPrefix() {
        return ConfigManager::getPrefix();
    }
}