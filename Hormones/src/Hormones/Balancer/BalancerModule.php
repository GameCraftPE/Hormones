<?php

/*
 *
 * Hormones
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

namespace Hormones\Balancer;

use Hormones\Balancer\Event\PlayerBalancedEvent;
use Hormones\HormonesPlugin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;

class BalancerModule implements Listener{
	private $plugin;
	/** @var string[] */
	private $exempts;

	public function __construct(HormonesPlugin $plugin){
		$this->plugin = $plugin;
		$this->getPlugin()->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$this->exempts = array_fill_keys(array_map("strtolower", $this->getPlugin()->getConfig()->getNested("balancer.exemptPlayers")), true);
	}

	/**
	 * @param PlayerPreLoginEvent $event
	 *
	 * @priority        HIGH
	 * @ignoreCancelled true
	 */
	public function e_onPreLogin(PlayerPreLoginEvent $event){
		// We can't check permissions here because permission plugins have not checked it yet

		if(count($this->getPlugin()->getServer()->getOnlinePlayers()) >= $this->getPlugin()->getSoftSlotsLimit()){ // getOnlinePlayers() doesn't include the current player
			$player = $event->getPlayer();
			$balEv = new PlayerBalancedEvent($this->getPlugin(), $player, $this->getPlugin()->getLymphResult()->altServer);
			if(in_array(strtolower($player->getName()), $this->exempts)){
				$balEv->setCancelled();
			}
			$this->getPlugin()->getServer()->getPluginManager()->callEvent($balEv);

			if(!$balEv->isCancelled()){
				$player->transfer($balEv->getTargetServer()->address, $balEv->getTargetServer()->port,
					"Server full! Transferring you to {$balEv->getTargetServer()->displayName}");
				$event->setCancelled();
			}
		}
	}

	public function getPlugin() : HormonesPlugin{
		return $this->plugin;
	}
}