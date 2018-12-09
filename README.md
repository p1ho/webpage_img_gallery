# Webpage Image Gallery #

This is a simple PHP program that takes in a URL, parses its HTML to get all img links, filter some images with suspicious links, and output the ones that passed to the page in a gallery format.

This was mainly a practice to get more acquainted with web security. Even though we browse the internet everyday without much care, there are likely lots of security holes still out there that we didn't even know existed. Going through this exercise made me a bit more mindful about potential risks when browsing the web.

Notably the filter process included:
 - URL Query parameters trim
 - File Type check
 - MIME Type Check

To setup, just put the files inside a directory where a local server (e.g., Apache) can serve the files, and then access ```form.html```.
