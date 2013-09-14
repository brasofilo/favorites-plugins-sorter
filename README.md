Favorite Plugins Sorter
=======================

Order and increment the number of plugins per page in the Favorites tab. 

Based on the Q&A: [How to Sort the Favorite Plugins Screen Alphabetically?](http://wordpress.stackexchange.com/q/76643/12615)

> *When viewing the Favorite Plugins tab, `/wp-admin/plugin-install.php?tab=favorites`, the list comes unordered, is it possible to sort it from A to Z?*


![original favorites](http://i.stack.imgur.com/0DyUr.png)

> *The problem is that the API doesn't offer an ordered query, so we can only sort each page results. The solution is to increase the number of items per page until no paging is necessary. The default limit is 30 plugins.*

## Embedded plugin

FPS has the plugin [Install Via URL](http://wordpress.org/plugins/upload-theme-via-url/) embedded into it. So, after <kbd>Favorites</kbd>, now there's a <kbd>URL</kbd> tab, to allow uploads via URL (for themes and plugins).