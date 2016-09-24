CAVERMAN
================

1. What is it?
--------------

A Helper Tool for [Mamuph Framework](https://github.com/Mamuph/base).



2. How it works?
----------------

Initialize a new Mamuph project:

        caverman.phar new [directory]


Build a project as a self-executable PHAR:
        
        caverman.phar build [mamuph project path] -x
        

Build a project as a signed PHAR:
    
        caverman.phar build [mamuph project path] --private=[PEM private key]
        

Build a project as a self-executable compressed PHAR and increase project major version:
        
        caverman.phar build [mamuph project path] -x -z --inc-major
        

Modify project version:

        caverman.phar inc-major [mamuph project path]
        caverman.phar inc-minor [mamuph project path]
        caverman.phar dec-major [mamuph project path]
        caverman.phar dec-minor [mamuph project path]



3. Help
-------

        caverman.phar --help
        

4. Build Caverman
-----------------

Caverman it able to build itself. Just type:

        php src/index.php build 



