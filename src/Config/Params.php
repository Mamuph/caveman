<?php
return
[
    'command'       =>
    [
        'description'   => 'Command (build, createcache, build-zshargs)',
        // 'default_value' => 'build',
        'optional'      => false,
        'options'       =>
        [
            'build',
            'inc-major',
            'build-zshargs'
        ]
    ],
    'source'        =>
    [
        'description'   => 'Project source path (By default the project source is autodetected)',
        'optional'      => true,
        'accept_value'  => 'path'
    ],
    'signature-type'     =>
    [
        'long_arg'      => 'signature-type',
        'description'   => 'Define the hash type',
        'optional'      => true,
        'options'       =>
        [
            'md5',
            'sha1',
            'sha256',
            'sha512',
            'openssl'
        ]
    ],
    'private-key'    =>
    [
        'long_arg'      => 'private-key',
        'description'   => 'Private key path used for sign the phar file (PEM format)',
        'optional'      => true,
        'accept_value'  => 'path'
    ],
    'executable'    =>
    [
        'short_arg'     => 'x',
        'description'   => 'Build PHAR as autoexecutable',
        'optional'      => true
    ],
    'compress'      =>
    [
        'short_arg'     => 'z',
        'long_arg'      => 'compress',
        'description'   => 'Compress',
        'optional'      => true,
        'options'       =>
        [
            true,
            'gz',
            'bz2'
        ]
    ],
    'remove-tmp' =>
    [
        'short_arg'     => 'r',
        'long_arg'      => 'remove-tmp',
        'description'   => 'Remove temporal files',
        'optional'      => true
    ],
    'inc-major'     =>
    [
        'long_arg'      => 'inc-major',
        'description'   => 'Increase major version',
        'optional'      => true
    ],
    'dec-major'     =>
    [
        'long_arg'      => 'dec-major',
        'description'   => 'Decrease major version',
        'optional'      => true
    ],
    'inc-minor'     =>
    [
        'long_arg'      => 'inc-minor',
        'description'   => 'Increase minor version',
        'optional'      => true
    ],
    'dec-minor'     =>
    [
        'long_arg'      => 'dec-minor',
        'description'   => 'Decrease minor version',
        'optional'      => true
    ],
    'inc-build'     =>
    [
        'long_arg'      => 'inc-build',
        'description'   => 'Increase build version',
        'optional'      => true
    ],
    'dec-build'     =>
    [
        'long_arg'      => 'dec-build',
        'description'   => 'Descrease build version',
        'optional'      => true
    ]

];