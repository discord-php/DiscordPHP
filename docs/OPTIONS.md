## Options

The Discord client constructor takes an array of options. Here are the valid options.

| Name | Description | Required | Default |
|------|-------------|----------|---------|
| token | the discord auth token | true | - |
| shardId | the ID of the shard (if you are using sharding) | false | - |
| shardCount | how many shards you are using (if you are using sharding) | false | - |
| loop | the ReactPHP event loop | false | ReactPHP Event Loop |
| logger | the Monolog logger to use | false | Monolog Logger |
| loggerLevel | the Monolog logger level to use | false | Logger::INFO |
| logging | whether logging is enabled | false | true |
| cachePool | a cache pool to use | false | Array Cache Pool |
| loadAllMembers | whether we should preload all members | false | false |
| disabledEvents | an array of events that will not be parsed | false | [] |
| pmChannels | whether pm channels should be parsed on READY | false | false |