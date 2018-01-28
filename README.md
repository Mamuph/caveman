CAVEMAN
================

1. What is it?
--------------

A Helper Tool for [Mamuph Framework](https://github.com/Mamuph/base).



2. How it works?
----------------

Initialize a new Mamuph project:

        caveman.phar new [directory]


Build a project as a self-executable PHAR:
        
        caveman.phar build [mamuph project path] -x
        

Build a project as a signed PHAR:
    
        caveman.phar build [mamuph project path] --private=[PEM private key]
        

Build a project as a self-executable compressed PHAR and increase project major version:
        
        caveman.phar build [mamuph project path] -x -z --inc-major
        

Modify project version:

        caveman.phar inc-major [mamuph project path]
        caveman.phar inc-minor [mamuph project path]
        caveman.phar dec-major [mamuph project path]
        caveman.phar dec-minor [mamuph project path]



3. Help
-------

        caveman.phar --help
        

4. Build Caveman
-----------------

Caveman is able to build itself. Just type:

        php src/index.php build 



