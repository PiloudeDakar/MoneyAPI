# MoneyAPI
 A simple money api for your server minecraft. You can use it in plugin with all the economy system and in terms of api for manage money in your plugins.
(It have for goal to replace EconomyAPI that will disappear).

__Disclaimer__ :

`Although the plugin idea is not mine (from onebone). All the code have been writen by me.`



## Features


- Economy system
- Economy management for the admins
- Economy API for other plugins (very simple)




## Commands


### Player

`/pay [string : player] [int : amount]` : send money from your balance to a player's balance

`/topmoney` : Get the leaderboard of balances

`/mymoney` : Get your balance's amount

`/getmoney [string : player]` : Get the amount of a player's balance


### Admin

`/addmoney [string : player] [int : amount]` : Add money to a balance

`/removemoney [string : player] [int : amount]` : Remove an amount of money from a balance

`/setmoney [string : player] [int : amount]` : Set a balance to an amount of money

`/clearmoney [string : player]` : Reset a balance to the base amount

`/banmoney [string : player] [int|null : period] ["m"|"h"|"d"|null : duration type]` : Ban a player from the /pay command for a certain time in minutes, hours or days. For ban for ever : no period and no duration type.

`/unbanmoney [string : player]` : Unban a player 
