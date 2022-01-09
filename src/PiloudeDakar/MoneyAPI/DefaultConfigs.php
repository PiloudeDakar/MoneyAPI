<?php

declare(strict_types=1);

namespace PiloudeDakar\MoneyAPI;

interface DefaultConfigs{

    public const DEVICE_JSON = '{
  "players": {
  }
}';
    public const YAML = '---
...';
    public const CONFIG = '---
#The amount a player will have after clear and on first connection
basicBalanceAmount: 1000
...';
}
