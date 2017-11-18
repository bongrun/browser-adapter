<?php

namespace interfaces;

/**
 * Interface ProxyInterface
 * @package interfaces
 */
interface ProxyInterface
{
    public function getIp():string;

    public function getPort():int;

    public function getUser():string;

    public function getPassword():string;
}