
Add an argument "sharedCache" in config which is an alternative json cache for $this->cacheFil. If a file can't be found in the source() function look in the shared cache if the id string is there. If the file associated with the id string exists update $this->cacheFil and save it. Else: proceed with updating the regulat cache as currently implemented in source(). Don't edit try.php this time.

 --

I want to rework the UI messaging in file_loader.php. Instead of the useMsgs argument use a callback and place the messages there. The callback should take an argument that tells the type of message, e.g. "update_cache" and one or more further arguments e.g. for $isString. Choose type strings that make sense. Also update try.php and use the callback so that the resulting messages are basically the same as currently.

 --

Relative src

In a new file make a algrithm that looks for a file with a certain string in name that may have one of multiple prefixes. IF the file can't be found in the start dir go one level up and look through this folder and it sub folders (except the one that we just processed). Repeat this until we processed a given base dir. If the file can't be found retrun null.
- Please make a single function and use scandir
- Use underscores in var names 

Can you make a new folder "debug_rel" with a test file try.php and sime sub dirs and files for debugging? We want to look for a file or folder with #myId in name

--

Make a file try.php for testing the functions, also include code for genearting test files and folders in /debug

- First sub folders base_1 and base_2 used as base folders for the functions
- Folder depth inside these 5, add some files with ids
- Add code to try all cases in the readme file, and produce a debug output in browser
  - build the cache by only loading some of the ids with preload_sources()
  - load some of the missing ids with source()
  - also try to use an id that is missing in file system
  - add a call to find_desc()
- Assume that I will manually run try.php a second time and move a file with an id before that
  - so relativeSrc will be used to find the new loation when source() is called
  - I added a debug flag in the class that is used in the function to produce some output, modify this code if you like so that it matches the debug output better.

 --

Add a case 3.1 similar to this sample from readme

  $file = source('base_1', 'MyId/sub/file',     'cache/files.json');  // default ext .md

The source function finds a file via id (MyId) then it will add sub path and default extension

Also add a test case 3.2 similar to

  $file = source('base_1', 'MyId/subfolder', 'cache/files.json');     // will find the - DESC file

It will try to find the - DESC file whenn the sub path is a folder

 --

Please modify your functions for creating the test structure so that we can be sure that ids in files or folders are unique over all base folders. A single id can exist only once instead of once per base folder.
 