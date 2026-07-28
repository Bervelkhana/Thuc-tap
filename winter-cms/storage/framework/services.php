<?php return array (
  'providers' => 
  array (
    0 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    1 => 'Illuminate\\Bus\\BusServiceProvider',
    2 => 'Illuminate\\Cache\\CacheServiceProvider',
    3 => 'Illuminate\\Cookie\\CookieServiceProvider',
    4 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    5 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    6 => 'Illuminate\\Hashing\\HashServiceProvider',
    7 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    8 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    9 => 'Illuminate\\Queue\\QueueServiceProvider',
    10 => 'Illuminate\\Session\\SessionServiceProvider',
    11 => 'Illuminate\\View\\ViewServiceProvider',
    12 => 'Laravel\\Tinker\\TinkerServiceProvider',
    13 => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    14 => 'Winter\\Storm\\Database\\DatabaseServiceProvider',
    15 => 'Winter\\Storm\\Halcyon\\HalcyonServiceProvider',
    16 => 'Winter\\Storm\\Filesystem\\FilesystemServiceProvider',
    17 => 'Winter\\Storm\\Parse\\ParseServiceProvider',
    18 => 'Winter\\Storm\\Html\\HtmlServiceProvider',
    19 => 'Winter\\Storm\\Html\\UrlServiceProvider',
    20 => 'Winter\\Storm\\Network\\NetworkServiceProvider',
    21 => 'Winter\\Storm\\Flash\\FlashServiceProvider',
    22 => 'Winter\\Storm\\Mail\\MailServiceProvider',
    23 => 'Winter\\Storm\\Argon\\ArgonServiceProvider',
    24 => 'Winter\\Storm\\Redis\\RedisServiceProvider',
    25 => 'Winter\\Storm\\Validation\\ValidationServiceProvider',
    26 => 'System\\ServiceProvider',
  ),
  'eager' => 
  array (
    0 => 'Illuminate\\Cookie\\CookieServiceProvider',
    1 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    2 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    3 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    4 => 'Illuminate\\Session\\SessionServiceProvider',
    5 => 'Illuminate\\View\\ViewServiceProvider',
    6 => 'Winter\\Storm\\Database\\DatabaseServiceProvider',
    7 => 'Winter\\Storm\\Halcyon\\HalcyonServiceProvider',
    8 => 'Winter\\Storm\\Filesystem\\FilesystemServiceProvider',
    9 => 'Winter\\Storm\\Parse\\ParseServiceProvider',
    10 => 'Winter\\Storm\\Html\\UrlServiceProvider',
    11 => 'Winter\\Storm\\Argon\\ArgonServiceProvider',
    12 => 'System\\ServiceProvider',
  ),
  'deferred' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastManager' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Factory' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Broadcaster' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\QueueingDispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\BatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\DatabaseBatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'cache' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.store' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.psr6' => 'Illuminate\\Cache\\CacheServiceProvider',
    'memcached.connector' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Cache\\RateLimiter' => 'Illuminate\\Cache\\CacheServiceProvider',
    'hash' => 'Illuminate\\Hashing\\HashServiceProvider',
    'hash.driver' => 'Illuminate\\Hashing\\HashServiceProvider',
    'Illuminate\\Contracts\\Pipeline\\Hub' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'queue' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.connection' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.failer' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.listener' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.worker' => 'Illuminate\\Queue\\QueueServiceProvider',
    'command.tinker' => 'Laravel\\Tinker\\TinkerServiceProvider',
    'Illuminate\\Cache\\Console\\ClearCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ForgetCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Winter\\Storm\\Foundation\\Console\\ClearCompiledCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigCacheCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigClearCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DownCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventCacheCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventClearCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Winter\\Storm\\Foundation\\Console\\EventListCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Winter\\Storm\\Foundation\\Console\\KeyGenerateCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PackageDiscoverCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListFailedCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FlushFailedCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ForgetFailedCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListenCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\MonitorCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneBatchesCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneFailedJobsCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RestartCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryBatchCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\WorkCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteCacheCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteClearCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteListCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleFinishCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleRunCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\UpCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewClearCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ServeCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\VendorPublishCommand' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migrator' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.repository' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.creator' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'composer' => 'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'html' => 'Winter\\Storm\\Html\\HtmlServiceProvider',
    'form' => 'Winter\\Storm\\Html\\HtmlServiceProvider',
    'block' => 'Winter\\Storm\\Html\\HtmlServiceProvider',
    'network.http' => 'Winter\\Storm\\Network\\NetworkServiceProvider',
    'flash' => 'Winter\\Storm\\Flash\\FlashServiceProvider',
    'mail.manager' => 'Winter\\Storm\\Mail\\MailServiceProvider',
    'mailer' => 'Winter\\Storm\\Mail\\MailServiceProvider',
    'Illuminate\\Mail\\Markdown' => 'Winter\\Storm\\Mail\\MailServiceProvider',
    'redis' => 'Winter\\Storm\\Redis\\RedisServiceProvider',
    'redis.connection' => 'Winter\\Storm\\Redis\\RedisServiceProvider',
    'validator' => 'Winter\\Storm\\Validation\\ValidationServiceProvider',
    'validation.presence' => 'Winter\\Storm\\Validation\\ValidationServiceProvider',
  ),
  'when' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastServiceProvider' => 
    array (
    ),
    'Illuminate\\Bus\\BusServiceProvider' => 
    array (
    ),
    'Illuminate\\Cache\\CacheServiceProvider' => 
    array (
    ),
    'Illuminate\\Hashing\\HashServiceProvider' => 
    array (
    ),
    'Illuminate\\Pipeline\\PipelineServiceProvider' => 
    array (
    ),
    'Illuminate\\Queue\\QueueServiceProvider' => 
    array (
    ),
    'Laravel\\Tinker\\TinkerServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Foundation\\Providers\\ConsoleSupportServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Html\\HtmlServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Network\\NetworkServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Flash\\FlashServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Mail\\MailServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Redis\\RedisServiceProvider' => 
    array (
    ),
    'Winter\\Storm\\Validation\\ValidationServiceProvider' => 
    array (
    ),
  ),
);