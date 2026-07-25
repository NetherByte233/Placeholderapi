<?php

declare(strict_types=1);

namespace NetherByte\PlaceholderAPI;

use NetherByte\PlaceholderAPI\expansion\ExpansionManager;
use NetherByte\PlaceholderAPI\expansion\builtin\BuiltinExpansion;
use NetherByte\PlaceholderAPI\provider\ProviderManager;
use pocketmine\scheduler\ClosureTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase implements Listener{
    private static Main $instance;
    private ExpansionManager $expansionManager;
    private ProviderManager $providerManager;

    /** @var array<string,int> */
    private array $sessionStart = [];

    public function onLoad() : void{
        self::$instance = $this;
    }

    public function onEnable() : void{
        $this->expansionManager = new ExpansionManager();
        $this->providerManager = new ProviderManager($this);

        // Register built-in expansion
        $this->expansionManager->register(new BuiltinExpansion($this));

        // Register events for session tracking
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        foreach($this->getServer()->getOnlinePlayers() as $p){
            $this->sessionStart[$p->getName()] = time();
        }

        // Defer reinstall so other plugins have time to register their Providers in their onEnable()
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
            $this->providerManager->reinstallAll();
        }), 1);
    }

    public static function getInstance() : Main{
        return self::$instance;
    }

    public function getExpansionManager() : ExpansionManager{
        return $this->expansionManager;
    }

    public function getProviderManager() : ProviderManager{
        return $this->providerManager;
    }

    /** Record the player's session start */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $this->sessionStart[$player->getName()] = time();
    }

    /** Cleanup on quit */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        unset($this->sessionStart[$player->getName()]);
    }

    /** Get seconds since this player joined (tracked by this plugin), or 0 if unknown */
    public function getSessionSeconds(Player $player) : int{
        $start = $this->sessionStart[$player->getName()] ?? null;
        return $start !== null ? max(0, time() - $start) : 0;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(strtolower($command->getName()) !== 'papi'){
            return false;
        }
        if(empty($args)){
            $sender->sendMessage(TF::YELLOW . "Usage: /papi <list|providers|download|reload|info|parse|test>");
            return true;
        }
        $sub = strtolower(array_shift($args));
        switch($sub){
            case 'list':
                $list = [];
                foreach($this->expansionManager->getAll() as $exp){
                    $list[] = $exp->getName();
                }
                $sender->sendMessage(TF::GREEN . "Loaded expansions (" . count($list) . "): " . TF::WHITE . implode(', ', $list));
                return true;
            case 'providers':
                $offer = $this->providerManager->getOfferings();
                if(empty($offer)){
                    $sender->sendMessage(TF::YELLOW . "No providers registered.");
                    return true;
                }
                foreach($offer as $prov => $ids){
                    $sender->sendMessage(TF::AQUA . $prov . TF::WHITE . ": " . (empty($ids) ? '-' : implode(', ', $ids)));
                }
                return true;
            case 'download':
                if(empty($args)){
                    $sender->sendMessage(TF::YELLOW . "Usage: /papi download <identifier>");
                    return true;
                }
                $id = (string) $args[0];
                $exp = $this->providerManager->install($id);
                if($exp === null){
                    $sender->sendMessage(TF::RED . "Could not find expansion '$id' from any provider.");
                }else{
                    $sender->sendMessage(TF::GREEN . "Installed expansion: " . $exp->getName());
                }
                return true;
            case 'reload':
                // Clear and re-register builtin, then reinstall saved expansions
                $this->expansionManager->clear();
                $this->expansionManager->register(new BuiltinExpansion($this));
                $count = $this->providerManager->reinstallAll();
                $sender->sendMessage(TF::GREEN . "Reloaded expansions. Reinstalled: $count");
                return true;
            case 'info':
                if(empty($args)){
                    $sender->sendMessage(TF::YELLOW . "Usage: /papi info <identifier[:param]> [player]");
                    return true;
                }
                $raw = (string) $args[0];
                $player = null;
                if(isset($args[1])){
                    $player = $this->getServer()->getPlayerExact((string)$args[1]);
                }
                [$expansionName, $value, $meta] = $this->inspectIdentifier($raw, $player);
                if($value === null){
                    $sender->sendMessage(TF::RED . "No expansion handled '$raw'. Value: <null>");
                    return true;
                }
                $sender->sendMessage(TF::GREEN . "Identifier: " . TF::WHITE . $raw);
                $sender->sendMessage(TF::GREEN . "Value: " . TF::WHITE . $value);
                $sender->sendMessage(TF::GREEN . "Handled by: " . TF::WHITE . $expansionName);
                if(!empty($meta)){
                    foreach($meta as $k => $v){
                        if($v !== null && $v !== ''){
                            $sender->sendMessage(TF::DARK_AQUA . ucfirst($k) . ": " . TF::WHITE . $v);
                        }
                    }
                }
                return true;
            case 'parse':
                if(empty($args)){
                    $sender->sendMessage(TF::YELLOW . "Usage: /papi parse <text> [player]");
                    return true;
                }
                $player = null;
                // If last arg matches an online player, treat it as player and exclude from text
                $maybe = end($args);
                $maybePlayer = null;
                if(is_string($maybe)){
                    $maybePlayer = $this->getServer()->getPlayerExact($maybe);
                }
                if($maybePlayer !== null){ array_pop($args); $player = $maybePlayer; }
                $text = implode(' ', $args);
                $parsed = PlaceholderAPI::parse($text, $player);
                $sender->sendMessage(TF::GREEN . "Parsed: " . TF::WHITE . $parsed);
                return true;
            case 'test':
                if(empty($args)){
                    $sender->sendMessage(TF::YELLOW . "Usage: /papi test <identifier[:param]> [player]");
                    return true;
                }
                $raw = (string)$args[0];
                $player = null;
                if(isset($args[1])){ $player = $this->getServer()->getPlayerExact((string)$args[1]); }
                $val = PlaceholderAPI::get($raw, $player);
                if($val === null){
                    $sender->sendMessage(TF::RED . "No value for '$raw'.");
                }else{
                    $sender->sendMessage(TF::GREEN . "Value: " . TF::WHITE . $val);
                }
                return true;
            default:
                $sender->sendMessage(TF::YELLOW . "Unknown subcommand. Use: list, providers, download, reload, info, parse, test");
                return true;
        }
    }

    /**
     * Try to find which expansion handles a given identifier and return metadata.
     * @return array{string, ?string, array<string, ?string>} [expansionName, value, meta]
     */
    private function inspectIdentifier(string $raw, ?Player $player) : array{
        $exps = $this->expansionManager->getAll();
        $base = $raw; $param = null;
        $pos = strpos($raw, ':');
        if($pos !== false){
            $base = substr($raw, 0, $pos);
            $param = substr($raw, $pos + 1) ?: null;
        }
        // First pass: respect prefix
        foreach([true, false] as $respectPrefix){
            foreach($exps as $exp){
                if($respectPrefix){
                    $prefix = $exp->getIdentifierPrefix();
                    if($prefix !== null && !str_starts_with($base, $prefix)){
                        continue;
                    }
                }
                $value = $exp->onRequestWithParams($base, $param, $player);
                if($value === null){
                    $identifier = $param !== null ? ($base . '_' . $param) : $base;
                    $value = $exp->onRequest($identifier, $player);
                }
                if($value !== null){
                    $meta = [
                        'name' => $exp->getName(),
                        'author' => $exp->getAuthor(),
                        'version' => $exp->getVersion(),
                        'description' => $exp->getDescription(),
                    ];
                    return [$exp->getName(), $value, $meta];
                }
            }
        }
        return ['<none>', null, []];
    }
}
