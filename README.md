TGM Batch Updates
=================

TGM Batch Updates is a batch updating utility plugin for WordPress. http://thomasgriffin.io/introducing-tgm-batch-updates-plugin-wordpress/

_Have you ever had a large dataset that you needed to churn through and update?_ I experienced this issue the other week. I had to go through nearly 10,000 licenses to check for a specific type of key, and if that key existed, I needed to update the license data associated with it to match some internal updates that I had put in place. I was not about to attempt this manually, so I set about to create a batch updating utility to do this for me. **Viola - TGM Batch Updates for WordPress was born.**

## How does it work? ##
This is a plugin built for developers. It uses some ajax goodness to process data batch intervals that you set. You can set an upper limit for your data query (defaults to 1000 items) and then process the updates. This is the only interface provided. It is up to you to modify 3 important items inside of the plugin to fit your needs (all conveniently located at the beginning of the class): the `$num` property, the `get_query_data` method and the `process_query_data` method.

The `$num` property delineates how many items you should loop through during each batch interval. It defaults to 10, but if your server can handle it, feel free to up this to 25, 50 or even 100 items at a time. If you are doing something resource intensive, I would recommend keeping it low, maybe 25 max at a time. Otherwise, feel free to expirement.

The `get_query_data` method determines the query to be made for retrieving the data. By default, the query is made using `get_posts`. The internal check for this data tests if the query returns empty or false, so you can adjust this to be any type of query you need.

The `process_query_data` method is the meat of the plugin and determines what is done with the data once it has been retrieved. Since by default we use `get_posts` for our query, we can just loop through the array of objects. Modify this as necessary to fit your specific query.

**The beauty of this is that once you set it in motion, it does everything for you.** Feel free to leave your computer while the processes run.

By default, this plugin will loop through posts and check if a custom field called `my_custom_field` exists. If it does not exist, we add it to the post and assign the post title as the value of the field. This is just one example of how this batch updating utility can be used, but I'm sure there are many other use cases for this. :-)

## Installation and Usage ##
Install like any other normal plugin, either through the Plugins interface or via FTP. Once activated, navigate to Tools > TGM Batch Updates. Determine the upper limit of items you want to update, and then click Start Batch Updates. Once the batch updating process has completed, you can click on the Reset Batch Updates Page button to refresh the page if you want to process more or a different set of batch updates.